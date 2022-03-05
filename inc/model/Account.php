<?php

namespace cashier;

require_once __DIR__."/Transaction.php";

class Account {
	private $currency;
	private $entityType;
	private $entityId;

	private function __construct($currencyId, $entityType, $entityId=0) {
		$this->currency=Currency::findOne($currencyId);
		if (!$this->currency)
			throw new \Exception("No currency for account.");

		$this->entityType=$entityType;
		$this->entityId=$entityId;

		switch ($this->entityType) {
			case "user":
			case "post":
				if (!$this->entityId)
					throw new \Exception("No entity for account.");
				break;

			case "rake":
				if ($this->entityId)
					throw new \Exception("Rake account shouldn't have an entity.");
				break;

			default:
				throw new \Exception("Unknown account entity in constructor.");
		}
	}

	public function getCurrency() {
		return $this->currency;
	}

	public function getCurrencyId() {
		return $this->currency->ID;
	}

	public static function getUserAccount($userId, $currencyId) {
		return new Account($currencyId,"user",$userId);
	}

	public static function getPostAccount($postId) {
		if (!$postId)
			return NULL;

		$currencyId=get_post_meta($postId,"currency",TRUE);
		if (!$currencyId)
			return NULL;

		return new Account($currencyId,"post",$postId);
	}

	public static function getRakeAccount($currencyId) {
		return new Account($currencyId,"rake");
	}

	public function getBalance() {

		switch ($this->entityType) {
			case "user":
				$metaKey="cashier_balance_".$this->getCurrencyId();
				$balance=get_user_meta($this->entityId,$metaKey,TRUE);
				break;

			case "post":
				$metaKey="cashier_balance_".$this->getCurrencyId();
				$balance=get_post_meta($this->entityId,$metaKey,TRUE);
				break;

			case "rake":
				$optionKey="cashier_rake_".$this->getCurrencyId();
				$balance=get_option($optionKey);
				break;

			default:
				throw new \Exception("Unknown account entity.");
		}

		$balance=intval($balance);

		return $balance;
	}

	public function setBalance($balance) {
		$balance=intval($balance);

		switch ($this->entityType) {
			case "user":
				$metaKey="cashier_balance_".$this->getCurrencyId();
				$balance=update_user_meta($this->entityId,$metaKey,$balance);
				break;

			case "post":
				$metaKey="cashier_balance_".$this->getCurrencyId();
				$balance=update_post_meta($this->entityId,$metaKey,$balance);
				break;

			case "rake":
				$optionKey="cashier_rake_".$this->getCurrencyId();
				update_option($optionKey,$balance);
				break;

			default:
				throw new \Exception("Unknown account entity.");
		}
	}

	private function createTransaction($amount) {
		$t=new Transaction(array(
			"amount"=>intval($amount)
		));

		$t->currency=$this->getCurrencyId();

		return $t;
	}

	public function createDepositTransaction($amount) {
		$t=$this->createTransaction($amount);

		$t->from_type="deposit";
		$t->from_id=NULL;
		$t->to_type=$this->entityType;
		$t->to_id=$this->entityId;

		return $t;
	}

	public function createWithdrawTransaction($amount) {
		$t=$this->createTransaction($amount);

		$t->from_type=$this->entityType;
		$t->from_id=$this->entityId;
		$t->to_type="withdraw";
		$t->to_id=NULL;

		return $t;
	}

	public function createSendTransaction($toAccount, $amount) {
		if (!$toAccount)
			throw new \Exception("Target account doesn't exist");

		if ($this->getCurrencyId()!=$toAccount->getCurrencyId())
			throw new \Exception("Different currency");

		$t=$this->createTransaction($amount);

		$t->from_type=$this->entityType;
		$t->from_id=$this->entityId;
		$t->to_type=$toAccount->entityType;
		$t->to_id=$toAccount->entityId;

		return $t;
	}

	public function getDisplay() {
		switch ($this->entityType) {
			case "user":
				$u=get_user_by("ID",$this->entityId);
				return $u->user_login;
				break;

			case "post":
				$post=get_post($this->entityId);
				return $post->post_title;
				break;
		}
	}

	public function equals($account) {
		if (!$account)
			return FALSE;

		return (
			($this->entityType==$account->entityType) &&
			($this->entityId==$account->entityId) &&
			($this->getCurrencyId()==$account->getCurrencyId())
		);
	}

	public function getTransactionCount($params=array()) {
		$params["currency"]=$this->getCurrencyId();
		$params[]=array(
			array(
				"from_type"=>$this->entityType,
				"from_id"=>$this->entityId,
			),

			array(
				"to_type"=>$this->entityType,
				"to_id"=>$this->entityId,
			),
		);

		return Transaction::getCount($params);
	}

	public function getTransactions($params=array()) {
		$params["currency"]=$this->getCurrencyId();
		$params[]=array(
			array(
				"from_type"=>$this->entityType,
				"from_id"=>$this->entityId,
			),

			array(
				"to_type"=>$this->entityType,
				"to_id"=>$this->entityId,
			),
		);

		return Transaction::findMany($params);
	}

	public function getReserved() {
		$transactions=$this->getTransactions(array(
			"status"=>"reserved"
		));

		$amount=0;
		foreach ($transactions as $transaction)
			$amount+=$transaction->getAmount();

		return $amount;
	}

	public function formatBalance($style="standard") {
		return $this->currency->format($this->getBalance(),$style);
	}

	public function getEventChannel() {
		return "account_".$this->currency->ID."_".$this->entityType."_".$this->entityId;
	}

	public function notify() {
		//error_log($this->getEventChannel());

		$revServer=CashierPlugin::instance()->getRevServer();
		$revServer->notify($this->getEventChannel());
	}
}