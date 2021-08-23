<?php

namespace custodial;

require_once __DIR__."/../utils/HtmlUtil.php";

class InputField {
	private $upstreamValue;
	private $loadedPostId;

	public function __construct($props=array()) {
		foreach($props as $k=>$v)
			$this->$k=$v;

		if (!$this->name)
			throw new \Exception("Need an input field name");
	}

	public function loadPostMeta($postId) {
		$this->loadedPostId=$postId;
		$this->upstreamValue=get_post_meta($postId,$this->name,TRUE);
	}

	public function savePostMeta($postId) {
		if (!$this->loadedPostId!=$postId)
			$this->loadPostMeta($postId);

		update_post_meta($postId,$this->name,$this->getFormValue(),$this->upstreamValue);
	}

	private function getFormValue() {
		if (array_key_exists($this->name,$_REQUEST))
			return HtmlUtil::getReqVar($this->name);

		return $this->upstreamValue;
	}

	public function display() {
		echo "<tr><th>".esc_html($this->name)."</th><td>";
		switch ($this->type) {
			case 'select':
				echo '<select name="'.esc_attr($this->name).'">';
				HtmlUtil::displaySelectOptions($this->options,$this->getFormValue());
				echo "</select>";
				break;
		}
		echo "</td></tr>";
	}
}