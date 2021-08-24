<?php

namespace cashier;

class StringUtil {
	public static function plural($s) {
		if (str_ends_with($s,"y"))
			return substr($s,0,-1)."ies";

		return $s."s";
	}
}