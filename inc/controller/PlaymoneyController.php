<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";

class PlaymoneyController extends Singleton {
	public function __construct() {
		add_action("wp",array($this,"wp"));
		add_filter("cashier_adapters",array($this,"cashier_adapters"),10,1);
	}

	public function cashier_adapters($adapters) {
		$adapters["playmoney"]=array(
			"title"=>"Playmoney",
			"config"=>array(
				array(
					"name"=>"replenish"
				),
			),
			"tabs"=>array(
				"deposit"=>"Top Up",
			),
			"tab_cb"=>array($this,"tab"),
			"install_cb"=>array($this,"install"),
			"denominations"=>array(
				"ply"=>array(
					"symbol"=>"PLY",
					"name"=>"Playmoney"
				),
				"kply"=>array(
					"symbol"=>"KPLY",
					"decimals"=>3,
					"value"=>.001,
					"name"=>"Thousands of Playmoney"
				)
			)
		);

		return $adapters;
	}

	public function install($currency) {
		error_log("installing playmoney");
	}

	public function wp() {
		if (array_key_exists("do_ply_topup",$_POST)) {
			$currency=Currency::getCurrent();
			if ($currency->getMeta("adapter")!="playmoney")
				throw new \Exception("Not playmoney");

			$user=wp_get_current_user();
			$account=Account::getUserAccount($user->ID,$currency->ID);
			$reservedAmount=$account->getReserved();
			$replenish=intval($currency->getMeta("replenish"));
			//$topupAmount=$replenish-$account->getBalance()-$reservedAmount;
			$topupAmount=1000;

			if ($topupAmount>0) {
				$t=$account->createDepositTransaction($topupAmount);
				$t->notice="Top up";
				$t->reserve();

				$notices=CashierPlugin::instance()->getSessionNotices();
				$notices->notice("Your ply has been topped up!");
				$url=add_query_arg(array(
					"tab"=>NULL,
				),HtmlUtil::getCurrentUrl());

				/*HtmlUtil::redirectAndContinue($url,303);
				sleep(10);
				$t->perform();*/

				$t->perform();
				wp_redirect(HtmlUtil::getCurrentUrl(),303);
				exit();
			}

			else {
				$notices=CashierPlugin::instance()->getSessionNotices();
				$notices->notice("Your ply account is already full!","error");
				wp_redirect(HtmlUtil::getCurrentUrl(),303);
			}

			exit();
		}
	}

	public function tab($tab, $currency, $user) {
		$formatter=$currency->getFormatterForUser($user->ID);

		$vars=array(
			"replenishText"=>$formatter->format($currency->getMeta("replenish"))
		);

		$t=new Template(__DIR__."/../tpl/playmoney-topup.tpl.php");
		return $t->render($vars);
	}
}