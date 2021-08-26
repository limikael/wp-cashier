<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../model/Account.php";
require_once __DIR__."/../model/Currency.php";

class CashierApi extends Singleton {
	public function getUserAccount($userId, $currencyId) {
		return Account::getUserAccount($userId,$currencyId);
	}

	public function getCurrency($currencyId) {
		return Currency::findOne($currencyId);
	}
}