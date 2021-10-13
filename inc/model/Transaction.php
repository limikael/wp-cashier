<?php

namespace cashier;

require_once __DIR__."/../utils/Record.php";

/**
 * Statuses:
 *   - new
 *   - complete
 *   - reserved
 *   - ignored
 *   - failed
 */
class Transaction extends Record {
	private static $lock;

	protected $amount;
	protected $status;
	protected $meta;

	public function __construct($data=array()) {
		if ($data) {
			$this->stamp=time();
			$this->amount=$data["amount"];
		}
	}

	public static function initialize() {
		self::field("id","integer not null auto_increment");
		self::field("from_type","varchar(8) null");
		self::field("from_id","integer null");
		self::field("to_type","varchar(8) null");
		self::field("to_id","integer null");
		self::field("currency","integer not null");
		self::field("stamp","integer not null");
		self::field("amount","integer not null");
		self::field("notice","text not null");
		self::field("status","varchar(32) not null");
		self::field("meta","text not null");
	}

	private function getAccount($type, $id) {
		$account=NULL;

		switch ($type) {
			case "post":
				$account=Account::getPostAccount($id);
				break;

			case "user":
				$account=Account::getUserAccount($id,$this->currency);
				break;

			case "rake":
				$account=Account::getRakeAccount($this->currency);
				break;
		}

		if (!$account || $account->getCurrencyId()!=$this->currency)
			return NULL;

		return $account;
	}

	public function getFromAccount() {
		return $this->getAccount($this->from_type,$this->from_id);
	}

	public function getToAccount() {
		return $this->getAccount($this->to_type,$this->to_id);
	}

	public function getOtherAccount($account) {
		if ($this->getFromAccount() && !$this->getFromAccount()->equals($account))
			return $this->getFromAccount();

		if ($this->getToAccount() && !$this->getToAccount()->equals($account))
			return $this->getToAccount();

		return NULL;
	}

	public function getRelativeAmount($account) {
		if ($account->equals($this->getFromAccount()))
			return -$this->amount;

		if ($account->equals($this->getToAccount()))
			return $this->amount;

		return NULL;
	}

	public function getCurrency() {
		return Currency::findOne($this->currency);
	}

	public function formatAmount($style="standard") {
		return $this->getCurrency()->format($this->amount,$style);
	}

	public function formatRelativeAmount($account, $style="standard") {
		$relativeAmount=$this->getRelativeAmount($account);
		return $this->getCurrency()->format($relativeAmount,$style);
	}

	public function formatSiteTime() {
		$localStamp=($this->stamp+(int)(get_option('gmt_offset')*HOUR_IN_SECONDS));
		return gmdate("Y-m-d H:i:s",$localStamp);
	}

	public function getStatus() {
		$status="new";

		if (isset($this->status) && $this->status!="")
			$status=$this->status;

		$statuses=array("new","complete","reserved","ignored","failed");
		if (!in_array($status,$statuses))
			throw new \Exception("Unknown tx status: ".$status);

		return $status;
	}

	public function perform() {
		if (!in_array($this->getStatus(),array("new","reserved")))
			throw new \Exception("Can't perform tx in status: ".$this->getStatus());

		if ($this->getStatus()=="new")
			$this->reserve();

		$toAccount=$this->getToAccount();
		if ($toAccount) {
			$toAccount->setBalance($toAccount->getBalance()+$this->amount);
		}

		else {
			if ($this->to_type!="withdraw") {
				$this->fail("Invalid to account.");
				throw new \Exception("Invalid to account.");
			}
		}

		$this->status="complete";
		$this->save();

		$this->notifyAccounts();
	}

	public function reserve() {
		if ($this->getStatus()!="new")
			throw new \Exception("Can only reserve new tx.");

		if (intval($this->amount)<=0)
			throw new \Exception("No transaction amount.");

		$fromAccount=$this->getFromAccount();
		if ($fromAccount) {
			if ($fromAccount->getBalance()<$this->amount)
				throw new \Exception("Insufficient funds.");

			$fromAccount->setBalance($fromAccount->getBalance()-$this->amount);
		}

		else {
			if ($this->from_type!="deposit")
				throw new \Exception("Invalid from account.");
		}

		$this->status="reserved";
		$this->save();

		$this->notifyAccounts();
	}

	public function ignore() {
		if ($this->getStatus()!="new")
			throw new \Exception("Can only ignore new tx.");

		$this->status="ignored";
		$this->save();

		$this->notifyAccounts();
	}

	public function fail($message) {
		if ($this->getStatus()!="reserved")
			throw new \Exception("Can only fail reserved tx.");

		$fromAccount=$this->getFromAccount();
		if ($fromAccount) {
			$fromAccount->setBalance($fromAccount->getBalance()+$this->amount);
		}

		else {
			if ($this->from_type!="deposit") {
				throw new \Exception("Invalid from account.");
			}
		}

		$this->status="failed";
		$this->setMeta("error",$message);
		$this->save();

		$this->notifyAccounts();
	}

	private function notifyAccounts() {
		if ($this->getFromAccount())
			$this->getFromAccount()->notify();

		if ($this->getToAccount())
			$this->getToAccount()->notify();
	}

	public function getAmount() {
		return $this->amount;
	}

	public function getMetas() {
		$metas=unserialize($this->meta);
		if ($metas===FALSE)
			$metas=array();

		return $metas;
	}

	public function getMeta($key) {
		$metas=$this->getMetas();
		if (!array_key_exists($key,$metas))
			return NULL;

		return $metas[$key];
	}

	public function setMeta($key, $value) {
		$metas=$this->getMetas();
		$metas[$key]=$value;
		$this->meta=serialize($metas);
	}

	/*public static function getLock() {
		if (!self::$lock)
			self::$lock=new WpdbTableLock(self::getFullTableName());

		return self::$lock;
	}*/
}
