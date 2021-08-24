<?php

namespace cashier;

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

	public function createInputFieldCollection() {
		$collection=new InputFieldCollection();

		$adapterOptions=array();
		foreach (CashierPlugin::instance()->getAdapters() as $id=>$adapter)
			$adapterOptions[$id]=$adapter["title"];

		$adapterSelect=$collection->createField(array(
			"name"=>"adapter",
			"type"=>"select",
			"options"=>$adapterOptions
		));

		foreach (CashierPlugin::instance()->getAdapters() as $id=>$adapter) {
			foreach ($adapter["config"] as $fieldConfig) {
				$field=new InputField($fieldConfig);
				$field->setCondition(array(
					"adapter"=>$id
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

	public function renderContent($currency) {
		$adapter=$currency->getAdapter();
		$link=get_permalink($currency->ID);

		$tabs=array(
			"activity"=>array(
				"title"=>"Activity",
				"link"=>$link,
			)
		);

		foreach ($adapter["tabs"] as $tabId=>$tabName) {
			$tabs[$tabId]=array(
				"title"=>$tabName,
				"link"=>add_query_arg(array(
					"tab"=>$tabId
				),$link)
			);
		}

		$currentTab="activity";
		if (isset($_REQUEST["tab"]))
			$currentTab=$_REQUEST["tab"];

		$vars=array(
			"tabs"=>$tabs,
			"balanceText"=>"123",
			"reservedText"=>"456",
			"currentTab"=>$currentTab
		);

		$t=new Template(__DIR__."/../tpl/currency-header.tpl.php");
		$content=$t->render($vars);

		if ($currentTab=="activity")
			$content.=$this->renderActivityTab($currency);

		else
			$content.=$adapter["tab_cb"]($currency,$currentTab);

		return $content;
	}

	public function renderActivityTab($currency) {
		return "activity..";
	}
}