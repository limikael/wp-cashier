<?php

namespace custodial;

require_once __DIR__."/../utils/ExtensiblePost.php";
require_once __DIR__."/CurrencyAdapter.php";
require_once __DIR__."/ElectrumAdapter.php";
require_once __DIR__."/PlaymoneyAdapter.php";

class Currency extends ExtensiblePost {
	private $adapter;

	public function getAdapter() {
		if (!$this->adapter) {
			$class=$this->getMeta("adapter");
			$this->adapter=new $class($this);
		}

		return $this->adapter;
	}

	static function getAvailableAdapters() {
		return array(
			PlaymoneyAdapter::class,
			ElectrumAdapter::class
		);
	}
}
