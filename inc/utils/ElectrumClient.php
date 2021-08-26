<?php

namespace cashier;

require_once __DIR__."/JsonRpcClient.php";

class ElectrumClient extends JsonRpcClient {
	public function __construct($url) {
		parent::__construct(array(
			"url"=>$url
		));
	}

	public function getDeserializedTransaction($txid) {
		$tx=$this->call("gettransaction",$txid);
		return $this->call("deserialize",$tx);
	}

	public function getTransactionAmount($txid, $addr) {
		$amount=0;
		$data=$this->getDeserializedTransaction($txid);

		foreach ($data["outputs"] as $output) {
			if ($output["address"]==$addr)
				$amount+=$output["value_sats"];
		}

		return $amount;
	}

	public function getAddressDeposits($addr) {
		$hists=$this->call("getaddresshistory",$addr);
		$res=array();

		foreach ($hists as &$hist) {
			$hist["amount"]=$this->getTransactionAmount($hist["tx_hash"],$addr);

			$status=$electrum->call("get_tx_status",$hist["tx_hash"]);
			$hist["confirmations"]=$status["confirmations"];

			if ($hist["amount"]>0)
				$res[]=$hist;
		}

		return $res;
	}

	public function lnPay($invoiceEncoded, $attempts) {
		$res=$this->call("lnpay",array(
			"invoice"=>$invoiceEncoded,
			"attempts"=>intval($attempts)
		));

		if (!$res["success"])
			throw new \Exception("Lightning Payment Failed: ".json_encode($res));

		if (!$res["preimage"])
			throw new Exception("Got no preimage");

		return $res["preimage"];
	}
}
