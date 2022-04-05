<?php

namespace cashier;

class CurrencyFormatter {
	public function __construct($conf) {
		$this->value=1;
		$this->decimals=0;
		$this->symbol="PLY";

		if (isset($conf["value"]))
			$this->value=$conf["value"];

		if (isset($conf["decimals"]))
			$this->decimals=$conf["decimals"];

		if (isset($conf["symbol"]))
			$this->symbol=$conf["symbol"];
	}

	public function getSymbol() {
		return $this->symbol;
	}

	public function format($amount, $options=array()) {
		if (!isset($options["includeSymbol"]))
			$options["includeSymbol"]=TRUE;

		if (!isset($options["hyphenZero"]))
			$options["hyphenZero"]=FALSE;

		if (!$amount && $options["hyphenZero"])
			return "-";

		$rateAmount=$amount*$this->value;
		$decimals=$this->decimals;
		$formattedAmount=sprintf("%.{$decimals}f",$rateAmount);

		$string=$formattedAmount;
		if ($options["includeSymbol"])
			$string.=" ".$this->symbol;

		return $string;
	}

	public function parse($amount) {
		return floatval($amount)/$this->value;
	}
}
