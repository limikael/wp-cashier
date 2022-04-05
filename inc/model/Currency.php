<?php

namespace cashier;

require_once __DIR__."/../utils/ExtensiblePost.php";
require_once __DIR__."/CurrencyFormatter.php";

class Currency extends ExtensiblePost {
	public function getAdapter() {
		$adapters=CashierPlugin::instance()->getAdapters();

		return $adapters[$this->getMeta("adapter")];
	}

	public function process($user) {
		$adapter=$this->getAdapter();
		if (isset($adapter["process_cb"]) && $adapter["process_cb"])
			return $adapter["process_cb"]($this,$user);
	}

	public function install() {
		$adapter=$this->getAdapter();
		if (isset($adapter["install_cb"]) && $adapter["install_cb"])
			return $adapter["install_cb"]($this);
	}

	public function getUserMeta($uid, $key) {
		if (!$uid)
			return;

		$key="cashier_".$this->ID."_".$key;
		return get_user_meta($uid,$key,TRUE);
	}

	public function setUserMeta($uid, $key, $value) {
		if (!$uid)
			return;

		$key="cashier_".$this->ID."_".$key;

		if ($value===NULL)
			delete_user_meta($uid,$key);

		else
			update_user_meta($uid,$key,$value);
	}

	public function getDenominations() {
		$adapter=$this->getAdapter();
		if (isset($adapter["denominations"]))
			return $adapter["denominations"];

		if (isset($adapter["denominations_cb"]))
			return $adapter["denominations_cb"]($this);

		$denomination=array(
			"symbol"=>strtoupper(substr($this->getMeta("adapter"),0,3))
		);

		if (isset($adapter["denomination"]))
			$denomination=$adapter["denomination"];

		return array(
			"default"=>$denomination
		);
	}

	public function getFormatter($denominationKey=NULL) {
		$denominations=$this->getDenominations();

		if (!$denominationKey)
			$denominationKey=array_keys($denominations)[0];

		return new CurrencyFormatter($denominations[$denominationKey]);
	}

	public function getFormatterForUser($uid) {
		if (!$uid)
			throw new \Exception("That's not a uid");

		return $this->getFormatter($this->getUserMeta($uid,"denomination"));
	}
}
