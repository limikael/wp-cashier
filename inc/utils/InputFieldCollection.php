<?php

namespace custodial;

class InputFieldCollection {
	private $fields;

	public function __construct() {
		$this->fields=[];
	}

	public function addField($field) {
		$this->fields[]=$field;
	}

	public function createField($conf) {
		$this->addField(new InputField($conf));
	}

	public function display() {
		echo "<table class='form-table'>";

		foreach ($this->fields as $field) {
			$field->display();
		}

		echo "</table>";
	}

	public function loadPostMeta($postId) {
		foreach ($this->fields as $field)
			$field->loadPostMeta($postId);
	}

	public function savePostMeta($postId) {
		foreach ($this->fields as $field)
			$field->savePostMeta($postId);
	}
}