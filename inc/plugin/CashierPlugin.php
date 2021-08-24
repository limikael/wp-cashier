<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../controller/CurrencyController.php";

class CashierPlugin extends Singleton {
	private $data;

	protected function __construct() {
		CurrencyController::instance();

		$this->data=get_file_data(CASHIER_PATH."/wp-cashier.php",array(
			'Version'=>'Version',
			'TextDomain'=>'Text Domain'
		));

		wp_enqueue_script("cashier",
			CASHIER_URL."/res/cashier.js",
			array("jquery"),$this->data["Version"],true);
	}
}
