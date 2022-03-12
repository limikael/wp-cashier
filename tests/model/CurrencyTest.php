<?php

require_once __DIR__."/../../inc/model/Currency.php";

use PHPUnit\Framework\TestCase;
use cashier\Currency;

class CurrencyTest extends TestCase {
	public function test_works() {
		$currency=Currency::create();
		$currency->setMeta("adapter","electrum");
		$currency->importRates();

//		print_r($currency->getMeta("rates"));

		$usd=$currency->convertAmountTo(100000000,"usd");
		$v=$currency->convertAmountFrom($usd,"usd");

		$this->assertEquals(100000000,$v);
	}
}
