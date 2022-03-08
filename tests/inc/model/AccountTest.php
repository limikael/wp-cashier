<?php

require_once __DIR__."/../../../inc/model/Account.php";

use PHPUnit\Framework\TestCase;
use cashier\Account;

class AccountTest extends TestCase {
	public function test_works() {
		$currencyId=wp_insert_post(array(
			"post_type"=>"currency"
		));

		update_post_meta($currencyId,"adapter","playmoney");

		$account=Account::getUserAccount(1,$currencyId);
		$adapter=$account->getCurrency()->getAdapter();

		$this->assertEquals($adapter["title"],"Playmoney");
		$this->assertEquals($account->getBalance(),0);
	}
}
