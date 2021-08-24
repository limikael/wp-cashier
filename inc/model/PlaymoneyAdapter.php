<?php

namespace cashier;

class PlaymoneyAdapter extends CurrencyAdapter {
	const NAME="Playmoney";
	const CONFIG=array(
		array(
			"name"=>"replenish"
		)
	);
}
