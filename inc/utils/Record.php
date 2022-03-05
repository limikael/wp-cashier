<?php

namespace cashier;

require_once __DIR__."/Qbe.php";

class Record {
	private static $classes=array();

	/**
	 * Get full table name.
	 */
	public static function tableName() {
		self::init();

		return self::$classes[get_called_class()]["table"];
	}

	/**
	 * Add field.
	 */
	protected static final function field($name, $definition) {
		if (!isset(self::$classes[get_called_class()]["primaryKey"]))
			self::$classes[get_called_class()]["primaryKey"]=$name;

		self::$classes[get_called_class()]["fields"][$name]=$definition;
	}

	/**
	 * Init.
	 */
	private static function init() {
		global $wpdb;

		$class=get_called_class();

		if (isset(self::$classes[$class]))
			return;

		self::$classes[$class]=array("fields"=>array());

		$s=strtolower(str_replace("\\","_",$class));
		self::$classes[$class]["table"]=$wpdb->prefix.$s;

		static::initialize();
	}

	/**
	 * Create underlying table.
	 */
	public final static function install() {
		global $wpdb;

		self::init();

		$table=self::$classes[get_called_class()]["table"];
		$fields=self::$classes[get_called_class()]["fields"];
		$primaryKey=self::$classes[get_called_class()]["primaryKey"];

		// Create table if it doesn't exist.
		$qs="CREATE TABLE IF NOT EXISTS ".$table." (";

		foreach ($fields as $name=>$declaration)
			$qs.=$name." ".$declaration.", ";

		$qs.="primary key(".$primaryKey."))";

		self::query($qs);

		// Check current state of database.
		$describeResult=self::query("DESCRIBE ".$table);

		$existing=array();
		foreach ($describeResult as $describeRow)
			$existing[]=$describeRow["Field"];

		// Create or modify existing fields.
		foreach ($fields as $name=>$declaration) {
			if (in_array($name,$existing)) {
				$q="ALTER TABLE `$table` MODIFY $name $declaration";
			}

			else {
				$q="ALTER TABLE `$table` ADD `$name` $declaration";
			}

			self::query($q);
		}

		// Drup unused fields.
		$currentFieldNames=array_keys($fields);
		foreach ($existing as $existingField) {
			if (!in_array($existingField, $currentFieldNames)) {
				self::query("ALTER TABLE $table DROP $existingField");
			}
		}
	}

	/**
	 * Drop table if it exists.
	 */
	public final static function uninstall() {
		global $wpdb;

		self::init();

		$table=self::$classes[get_called_class()]["table"];
		$wpdb->query("DROP TABLE IF EXISTS $table");
	}

	/**
	 * Get value for primary key.
	 */
	private function getPrimaryKeyValue() {
		$conf=self::$classes[get_called_class()];
		$pk=$conf["primaryKey"];

		if (!isset($this->$pk))
			return NULL;

		return $this->$pk;
	}

	/**
	 * Get conf.
	 */
	private static function getConf() {
		self::init();
		return self::$classes[get_called_class()];
	}

	/**
	 * Save.
	 */
	public function save() {
		$conf=self::getConf();

		$pk=$this->getPrimaryKeyValue();
		$s="";

		if ($pk)
			$s.="UPDATE $conf[table] SET ";

		else
			$s.="INSERT INTO $conf[table] SET ";

		$params=array();

		$first=TRUE;
		foreach ($conf["fields"] as $field=>$declaration)
			if ($field!=$conf["primaryKey"]) {
				if (!$first)
					$s.=", ";

				$s.="$field=%s";
				$first=FALSE;

				if (isset($this->$field))
					$params[]=$this->$field;

				else
					$params[]=NULL;
			}

		if ($pk) {
			$s.=" WHERE $conf[primaryKey]=%s";
			$params[]=$this->getPrimaryKeyValue();
		}

		$statement=self::query($s,...$params);

		if (!$this->getPrimaryKeyValue()) {
			$primaryKeyField=$conf["primaryKey"];
			$this->$primaryKeyField=self::lastInsertId();
		}
	}

	/**
	 * Delete this item.
	 */
	public final function delete() {
		self::init();
		$conf=self::$classes[get_called_class()];

		if (!$this->getPrimaryKeyValue())
			throw new \Exception("Can't delete, there is no id");

		self::query(
			"DELETE FROM :table WHERE $conf[primaryKey]=%s",
			$this->getPrimaryKeyValue()
		);

		$primaryKeyField=$conf["primaryKey"];
		unset($this->$primaryKeyField);
	}

	/**
	 *
	 */
	public static final function getCount($params=array()) {
		$qbe=new Qbe($params);
		$q="SELECT COUNT(*) AS count FROM :table WHERE ".$qbe->getClause();
		$queryRows=self::query($q,...$qbe->getParams());

		return $queryRows[0]["count"];
	}

	/**
	 * Find all by value.
	 */
	public static final function findMany($params=array()) {
		$conf=self::getConf();

		if (!is_array($params))
			$params=array(
				$conf["primaryKey"]=>$params
			);

		$qbe=new Qbe($params);
		$q="SELECT * FROM :table WHERE ".$qbe->getClause();

		$queryRows=self::query($q,...$qbe->getParams());
		$res=array();

		$class=get_called_class();
		$fields=self::$classes[get_called_class()]["fields"];
		foreach ($queryRows as $queryRow) {
			$o=new $class;

			foreach ($fields as $field=>$declaration)
				$o->$field=$queryRow[$field];

			$res[]=$o;
		}

		return $res;
	}

	/**
	 * Find one by id.
	 */
	public static final function findOne($p=array()) {
		$all=self::findMany($p);
		if (!sizeof($all))
			return NULL;

		return $all[0];
	}

	/**
	 * Run query with parameters.
	 * The parameters are varadic!
	 */
	private static function query($q,...$params) {
		global $wpdb;

		$q=str_replace(":table",self::tableName(),$q);
		$q=str_replace("%table",self::tableName(),$q);
		$q=str_replace("%t",self::tableName(),$q);

		if (sizeof($params))
			$q=$wpdb->prepare($q,...$params);

		$res=$wpdb->get_results($q,ARRAY_A);
		if ($wpdb->last_error)
			throw new \Exception($wpdb->last_error);

		if ($res===NULL)
			throw new \Exception("Unknown error");

		return $res;
	}

	/**
	 * Run query with parameters.
	 */
	private static function lastInsertId() {
		global $wpdb;

		return $wpdb->insert_id;
	}
}
