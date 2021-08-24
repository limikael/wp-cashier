<?php

namespace custodial;

require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../utils/InputField.php";
require_once __DIR__."/../utils/InputFieldCollection.php";

class CurrencyController extends Singleton {
	protected function __construct() {
		Currency::registerPostType();
		Currency::addMetaBox("Settings",array($this,"settingsMetaBox"));
		Currency::registerContentHandler(array($this,"renderContent"));
		Currency::registerSaveHandler(array($this,"save"));
		Currency::removeRowActions(array("quick-edit"));
		Currency::useCleanSaveForm();
		Currency::setupMessages();
	}

	public function renderContent($currency) {
		return "hello this is contentasasd";
	}

	public function createInputFieldCollection() {
		$collection=new InputFieldCollection();

		$adapterOptions=array();
		foreach (Currency::getAvailableAdapters() as $adapter)
			$adapterOptions[$adapter]=$adapter::NAME;

		$adapterSelect=$collection->createField(array(
			"name"=>"adapter",
			"type"=>"select",
			"options"=>$adapterOptions
		));

		foreach (Currency::getAvailableAdapters() as $adapter) {
			foreach ($adapter::CONFIG as $fieldConfig) {
				$field=new InputField($fieldConfig);
				$field->setCondition(array(
					"adapter"=>$adapter
				));
				$collection->addField($field);
			}
		}

		return $collection;
	}

	public function settingsMetaBox($currency) {
		$fieldCollection=$this->createInputFieldCollection();
		$fieldCollection->loadPostMeta($currency->ID);
		$fieldCollection->display();
	}

	public function save($currency) {
		$fieldCollection=$this->createInputFieldCollection();
		$fieldCollection->savePostMeta($currency->ID);
	}
}