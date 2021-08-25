<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../utils/SessionNotices.php";
require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../model/Transaction.php";
require_once __DIR__."/../controller/CurrencyController.php";
require_once __DIR__."/../controller/PlaymoneyController.php";

class CashierPlugin extends Singleton {
	private $data;
	private $adapters;
	private $notices;

	protected function __construct() {
		CurrencyController::instance();
		/*ElectrumController::instance();*/
		PlaymoneyController::instance();

		$this->data=get_file_data(CASHIER_PATH."/wp-cashier.php",array(
			'Version'=>'Version',
			'TextDomain'=>'Text Domain'
		));

		add_action("wp_enqueue_scripts",array($this,"enqueue_scripts"));
		add_action("admin_enqueue_scripts",array($this,"enqueue_scripts"));
		add_filter("cashier_adapters",array($this,"cashier_adapters"),10,1);
	}

	public function activate() {
		Transaction::install();
	}

	public function enqueue_scripts() {
		wp_enqueue_script("cashier",
			CASHIER_URL."/res/cashier.js",
			array("jquery"),$this->data["Version"],true);

		wp_enqueue_style("cashier-style",
			CASHIER_URL."/res/cashier.css",
			array(),$this->data["Version"]);
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
			),
			"tabs"=>array(
				"deposit"=>"Deposit",
				"withdraw"=>"Withdraw",
			),
		);

		$adapters["playmoney"]=array(
			"title"=>"Playmoney",
			"config"=>array(
				array(
					"name"=>"replenish"
				),
			),
			"tabs"=>array(
				"topup"=>"Top Up",
			),
			"tab_cb"=>array(PlaymoneyController::instance(),"tab")
		);

		return $adapters;
	}

	public function getAdapters() {
		$default=array(
			"tabs"=>array()
		);

		if (!$this->adapters) {
			$this->adapters=apply_filters("cashier_adapters",array());

			foreach ($this->adapters as $k=>$adapter)
				$this->adapters[$k]=array_merge($default,$adapter);
		}

		return $this->adapters;
	}

	public function getSessionNotices() {
		if (!$this->notices)
			$this->notices=new SessionNotices("cashier_account_notices");

		return $this->notices;
	}
}
