<?php

namespace custodial;

require_once __DIR__."/../model/Currency.php";

class CurrencyController extends Singleton {
	protected function __construct() {
		Currency::registerPostType();
		Currency::addMetaBox("Settings",array($this,"settingsMeta"));
		Currency::registerContentHandler(array($this,"renderContent"));
		Currency::useCleanSaveForm();
		Currency::removeRowActions(array("quick-edit"));
	}

	public function renderContent($currency) {
		return "hello this is contentasasd";
	}

	public function settingsMeta() {
		echo "hello";
	}
}