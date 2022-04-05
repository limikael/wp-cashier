<?php

require_once __DIR__."/../../inc/model/Currency.php";

use PHPUnit\Framework\TestCase;
use cashier\Currency;

class CurrencyTest extends TestCase {
	public function test_works() {
		$currency=Currency::create();
		$currency->setMeta("adapter","electrum");

		$currency->setMeta("rates",array(
			"btc"=>array(
				"name" => "Bitcoin",
				"value" => 1.0E-8,
				"decimals" => 8,
				"symbol" => "BTC",
			),
			"sats"=>array(
				"name" => "Satoshi",
				"value" => 1,
				"decimals" => 0,
				"symbol" => "SATS",
			),
			"usd"=>array(
				"value" => 0.00047351683,
				"name" => "US Dollar",
				"decimals" => 2,
				"symbol" => "USD",
			)
		));

		$user=get_user_by("id",1);
		$currency->setUserMeta($user->ID,"displayCurrency","usd");

		$f=$currency->getFormatterForUser($user->ID);
		$this->assertEquals("47351.68 USD",$f->format(100000000));

		$currency->setUserMeta($user->ID,"displayCurrency","btc");
		$f=$currency->getFormatterForUser($user->ID);
		$this->assertEquals(100000000,$f->parse("1"));
	}
}
