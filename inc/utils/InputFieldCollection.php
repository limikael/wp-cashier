<?php

namespace custodial;

class InputFieldCollection {
	private $fields;
	private $loadedPostId;

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

		foreach ($this->fields as $field)
			echo $field->renderTr();

		echo "</table>";
	}

	public function loadPostMeta($postId) {
		foreach ($this->fields as &$field) {
			$v=get_post_meta($postId,$field->name,TRUE);
			$field->setValue($v);
		}
	}

	public function savePostMeta($postId) {
		foreach ($this->fields as &$field) {
			$field->useFormValue($_REQUEST);
			update_post_meta($postId,$field->name,$field->getValue());
		}
	}
}