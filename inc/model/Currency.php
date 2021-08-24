<?php

namespace cashier;

require_once __DIR__."/../utils/ExtensiblePost.php";

class Currency extends ExtensiblePost {
	public function getAdapter() {
		$adapters=CashierPlugin::instance()->getAdapters();

		return $adapters[$this->getMeta("adapter")];
	}
}
