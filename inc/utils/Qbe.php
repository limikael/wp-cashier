<?php

namespace cashier;

class Qbe {
	private $params;
	private $clause;

	public function __construct($query) {
		$this->params=array();
		$this->clause=$this->processAnd($query);
	}

	private function processAnd($args) {
		if (!is_array($args))
			throw new \Exception("Need an array for and clause");

		$qa=[];
		$ea=[];
		foreach ($args as $key=>$value) {
			if (is_array($value)) {
				$qa[]=$this->processOr($value);
			}

			else if (is_numeric($key)) {
				$ea[]=$value;
			}

			else {
				$op="=";
				switch (substr($key,-1)) {
					case "<":
					case ">":
						$op=substr($key,-1);
						break;

					case "!":
						$op="<>";
						break;

					case "%":
						$op="LIKE";
						break;
				}

				$key=rtrim($key,"!<>%");

				$qa[]="$key $op %s";
				$this->params[]=$value;
			}
		}

		return "(".join(" AND ",$qa).") ".join(" ",$ea);
	}

	private function processOr($args) {
		if (!is_array($args))
			throw new \Exception("Need an array for or clause");

		$qa=[];
		foreach ($args as $arg) {
			$qa[]=$this->processAnd($arg);
		}

		return "(".join(" OR ",$qa).")";
	}

	public function getClause() {
		return $this->clause;
	}

	public function getParams() {
		return $this->params;
	}
}