<?php

require_once __DIR__."/../../../inc/utils/Record.php";

use PHPUnit\Framework\TestCase;
use cashier\Record;

class TestRecord extends Record {
	static function initialize() {
		self::field("id","integer not null auto_increment");
		self::field("sometext","varchar(255) not null");
		self::field("otherint","varchar(255) not null");
	}
}

class RecordTest extends TestCase {
	public function test_works() {
		TestRecord::install();

		$t=new TestRecord();
		$t->sometext="hello";
		$t->otherint=5;
		$t->save();

		$t=new TestRecord();
		$t->sometext="hello";
		$t->otherint=6;
		$t->save();

		$u=TestRecord::findOne($t->id);
		$this->assertEquals("hello",$u->sometext);

		$a=TestRecord::findMany(array(
			"sometext"=>"hello"
		));
		$this->assertEquals("hello",$a[0]->sometext);
		$this->assertEquals("hello",$a[1]->sometext);
		$this->assertEquals(11,$a[0]->otherint+$a[1]->otherint);

		$t->delete();
		$a=TestRecord::findMany(array(
			"sometext"=>"hello"
		));

		$this->assertEquals(1,sizeof($a));
	}
}
