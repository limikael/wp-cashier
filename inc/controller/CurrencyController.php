<?php

namespace cashier;

require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../model/Account.php";
require_once __DIR__."/../utils/InputField.php";
require_once __DIR__."/../utils/InputFieldCollection.php";

class CurrencyController extends Singleton {
	protected function __construct() {
		add_action("wp",array($this,"wp"));

		Currency::registerPostType();
		Currency::addMetaBox("Settings",array($this,"settingsMetaBox"));
		Currency::registerContentHandler(array($this,"renderContent"));
		Currency::registerSaveHandler(array($this,"save"));
		Currency::removeRowActions(array("quick-edit"));
		Currency::useCleanSaveForm();
		Currency::setupMessages();

		add_filter("sse_init",array($this,"sse_init"));
		add_filter("sse_data",array($this,"sse_data"),10,2);
		add_filter("sse_ping",array($this,"sse_ping"));
	}

	public function wp() {
		if (isset($_REQUEST["save-currency-settings"])) {
			$currency=Currency::getCurrent();
			$user=wp_get_current_user();

			if (!$user->ID || !$currency->ID)
				throw new \Exception("Bad args");

			$currency->setUserMeta($user->ID,"denomination",$_REQUEST["denomination"]);
			$notices=CashierPlugin::instance()->getSessionNotices();
			$notices->notice("Settings saved.");
			wp_redirect(HtmlUtil::getCurrentUrl(),303);
			exit();
		}
	}

	public function sse_ping($data) {
		if (isset($_REQUEST["currency"])) {
			$user=wp_get_current_user();
			$account=Account::getUserAccount($user->id,$_REQUEST["currency"]);
			$currency=$account->getCurrency();
			return $currency->process($user);
		}
	}

	public function sse_init($channels) {
		if (isset($_REQUEST["currency"])) {
			$uid=get_current_user_id();
			if ($uid) {
				$account=Account::getUserAccount($uid,$_REQUEST["currency"]);
				$channels[]=$account->getEventChannel();
			}
		}

		return $channels;
	}

	public function sse_data($data, $key) {
		if (!is_user_logged_in())
			return $data;

		$user=wp_get_current_user();
		$account=Account::getUserAccount($user->id,$_REQUEST["currency"]);

		if ($key==$account->getEventChannel()) {
			$currency=$account->getCurrency();
			$formatter=$currency->getFormatterForUser($user->ID);

			$data[".cashier-balance-menu a"]=
				"Balance: ".$formatter->format($account->getBalance());

			$data[".cashier-account-balance"]=$formatter->format($account->getBalance());
			$data[".cashier-account-reserved"]=$formatter->format($account->getReserved(),array(
					"hyphenZero"=>TRUE
				));

			if (array_key_exists("activityPage",$_REQUEST))
				$data["#cashier-transaction-list"]=
					$this->renderActivityTab($user,$currency,$_REQUEST["activityPage"]);
		}

		return $data;
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
		$currency->install();
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
			),
			"settings"=>array(
				"title"=>"Settings",
				"link"=>add_query_arg("tab","settings",$link)
			)
		);

		$tabIds=$adapter["tabs"];
		if (isset($adapter["tabs_cb"]))
			$tabIds=$adapter["tabs_cb"]($currency);

		foreach ($tabIds as $tabId=>$tabName) {
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

		$formatter=$currency->getFormatterForUser($user->ID);

		$vars=array(
			"tabs"=>$tabs,
			"balance"=>$account->getBalance(),
			"balanceText"=>$formatter->format($account->getBalance()),
			"currentTab"=>$currentTab,
			"notices"=>CashierPlugin::instance()->getSessionNotices()->renderNotices(),
			"currencyId"=>$currency->ID
		);

		$t=new Template(__DIR__."/../tpl/currency-header.tpl.php");
		$content=$t->render($vars);

		if ($currentTab=="activity") {
			$content.=HtmlUtil::renderTag("div",array(
				"id"=>"cashier-transaction-list"
			));

			$activityPage=0;
			if (array_key_exists("activityPage",$_REQUEST))
				$activityPage=$_REQUEST["activityPage"];

			$content.=HtmlUtil::renderTag("input",array(
				"type"=>"hidden",
				"class"=>"event-source-param",
				"name"=>"activityPage",
				"value"=>$activityPage
			));
		}

		else if ($currentTab=="settings") {
			foreach ($currency->getDenominations() as $k=>$denomination)
				$denominationOptions[$k]=$denomination["name"]." (".$denomination["symbol"].")";

			$vars=array(
				"denomination"=>$currency->getUserMeta($user->ID,"denomination"),
				"denominationOptions"=>$denominationOptions,
				"currency"=>$currency->ID
			);

			$t=new Template(__DIR__."/../tpl/settings.tpl.php");
			$content.=$t->render($vars);
		}

		else
			$content.=$adapter["tab_cb"]($currentTab,$currency,$user);

		return $content;
	}

	public function renderActivityTab($user, $currency, $activityPage) {
		$account=Account::getUserAccount($user->ID,$currency->ID);
		$formatter=$currency->getFormatterForUser($user->ID);

		$perPage=20;
		$numTransactions=$account->getTransactionCount();
		$numPages=max(1,ceil($numTransactions/$perPage));
		if ($activityPage<0)
			$activityPage=0;
		if ($activityPage>=$numPages)
			$activityPage=$numPages-1;

		$transactions=$account->getTransactions(array(
			"status!"=>"ignored",
			"order by stamp desc",
			"limit ".intval($perPage),
			"offset ".intval($activityPage*$perPage)
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
				"amount"=>$formatter->format($transaction->getRelativeAmount($account)),
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
			"transactions"=>$transactionViews,
			"numPages"=>$numPages,
			"pageLink"=>get_post_permalink($currency->ID),
			"activityPage"=>$activityPage
		);

		$t=new Template(__DIR__."/../tpl/activity.tpl.php");
		return $t->render($vars);
	}
}