<?php

namespace cashier;

require_once __DIR__."/../utils/AjaxHandler.php";
require_once __DIR__."/../controller/CurrencyController.php";

class AjaxController extends AjaxHandler {
	protected function __construct() {
		parent::__construct("cashier-frontend");
	}

	public function getCurrencyTexts($p) {
		$user=wp_get_current_user();
		if (!$user || !$user->id)
			throw new \Exception("Not logged in");

		$account=Account::getUserAccount($user->id,$p["currency"]);
		$currency=$account->getCurrency();
		$response=$currency->process($user);//,$p);

		if (!$response)
			$response=array();

		if (!isset($response["text"]))
			$response["text"]=array();

		if (!isset($response["replaceWith"]))
			$response["replaceWith"]=array();

		/*$reservedAmount=MoneyGame::getTotalBalancesForUser(
			$currency->getId(),
			$user->user_login
		);*/

		$reservedAmount=$account->getReserved();

		$response["text"]["#cashier-account-balance"]=
			$account->formatBalance();

		$response["text"]["#cashier-account-reserved"]=
			$currency->format($reservedAmount,"hyphenated");

		if (isset($_REQUEST["renderTransactionList"]))
			$response["replaceWith"]["#cashier-transaction-list"]=
				CurrencyController::instance()->renderActivityTab($user, $currency);

		$response["balance"]=$account->getBalance();

		return $response;
	}
}