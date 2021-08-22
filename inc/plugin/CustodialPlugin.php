<?php

namespace custodial;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../controller/CurrencyController.php";

class CustodialPlugin extends Singleton {
	private $data;

	protected function __construct() {
		CurrencyController::instance();

		$this->data=get_file_data(CUSTODIAL_PATH."/wp-custodial.php",array(
			'Version'=>'Version',
			'TextDomain'=>'Text Domain'
		));
	}
}
