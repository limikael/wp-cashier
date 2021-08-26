<?php

namespace cashier;

require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../model/Account.php";
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

		$collection->createField(array(
			"name"=>"symbol"
		));

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
		$user=wp_get_current_user();
		$currency->process($user);

		$account=Account::getUserAccount($user->ID,$currency->ID);

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

		$reservedAmount=$account->getReserved();

		$vars=array(
			"tabs"=>$tabs,
			"balanceText"=>$account->formatBalance(),
			"reservedText"=>$currency->format($reservedAmount,"hyphenated"),
			"currentTab"=>$currentTab,
			"notices"=>CashierPlugin::instance()->getSessionNotices()->renderNotices(),
			"currencyId"=>$currency->ID
		);

		$t=new Template(__DIR__."/../tpl/currency-header.tpl.php");
		$content=$t->render($vars);

		if ($currentTab=="activity")
			$content.=$this->renderActivityTab($user,$currency);

		else
			$content.=$adapter["tab_cb"]($currentTab,$currency,$user);

		return $content;
	}

	public function renderActivityTab($user, $currency) {
		$account=Account::getUserAccount($user->ID,$currency->ID);

		$transactions=$account->getTransactions(array(
			"status!"=>"ignored",
			"order by stamp desc"
		));

		$transactionViews=array();
		foreach ($transactions as $transaction) {
			$other=$transaction->getOtherAccount($account);

			$class="";
			$iconClass="";
			switch ($transaction->getStatus()) {
				case "reserved":
					$class="table-warning";
					$iconClass="bi-hourglass-top";
					break;

				case "failed":
					$class="table-danger";
					$iconClass="bi-x-circle";
					break;
			}

			$transactionView=array(
				"stamp"=>$transaction->formatSiteTime(),
				"amount"=>$transaction->formatRelativeAmount($account),
				"entity"=>"-",
				"notice"=>$transaction->notice,
				"class"=>$class,
				"iconClass"=>$iconClass,
				"id"=>$transaction->id
			);

			$meta=array();
			$meta["Time"]=$transactionView["stamp"];
			$meta["Amount"]=$transactionView["amount"];

			if ($other) {
				$transactionView["entity"]=$other->getDisplay();
				$meta["To/From"]=$other->getDisplay();
			}

			$meta["Notice"]=$transactionView["notice"];

			foreach ($transaction->getMetas() as $key=>$value)
				$meta[ucfirst($key)]=$value;

			$transactionView["meta"]=$meta;
			$transactionViews[]=$transactionView;
		}

		$vars=array(
			"transactions"=>$transactionViews
		);

		$t=new Template(__DIR__."/../tpl/activity.tpl.php");
		return $t->render($vars);
	}
}