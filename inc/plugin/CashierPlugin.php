<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../controller/CurrencyController.php";

class CashierPlugin extends Singleton {
	private $data;
	private $adapters;

	protected function __construct() {
		CurrencyController::instance();
		/*ElectrumController::instance();
		PlaymoneyController::instance();*/

		$this->data=get_file_data(CASHIER_PATH."/wp-cashier.php",array(
			'Version'=>'Version',
			'TextDomain'=>'Text Domain'
		));

		wp_enqueue_script("cashier",
			CASHIER_URL."/res/cashier.js",
			array("jquery"),$this->data["Version"],true);

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
			),
		);

		$adapters["playmoney"]=array(
			"title"=>"Playmoney",
			"config"=>array(
				array(
					"name"=>"replenish"
				),
			),
		);

		return $adapters;
	}

	public function getAdapters() {
		if (!$this->adapters)
			$this->adapters=apply_filters("cashier_adapters",array());

		return $this->adapters;
	}
}
