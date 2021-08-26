<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../utils/Template.php";

class ElectrumUser {
	private $user;
	private $currency;
	private $electrum;

	public function __construct($currency, $user) {
		$this->user=$user;
		$this->currency=$currency;
		$this->getElectrumClient();

		if (!$user || !$user->ID)
			throw new \Exception("not a user");
	}

	private function getElectrumClient() {
		$electrumClient=ElectrumController::instance()->getElectrumClient($this->currency);
		if (!$electrumClient)
			throw new \Exception("Currency not configured: ".$this->currency->ID);

		return $electrumClient;
	}

	private function getMeta($key) {
		$key="cashier_".$this->currency->ID."_".$key;
		return get_user_meta($this->user->ID,$key,TRUE);
	}

	private function setMeta($key, $value) {
		$key="cashier_".$this->currency->ID."_".$key;

		if ($value===NULL)
			delete_user_meta($this->user->ID,$key);

		else
			update_user_meta($this->user->ID,$key,$value);
	}

	public function getAddress() {
		return $this->getMeta("address");
	}

	public function generateAddress() {
		$electrum=$this->getElectrumClient();
		$address=$electrum->call("createnewaddress");
		$this->setMeta("address",$address);
	}

	public function getLightningRequest() {
		return $this->getMeta("lightning_request");
	}

	// Amount in satoshi.
	public function generateLightningRequest($amount) {
		$electrum=$this->getElectrumClient();

		$btcAmount=$amount/100000000;
		$request=$electrum->call("add_lightning_request",$btcAmount);
		if (!$request)
			throw new \Exception("no lightning request generated");

		$this->setMeta("lightning_request",$request);

		return $this->getLightningRequest();
	}

	public function getAccount() {
		return Account::getUserAccount($this->user->ID,$this->currency->ID);
	}

	private function processNewTransactions() {
		$electrum=$this->getElectrumClient();
		$account=$this->getAccount();

		$history=$electrum->call("getaddresshistory",$this->getAddress());
		$historyTxIds=array_column($history,"tx_hash");

		$accountTxIds=array();
		$accountTransactions=$account->getTransactions(array(
			"from_type"=>"deposit"
		));
		foreach ($accountTransactions as $t)
			if ($t->getMeta("txid"))
				$accountTxIds[]=$t->getMeta("txid");

		$newTxIds=array_diff($historyTxIds,$accountTxIds);
		foreach ($newTxIds as $txId) {
			$amount=$electrum->getTransactionAmount($txId,$this->getAddress());

			$t=$account->createDepositTransaction($amount);
			$t->setMeta("txid",$txId);

			if ($amount)
				$t->reserve();

			else
				$t->ignore();
		}
	}

	private function processConfirmations() {
		$electrum=$this->getElectrumClient();
		$account=$this->getAccount();

		$unconfirmedTransactions=$account->getTransactions(array(
			"from_type"=>"deposit",
			"status"=>"reserved"
		));

		$reqConfs=get_option("tphbtc_confirmations",3);
		foreach ($unconfirmedTransactions as $t) {
			$txId=$t->getMeta("txid");

			if ($txId) {
				$status=$electrum->call("get_tx_status",$t->getMeta("txid"));
				if ($status["confirmations"]>=$reqConfs) {
					$t->notice="Confirmed";
					$t->perform();
				}

				else {
					$t->notice=$status["confirmations"]." confirmation(s)";
					$t->save();
				}
			}
		}
	}

	private function processLightningRequest() {
		$electrum=$this->getElectrumClient();
		$originalRequest=$this->getLightningRequest();
		$request=$electrum->call("getrequest",$originalRequest["rhash"]);

		if ($request["status"]==3 && $request["status_str"]=="Paid") {
			$amount=$request["amount_msat"]/1000;
			$account=$this->getAccount();

			$t=$account->createDepositTransaction($amount);
			$t->notice="Lightning Deposit";
			$t->setMeta("invoice",$request["invoice"]);
			$t->perform();

			$this->setMeta("lightning_request",NULL);
		}
	}

	public function createLightningWithdrawTransaction($invoiceEncoded) {
		$electrum=$this->getElectrumClient();
		$invoice=$electrum->call("decode_invoice",$invoiceEncoded);
		$amount=$invoice["amount_msat"]/1000;
		$account=$this->getAccount();

		if (!$amount)
			throw new \Exception("No amount!");

		if ($amount>$account->getBalance())
			throw new \Exception("Insufficient funds");

		$t=$account->createWithdrawTransaction($amount);
		$t->setMeta("invoice",$invoiceEncoded);
		$t->notice="Lightning Withdrawal";
		$t->reserve();

		return $t;
	}

	public function processLightningWithdrawTransaction($invoiceEncoded, $t) {
		$electrum=$this->getElectrumClient();
		$attempts=$this->currency->getMeta("cashier_lightning_attempts");
		$preimage=$electrum->lnPay($invoiceEncoded,$attempts);
		$t->setMeta("preimage",$preimage);
		$t->perform();
	}

	public function withdraw($address, $amountSatoshi, $feeSatoshi) {
		$electrum=$this->getElectrumClient();
		$amountSatoshi=intval($amountSatoshi);
		$account=$this->getAccount();

		if (!$amountSatoshi)
			throw new \Exception("No amount!");

		if ($amountSatoshi+$feeSatoshi>$account->getBalance())
			throw new \Exception("Insufficient funds");

		$amountBtc=$amountSatoshi/100000000;
		$feeBtc=$feeSatoshi/100000000;

		$tx=$electrum->call("payto",array(
			"destination"=>$address,
			"amount"=>$amountBtc,
			"fee"=>$feeBtc
		));

		$txid=$electrum->call("broadcast",$tx);

		$t=$account->createWithdrawTransaction($amountSatoshi+$feeSatoshi);
		$t->notice="Withdrawal";
		$t->setMeta("txid",$txid);
		$t->perform();
	}

	public function process($params) {
		if ($this->getAddress()) {
			$this->processNewTransactions();
			$this->processConfirmations();
		}

		if ($this->getLightningRequest()) {
			$this->processLightningRequest();
		}

		if (isset($params["lightningInvoice"])) {
			$account=$this->getAccount();

			$txs=$account->getTransactions(array(
				"meta%"=>"%".$params["lightningInvoice"]."%"
			));

			if (sizeof($txs)) {
				$tx=$txs[0];
				$t=new Template(__DIR__."/../tpl/paid-invoice.tpl.php");
				$cover=$t->render(array(
					"amount"=>"+".$tx->formatAmount()
				));

				return array(
					"replaceWith"=>array(
						".tphbtc-qrcode-cover"=>$cover
					)
				);
			}
		}
	}

	/*public static function getCurrentByCurrency($currencyId) {
		$u=wp_get_current_user();

		if (!$u || !$u->ID)
			return NULL;

		return CurrencyUser::getByCurrencyAndId($currencyId,$u->ID);
	}

	public static function getByCurrencyAndId($currencyId, $userId) {
		return new CurrencyUser($currencyId,get_user_by("id",$userId));
	}*/
}