<?php

require_once __DIR__."/../../inc/model/CurrencyFormatter.php";

use PHPUnit\Framework\TestCase;
use cashier\CurrencyFormatter;

class CurrencyFormatterTest extends TestCase {
	public function test_works() {
		$rates=array(
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
		);

		$formatter=new CurrencyFormatter($rates["usd"]);

		$this->assertEquals("47351.68 USD",$formatter->format(100000000));
	}
}
