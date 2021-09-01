<?php

namespace cashier;

class StringUtil {
	public static function plural($s) {
		if (substr($s,-1)=="y")
			return substr($s,0,-1)."ies";

		return $s."s";
	}

	public static function envVarSubst($spec) {
		if (substr($spec,0,1)=='$') {
			$var=substr($spec,1);
			return getenv($var);
		}

		return $spec;
	}
}