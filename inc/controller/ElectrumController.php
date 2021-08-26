<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../utils/Template.php";
require_once __DIR__."/../utils/ElectrumClient.php";
require_once __DIR__."/ElectrumUser.php";

class ElectrumController extends Singleton {
	protected function __construct() {
		add_action("init",array($this,"init"));
	}

	public function init() {
		/*if (isset($_POST["tphbtc_lightning_amount"])) {
			$user=CurrencyUser::getCurrentByCurrency($_REQUEST["currency"]);
			$selectedAmount=intval($_POST["tphbtc_lightning_amount"]);
			$user->generateLightningRequest($selectedAmount);

			wp_redirect(HtmlUtil::getCurrentUrl(),303);
			exit();
		}

		if (isset($_POST["tphbtc-withdraw-lightning"])) {
			$plugin=TonopahBitcoinPlugin::instance();

			try {
				$user=CurrencyUser::getCurrentByCurrency($_REQUEST["currency"]);
				$invoice=$_POST["tphbtc-request"];
				$t=$user->createLightningWithdrawTransaction($invoice);
				tonopah_api()->accountNotice("The withdrawal is processing.","info");
			}

			catch (\Exception $e) {
				tonopah_api()->accountNotice($e->getMessage(),"error");
				wp_redirect(HtmlUtil::getCurrentUrl(),303);
				exit();
			}

			$url=add_query_arg(array(
					"tab"=>NULL,
				),
				HtmlUtil::getCurrentUrl()
			);

			HtmlUtil::redirectAndContinue($url,303);

			try {
				$user->processLightningWithdrawTransaction($invoice,$t);
			}

			catch (\Exception $e) {
				$t->fail($e->getMessage());
			}

			exit();
		}

		if (isset($_POST["tphbtc-withdraw"])) {
			$plugin=TonopahBitcoinPlugin::instance();

			try {
				$user=CurrencyUser::getCurrentByCurrency($_REQUEST["currency"]);
				$account=$user->getAccount();
				$user->withdraw(
					$_POST["tphbtc-address"],
					$account->getCurrency()->parseInput($_POST["tphbtc-amount"]),
					$_POST["tphbtc-fee"]
				);
				tonopah_api()->accountNotice("The withdrawal has been processed.");
			}

			catch (\Exception $e) {
				tonopah_api()->accountNotice($e->getMessage(),"error");
			}

			wp_redirect(HtmlUtil::getCurrentUrl(),303);
			exit();
		}

		if (isset($_POST["tphbtc-generate-lightning-invoice"])) {
			$plugin=TonopahBitcoinPlugin::instance();
			try {
				$user=CurrencyUser::getCurrentByCurrency($_REQUEST["currency"]);
				$account=$user->getAccount();
				$amount=$account->getCurrency()->parseInput($_POST["tphbtc-amount"]);
				$user->generateLightningRequest($amount);
			}

			catch (\Exception $e) {
				tonopah_api()->accountNotice($e->getMessage(),"error");
			}

			wp_redirect(HtmlUtil::getCurrentUrl(),303);
			exit();
		}*/
	}

	public function getElectrumClient($currency) {
		if (!isset($currecy->electrumClient))
			$currency->electrumClient=new ElectrumClient($currency->getMeta("electrumUrl"));

		return $currency->electrumClient;
	}

	private function getFeeRates($currency) {
		$electrum=$this->getElectrumClient($currency);
		$estimatedTransactionSize=200;

		$feeOptions=array(
			"0"=>"Slow",
			"0.5"=>"Medium",
			"1"=>"Fast"
		);

		$res=array();
		foreach ($feeOptions as $rate=>&$option) {
			$rate=$electrum->call("getfeerate","ETA",$rate);
			$rate=$rate*$estimatedTransactionSize/1000;
			$rateLabel=$currency->format($rate);
			$res[]=array(
				"key"=>$rate,
				"label"=>$option." (".$rateLabel.")"
			);
		}

		return $res;
	}

	public function deposit_tab($currency, $user) {
		$electrumUser=new ElectrumUser($currency,$user);
		if (!$electrumUser)
			throw new \Exception("Not logged in");

		if (!$electrumUser->getAddress())
			$electrumUser->generateAddress();

		$account=$electrumUser->getAccount();
		$vars=array(
			"address"=>$electrumUser->getAddress(),
			"addressUrl"=>"bitcoin:".$electrumUser->getAddress(),
			"invoice"=>NULL,
			"amount"=>""
		);

		$vars["currencySymbol"]=$currency->getMeta("symbol");

		$vars["methodOptions"]=array(
			"lightning"=>"Deposit Using Lightning Network (Recommended)",
			"onchain"=>"Deposit Using A Bitcoin Transaction",
		);

		$request=$electrumUser->getLightningRequest();
		if ($request) {
			$expires=$request["timestamp"]+$request["expiration"];

			if (time()<$expires) {
				$requestAmount=$request["amount_msat"]/1000;
				$vars["amount"]=$account->getCurrency()->format($requestAmount,"string");
				$vars["invoice"]=$request["invoice"];
				$vars["invoiceUrl"]="lightning:".$request["invoice"];
			}
		}

		$t=new Template(__DIR__."/../tpl/electrum-deposit-tab.tpl.php");
		return $t->render($vars);
	}

	public function withdraw_tab($currency, $user) {
		$electrumUser=new ElectrumUser($currency,$user);
		if (!$electrumUser)
			throw new \Exception("Not logged in");

		$vars=array();

		$vars["currencySymbol"]=$currency->getMeta("symbol");
		$vars["feeOptions"]=$this->getFeeRates($currency);

		$vars["methodOptions"]=array(
			"lightning"=>"Withdraw Using Lightning Network (Recommended)",
			"onchain"=>"Withdraw Using A Bitcoin Transaction"
		);

		$t=new Template(__DIR__."/../tpl/electrum-withdraw-tab.tpl.php");
		return $t->render($vars);
	}

	public function tab($tab, $currency, $user) {
		switch ($tab) {
			case "deposit":
				return $this->deposit_tab($currency,$user);

			case "withdraw":
				return $this->withdraw_tab($currency,$user);

			default:
				throw new \Exception("No such tab.");
		}
	}

	/*public function process($currencyId, $userId, $params) {
		$user=CurrencyUser::getByCurrencyAndId($currencyId, $userId);
		if (!$user)
			throw new \Exception("User not found");

		return $user->process($params);
	}*/
}