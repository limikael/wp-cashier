<?php

require_once __DIR__."/../../../inc/utils/StringUtil.php";

use PHPUnit\Framework\TestCase;
use cashier\StringUtil;

/**
 * Sample test case.
 */
class StringUtilTest extends TestCase {
	public function test_works() {
		$this->assertEquals("hello",StringUtil::envVarSubst("hello"));
		$this->assertEquals("/bin/bash",StringUtil::envVarSubst('$SHELL'));
	}
}
