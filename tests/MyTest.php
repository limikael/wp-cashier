<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class MyTest extends TestCase {

    protected function setUp():void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown():void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_bla() {
		$this->assertEquals("hello","hello");
    }
}