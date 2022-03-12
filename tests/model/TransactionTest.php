<?php

require_once __DIR__."/../../inc/model/Transaction.php";
require_once __DIR__."/../../inc/model/Account.php";

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

	function assertThrows($msg, $fn) {
		$caught=NULL;
		try {
			$fn();
		}

		catch (\Exception $e) {
			$caught=$e;
		}

		$this->assertNotNull($caught);
		$this->assertStringContainsString($msg,$caught->getMessage());
	}

	public function test_changeReserved() {
		$currencyId=wp_insert_post(array(
			"post_type"=>"currency"
		));

		update_post_meta($currencyId,"adapter","playmoney");

		$account=Account::getUserAccount(1,$currencyId);
		$account->createDepositTransaction(100)->perform();
		$this->assertEquals($account->getBalance(),100);

		$destAccount=Account::getRakeAccount($currencyId);
		$destAccount->setBalance(0);

		$tx=$account->createSendTransaction($destAccount,50);

		$this->assertThrows("not reserved",function() use ($tx) {
			$tx->updateReservedAmount(123);
		});

		$tx->reserve();

		$this->assertThrows("smaller",function() use ($tx) {
			$tx->updateReservedAmount(25);
		});

		$this->assertThrows("funds",function() use ($tx) {
			$tx->updateReservedAmount(150);
		});

		$tx->updateReservedAmount(75);

		$this->assertEquals(25,$account->getBalance());
		$this->assertEquals(0,$destAccount->getBalance());

		$tx->perform();
		$this->assertEquals(25,$account->getBalance());
		$this->assertEquals(75,$destAccount->getBalance());
	}
}
