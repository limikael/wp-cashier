<?php

namespace cashier;

require_once __DIR__."/../utils/ExtensiblePost.php";

class Currency extends ExtensiblePost {
	public function getAdapter() {
		$adapters=CashierPlugin::instance()->getAdapters();

		return $adapters[$this->getMeta("adapter")];
	}

	public function getDivisorPlaces() {
		return intval($this->getMeta("divisorPlaces"));
	}

	public function format($amount, $style="standard") {
		$dividedAmount=$amount/pow(10,$this->getDivisorPlaces());

		switch ($style) {
			case "hyphenated":
				if (!$amount)
					return "-";

				return $this->format($amount,"standard");
				break;

			case "standard":
				$dividedAmount=Currency::toString($dividedAmount);
				$dividedAmount.=" ".$this->getMeta("symbol");
				break;

			case "number":
				break;

			case "string":
				return Currency::toString($dividedAmount);
				break;

			default:
				throw new \Exception("Unknown currency format style");
		}

		return $dividedAmount;
	}

	public function parseInput($input) {
		$amount=floatval($input)*pow(10,$this->getDivisorPlaces());

		return $amount;
	}

	private static function toString($number) {
		$s=sprintf("%.10f",$number);
		$s=rtrim($s,"0");
		$s=rtrim($s,".");
		return $s;
	}
}
