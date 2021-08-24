<?php

namespace custodial;

require_once __DIR__."/../utils/HtmlUtil.php";

class InputField {
	private $value;
	private $condition;
	private $label;

	public function __construct($props=array()) {
		foreach($props as $k=>$v)
			$this->$k=$v;

		if (!$this->name)
			throw new \Exception("Need an input field name");

		if (!isset($this->type))
			$this->type="text";
	}

	public function setCondition($condition) {
		$this->condition=$condition;
	}

	public function setValue($value) {
		$this->value=$value;
	}

	public function getValue() {
		return $this->value;
	}

	public function useFormValue($formData) {
		$this->setValue($formData[$this->name]);
	}

	public function render() {
		switch ($this->type) {
			case 'select':
				$options=HtmlUtil::renderSelectOptions($this->options,$this->getValue());
				return HtmlUtil::renderTag("select",array(
					"name"=>$this->name,
				),$options);
				break;

			case 'text':
				return HtmlUtil::renderTag("input",array(
					"class"=>"regular-text",
					"type"=>"text",
					"name"=>$this->name,
					"value"=>$this->getValue()
				));
				break;

			default:
				throw new \Exception("Unknown type: ".$this->type);
				break;
		}
	}

	public function getLabel() {
		if ($this->label)
			return $this->label;

		return ucfirst($this->name);
	}

	private function querifyCondition() {
		$c=array();

		foreach ($this->condition as $k=>$v)
			$c["[name=".$k."]"]=$v;

		return $c;
	}

	public function renderTr() {
		$content=
			"<th>".esc_html($this->getLabel())."</th>".
			"<td>".$this->render()."</td>";

		$attr=array();

		if ($this->condition) {
			$attr["data-condition"]=json_encode($this->querifyCondition());
			$attr["style"]="display: none";
		}

		return HtmlUtil::renderTag("tr",$attr,$content);
	}
}