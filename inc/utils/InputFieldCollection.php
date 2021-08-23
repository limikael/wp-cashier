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

		foreach ($this->fields as $field) {
			$field->display();
		}

		echo "</table>";
	}

	public function loadPostMeta($postId) {
		$this->loadedPostId=$postId;
		foreach ($this->fields as &$field) {
			$v=get_post_meta($postId,$field->name,TRUE);
			$field->setUpstreamValue($v);
		}
	}

	public function savePostMeta($postId) {
		if ($postId!=$this->loadedPostId)
			$this->loadPostMeta($postId);

		foreach ($this->fields as &$field) {
			$value=$field->getCurrentValue();
			$upstream=$field->getUpstreamValue();
			update_post_meta($postId,$field->name,$value,$upstream);
		}
	}
}