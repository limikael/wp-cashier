<?php

namespace cashier;

require_once __DIR__."/../utils/ExtensiblePost.php";

class Currency extends ExtensiblePost {
	public function getAdapter() {
		$adapters=CashierPlugin::instance()->getAdapters();

		return $adapters[$this->getMeta("adapter")];
	}

	public function getDecimals() {
		return intval($this->getMeta("decimals"));
	}

	public function format($amount, $style="standard") {
		$dividedAmount=$amount/pow(10,$this->getDecimals());

		switch ($style) {
			case "hyphenated":
				if (!$amount)
					return "-";

				return $this->format($amount,"standard");
				break;

			case "standard":
				$dividedAmount=sprintf("%.{$this->getDecimals()}f",$dividedAmount);
				$dividedAmount.=" ".$this->getMeta("symbol");
				break;

			case "number":
				break;

			case "string":
				return sprintf("%.{$this->getDecimals()}f",$dividedAmount);
				break;

			default:
				throw new \Exception("Unknown currency format style");
		}

		return $dividedAmount;
	}

	public function parseInput($input) {
		$amount=floatval($input)*pow(10,$this->getDecimals());

		return $amount;
	}

	public function process($user) {
		$adapter=$this->getAdapter();
		if (isset($adapter["process_cb"]) && $adapter["process_cb"])
			return $adapter["process_cb"]($this,$user);
	}

	public function importRates() {
		$adapter=$this->getAdapter();
		if (!isset($adapter["import_rates_cb"]))
			throw new \Exception("This currency does not support rates.");

		$adapter["import_rates_cb"]($this);
	}

	public function convertAmountTo($amount, $symbol) {
		$rateMeta=$this->getMeta("rates");
		if (!array_key_exists($symbol,$rateMeta))
			throw new \Exception("Unknown rate currency: ".$symbol);

		return $amount*$rateMeta[$symbol];
	}

	public function convertAmountFrom($amount, $symbol) {
		$rateMeta=$this->getMeta("rates");
		if (!array_key_exists($symbol,$rateMeta))
			throw new \Exception("Unknown rate currency: ".$symbol);

		return $amount/$rateMeta[$symbol];
	}

	public function hasSupport($feature) {
		switch ($feature) {
			case 'rates':
				$adapter=$this->getAdapter();
				return isset($adapter["import_rates_cb"]);
				break;
			
			default:
				throw new \Exception("Unknown currency feature: ".$feature);
				break;
		}
	}
}
