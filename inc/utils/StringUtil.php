<?php

namespace cashier;

class StringUtil {
	public static function plural($s) {
		if (substr($s,-1)=="y")
			return substr($s,0,-1)."ies";

		return $s."s";
	}
}