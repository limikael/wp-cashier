<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../utils/Template.php";
require_once __DIR__."/../utils/ElectrumClient.php";
require_once __DIR__."/ElectrumUser.php";

// API: curl "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=bitcoin"
// https://api.coingecko.com/api/v3/exchange_rates

class ElectrumController extends Singleton {
	protected function __construct() {
		add_action("wp",array($this,"wp"));
		add_filter("cashier_adapters",array($this,"cashier_adapters"),10,1);
	}

	public function cashier_adapters($adapters) {
		$adapters["electrum"]=array(
			"title"=>"Electrum",
			"config"=>array(
				array(
					"name"=>"electrumUrl"
				),
				array(
					"name"=>"confirmations",
					"type"=>"select",
					"options"=>array(0,1,2,3,4,5,6)
				),
				array(
					"name"=>"withdraw",
					"label"=>"Withdraw Possible",
					"type"=>"select",
					"options"=>array(
						0=>"No",
						1=>"Yes"
					)
				)
			),
			"tabs_cb"=>array($this,"tabs"),
			"tab_cb"=>array($this,"tab"),
			"process_cb"=>array($this,"process"),
			"install_cb"=>array($this,"install"),
			"denominations_cb"=>array($this,"denominations")
		);

		return $adapters;
	}

	public function install($currency) {
		function array_move_key_first(&$a, $k) {
			$a=array_merge(array($k=>$a[$k]),$a);
		}

		error_log("importing rates...");

		$curl=curl_init("https://api.coingecko.com/api/v3/exchange_rates");
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$data=json_decode(curl_exec($curl),TRUE);
		if (!$data)
			throw new \Exception("Unable to import rates");

		$rateMeta=array();
		$rateMeta["btc"]=array(
			"name"=>"Bitcoin",
			"value"=>1/100000000,
			"decimals"=>8,
			"symbol"=>"BTC"
		);

		$rateMeta["sats"]=array(
			"name"=>"Satoshi",
			"value"=>1,
			"decimals"=>0,
			"symbol"=>"SATS"
		);

		foreach ($data["rates"] as $k=>$rateData) {
			if ($rateData["type"]=="fiat") {
				$rateMeta[$k]["value"]=$rateData["value"]/100000000;
				$rateMeta[$k]["name"]=$rateData["name"];
				$rateMeta[$k]["decimals"]=2;
				$rateMeta[$k]["symbol"]=strtoupper($k);
			}
		}

		$name=array_column($rateMeta,'name');
		array_multisort($name,SORT_ASC,$rateMeta);

		array_move_key_first($rateMeta,"eur");
		array_move_key_first($rateMeta,"usd");
		array_move_key_first($rateMeta,"btc");
		array_move_key_first($rateMeta,"sats");

		$currency->setMeta("rates",$rateMeta);
	}

	public function denominations($currency) {
		return $currency->getMeta("rates");
	}

	public function wp() {
		$formHandlers=array(
			"cashier-generate-lightning-invoice"=>array($this,"generateLightningInvoice"),
			"cashier-withdraw-lightning"=>array($this,"withdrawLightning"),
			"cashier-withdraw-onchain"=>array($this,"withdrawOnchain")
		);

		$handler=NULL;
		foreach ($formHandlers as $var=>$handler) {
			if (isset($_POST[$var])) {
				$electrumUser=ElectrumUser::getCurrent();
				if (!$electrumUser)
					throw new \Exception("Not logged in");

				try {
					$handler($electrumUser);
				}

				catch (\Exception $e) {
					error_log(print_r($e,TRUE));
					$this->notice($e->getMessage(),"error");
					wp_redirect(HtmlUtil::getCurrentUrl(),303);
					exit();
				}
			}
		}
	}

	public function withdrawOnchain($electrumUser) {
		$currency=$electrumUser->getCurrency();
		if (!$currency->getMeta("withdraw"))
			throw new \Exception("Can not be withdrawn");

		$user=$electrumUser->getUser();
		$formatter=$currency->getFormatterForUser($user->ID);
		$electrumUser->withdraw(
			$_POST["cashier-address"],
			$formatter->parse($_POST["cashier-amount"]),
			$_POST["cashier-fee"]
		);

		$this->notice("The withdrawal has been processed.");
		$url=get_permalink($electrumUser->getCurrency()->ID);
		wp_redirect($url,303);
		exit();
	}

	public function withdrawLightning($electrumUser) {
		$currency=$electrumUser->getCurrency();
		if (!$currency->getMeta("withdraw"))
			throw new \Exception("Can not be withdrawn");

		$invoice=$_POST["cashier-request"];
		$t=$electrumUser->createLightningWithdrawTransaction($invoice);
		$this->notice("The withdrawal is processing.","info");

		$url=get_permalink($electrumUser->getCurrency()->ID);
		HtmlUtil::redirectAndContinue($url,303);

		try {
			$electrumUser->processLightningWithdrawTransaction($invoice,$t);
		}

		catch (\Exception $e) {
			$t->fail($e->getMessage());
		}

		exit();
	}

	public function generateLightningInvoice($electrumUser) {
		$currency=$electrumUser->getCurrency();
		$user=$electrumUser->getUser();
		$formatter=$currency->getFormatterForUser($user->ID);
		$amount=$formatter->parse($_POST["amount"]);
		$electrumUser->generateLightningRequest($amount);

		wp_redirect(HtmlUtil::getCurrentUrl(),303);
		exit();
	}

	private function notice($message, $class="success") {
		CashierPlugin::instance()->
			getSessionNotices()->notice($message,$class);
	}

	public function getElectrumClient($currency) {
		if (!isset($currecy->electrumClient)) {
			$url=StringUtil::envVarSubst($currency->getMeta("electrumUrl"));
			$currency->electrumClient=new ElectrumClient($url);
		}

		return $currency->electrumClient;
	}

	private function getFeeRates($electrumUser) {
		$currency=$electrumUser->getCurrency();
		$user=$electrumUser->getUser();
		$electrum=$this->getElectrumClient($currency);
		$estimatedTransactionSize=200;

		$feeOptions=array(
			"0"=>"Slow",
			"0.5"=>"Medium",
			"1"=>"Fast"
		);

		$formatter=$currency->getFormatterForUser($user->ID);

		$res=array();
		foreach ($feeOptions as $rate=>&$option) {
			$rate=$electrum->call("getfeerate","ETA",$rate);
			$rate=$rate*$estimatedTransactionSize/1000;
			$rateLabel=$formatter->format($rate);
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

		$formatter=$currency->getFormatterForUser($user->ID);
		$account=$electrumUser->getAccount();
		$vars=array(
			"lightningQr"=>NULL,
			"amount"=>""
		);

		$qrT=new Template(__DIR__."/../tpl/qr.tpl.php");
		$vars["onchainQr"]=$qrT->render(array(
			"title"=>"Address",
			"data"=>$electrumUser->getAddress(),
			"url"=>"bitcoin:".$electrumUser->getAddress(),
			"class"=>""
		));

		$vars["currencySymbol"]=$formatter->getSymbol();

		$vars["methodOptions"]=array(
			"lightning"=>"Deposit Using Lightning Network (Recommended)",
			"onchain"=>"Deposit Using A Bitcoin Transaction",
		);

		$request=$electrumUser->getLightningRequest();
		if ($request) {
			$expires=$request["timestamp"]+$request["expiration"];

			if (time()<$expires) {
				$requestAmount=$request["amount_msat"]/1000;
				$vars["amount"]=$formatter->format($requestAmount,array(
					"includeSymbol"=>FALSE
				));
				$coverAmount=$formatter->format($requestAmount);

				$qrT=new Template(__DIR__."/../tpl/qr.tpl.php");
				$vars["lightningQr"]=$qrT->render(array(
					"title"=>"Invoice",
					"data"=>$request["invoice"],
					"url"=>"lightning:".$request["invoice"],
					"coverAmount"=>"+".$coverAmount,
					"class"=>"lightning"
				));

				$vars["lightningInvoice"]=$request["invoice"];
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

		$formatter=$currency->getFormatterForUser($user->ID);
		$vars["currencySymbol"]=$formatter->getSymbol();
		$vars["feeOptions"]=$this->getFeeRates($electrumUser);

		$vars["methodOptions"]=array(
			"lightning"=>"Withdraw Using Lightning Network (Recommended)",
			"onchain"=>"Withdraw Using A Bitcoin Transaction"
		);

		$t=new Template(__DIR__."/../tpl/electrum-withdraw-tab.tpl.php");
		return $t->render($vars);
	}

	public function tabs($currency) {
		$tabIds=array(
			"deposit"=>"Deposit",
		);

		if ($currency->getMeta("withdraw"))
			$tabIds["withdraw"]="Withdraw";

		return $tabIds;
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

	public function process($currency, $user) {
		$electrumUser=new ElectrumUser($currency,$user);
		$electrumUser->process();

		if (isset($_REQUEST["lightningInvoice"])) {
			$account=$electrumUser->getAccount();

			$txs=$account->getTransactions(array(
				"meta%"=>"%".$_REQUEST["lightningInvoice"]."%"
			));

			if (sizeof($txs)) {
				$tx=$txs[0];
				$t=new Template(__DIR__."/../tpl/qr-paid-cover.tpl.php");
				$formatter=$currency->getFormatterForUser($user->ID);
				$cover=$t->render(array(
					"amount"=>"+".$formatter->format($tx->getAmount())
				));

				$selector=".cashier-qr-cover.lightning";

				return array(
					$selector=>$cover
				);
			}
		}
	}
}