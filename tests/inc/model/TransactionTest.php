<?php

require_once __DIR__."/../../../inc/model/Transaction.php";
require_once __DIR__."/../../../inc/model/Account.php";

use PHPUnit\Framework\TestCase;
use cashier\Account;
use cashier\Transaction;

class TransactionTest extends TestCase {
	public function test_works() {
		$currencyId=wp_insert_post(array(
			"post_type"=>"currency"
		));

		update_post_meta($currencyId,"adapter","playmoney");

		$account=Account::getUserAccount(1,$currencyId);
		$adapter=$account->getCurrency()->getAdapter();

		$this->assertEquals($adapter["title"],"Playmoney");
		$this->assertEquals($account->getBalance(),0);

		$tx=$account->createDepositTransaction(100);
		$tx->perform();

		$this->assertEquals($account->getBalance(),100);
	}
}
