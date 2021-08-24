<?php

namespace cashier;

require_once __DIR__."/../utils/Singleton.php";

class PlaymoneyController extends Singleton {
	public function tab($currency, $tab) {
		return "top yp";
	}
}