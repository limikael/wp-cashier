<?php

namespace custodial;

require_once __DIR__."/../utils/ExtensiblePost.php";

class Currency extends ExtensiblePost {
	public function getAdapter() {
		switch ($this->getMeta("adapter")) {
			case "playmoney":
				return new PlaymoneyAdapter($this);

			case "electrum":
				return new ElectrumAdapter($this);
		}
	}
}
