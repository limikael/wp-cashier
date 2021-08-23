<?php

namespace custodial;

require_once __DIR__."/../utils/ExtensiblePost.php";
require_once __DIR__."/CurrencyAdapter.php";
require_once __DIR__."/ElectrumAdapter.php";
require_once __DIR__."/PlaymoneyAdapter.php";

class Currency extends ExtensiblePost {
	public function getAdapter() {
		switch ($this->getMeta("adapter")) {
			case "playmoney":
				return new PlaymoneyAdapter($this);

			case "electrum":
				return new ElectrumAdapter($this);
		}
	}

	static function getAvailableAdapters() {
		return array(
			PlaymoneyAdapter::class,
			ElectrumAdapter::class
		);
	}
}
