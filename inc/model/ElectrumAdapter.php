<?php

namespace custodial;

class ElectrumAdapter extends CurrencyAdapter {
	const ID="electrum";
	const CONFIG=array(
		array(
			"name"=>"elecrumUrl"
		),
		array(
			"name"=>"confirmations",
			"type"=>"select",
			"options"=>array(0,1,2,3,4,5,6)
		)
	);
}
