<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../model/Account.php";
require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../model/Transaction.php";

class CashierApi extends Singleton {
	public function getRakeAccount($currencyId) {
		return Account::getRakeAccount($currencyId);
	}

	public function getUserAccount($userId, $currencyId) {
		return Account::getUserAccount($userId,$currencyId);
	}

	public function getTransactionByMeta($key, $value) {
		return Transaction::findOneByMeta($key,$value);
	}

	public function getCurrency($p) {
		return Currency::findOne($p);
	}

	public function getActiveCurrencies() {
		return Currency::findMany();
	}

	public function getRevServer() {
		return CashierPlugin::instance()->getRevServer();
	}
}