<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../utils/Template.php";
require_once __DIR__."/../utils/ElectrumClient.php";
require_once __DIR__."/ElectrumUser.php";

class ElectrumController extends Singleton {
	protected function __construct() {
		add_action("wp",array($this,"wp"));
	}

	public function wp() {
		$formHandlers=array(
			"cashier-generate-lightning-invoice"=>array($this,"generateLightningInvoice"),
			"cashier-withdraw-lightning"=>array($this,"withdrawLightning"),
			"cashier-withdraw-onchain"=>array($this,"withdrawOnchain"),
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
		$electrumUser->withdraw(
			$_POST["cashier-address"],
			$currency->parseInput($_POST["cashier-amount"]),
			$_POST["cashier-fee"]
		);

		$this->notice("The withdrawal has been processed.");
		$url=get_permalink($electrumUser->getCurrency()->ID);
		wp_redirect($url,303);
		exit();
	}

	public function withdrawLightning($electrumUser) {
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
		$amount=$currency->parseInput($_POST["amount"]);
		$electrumUser->generateLightningRequest($amount);

		wp_redirect(HtmlUtil::getCurrentUrl(),303);
		exit();
	}

	private function notice($message, $class="success") {
		CashierPlugin::instance()->
			getSessionNotices()->notice($message,$class);
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
				$coverAmount=$account->getCurrency()->format($requestAmount);

				$qrT=new Template(__DIR__."/../tpl/qr.tpl.php");
				$vars["lightningQr"]=$qrT->render(array(
					"title"=>"Invoice",
					"data"=>$request["invoice"],
					"url"=>"lightning:".$request["invoice"],
					"coverAmount"=>"+".$coverAmount,
					"class"=>"lightning"
				));
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
				$cover=$t->render(array(
					"amount"=>"+".$tx->formatAmount()
				));

				$selector=".cashier-qr-cover.lightning";

				return array(
					"replaceWith"=>array(
						$selector=>$cover
					)
				);
			}
		}
	}
}