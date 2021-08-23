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

		if (!isset($this->type))
			$this->type="text";
	}

	public function setUpstreamValue($value) {
		$this->upstreamValue=$value;
	}

	public function getUpstreamValue() {
		return $this->upstreamValue;
	}

	public function getCurrentValue() {
		if (array_key_exists($this->name,$_REQUEST))
			return HtmlUtil::getReqVar($this->name);

		return $this->upstreamValue;
	}

	public function display() {
		echo "<tr><th>".esc_html($this->name)."</th><td>";
		switch ($this->type) {
			case 'select':
				$options=HtmlUtil::renderSelectOptions($this->options,$this->getCurrentValue());
				echo self::renderTag("select",array(
					"name"=>$this->name,
				),$options);
				break;

			case 'text':
				echo self::renderTag("input",array(
					"type"=>"text",
					"name"=>$this->name,
					"value"=>$this->getCurrentValue()
				));
				break;
		}
		echo "</td></tr>";
	}

	private static function renderTag($tag, $attr, $content="") {
		$s="<$tag";
		foreach ($attr as $k=>$v)
			$s.=" ".$k.'="'.esc_attr($v).'"';
		$s.=">$content</$tag>";

		return $s;
	}
}