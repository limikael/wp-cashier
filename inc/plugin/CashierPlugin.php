<?php

namespace cashier;

require_once __DIR__."/CashierApi.php";
require_once __DIR__."/../utils/Singleton.php";
require_once __DIR__."/../utils/SessionNotices.php";
require_once __DIR__."/../utils/RevServer.php";
require_once __DIR__."/../model/Currency.php";
require_once __DIR__."/../model/Transaction.php";
require_once __DIR__."/../controller/CurrencyController.php";
require_once __DIR__."/../controller/PlaymoneyController.php";
require_once __DIR__."/../controller/ElectrumController.php";
require_once __DIR__."/../controller/AjaxController.php";
require_once __DIR__."/../controller/UmController.php";

class CashierPlugin extends Singleton {
	private $data;
	private $adapters;
	private $notices;

	protected function __construct() {
		CurrencyController::instance();
		UmController::instance();
		ElectrumController::instance();
		PlaymoneyController::instance();

		$this->data=get_file_data(CASHIER_PATH."/wp-cashier.php",array(
			'Version'=>'Version',
			'TextDomain'=>'Text Domain'
		));

		add_action("wp_enqueue_scripts",array($this,"enqueue_scripts"));
		add_action("admin_enqueue_scripts",array($this,"enqueue_scripts"));
		add_action("cashier_cron",array($this,"cashier_cron"));

		$this->notices=new SessionNotices("cashier_account_notices");

		$dir=sys_get_temp_dir()."/rev-".sanitize_title(get_bloginfo("url"));
		$this->revServer=new RevServer($dir);
		$this->revServer->setTimeout(10);
		$this->revServer->initAjax("events");
	}

	public function cashier_cron() {
		//error_log("Running cron... This is not an error...");
		$userIds=get_users(array("fields"=>"ID"));
		foreach (Currency::findMany() as $currency) {
			foreach ($userIds as $userId) {
				$user=get_user_by("ID",$userId);
				$currency->process($user);
			}
		}
	}

	public function activate() {
		Transaction::install();

		Currency::registerPostType(array(),TRUE);
		flush_rewrite_rules(false);

		wp_schedule_event(time(),"hourly","cashier_cron");
	}

	public function deactivate() {
		wp_clear_scheduled_hook("cashier_cron");
	}

	public function uninstall() {
		Transaction::uninstall();
	}

	public function enqueue_scripts() {
		wp_enqueue_script("qrious",
			CASHIER_URL."/res/qrious.min.js",
			array(),$this->data["Version"],true);

		wp_enqueue_script("cashier",
			CASHIER_URL."/res/cashier.js",
			array("jquery","qrious"),$this->data["Version"],true);

		wp_localize_script("cashier","ajaxurl",admin_url('admin-ajax.php'));

		wp_enqueue_style("cashier-style",
			CASHIER_URL."/res/cashier.css",
			array(),$this->data["Version"]);

		wp_enqueue_style("bootstrap-icons",
			"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css",
			array(),$this->data["Version"]);
	}

	public function getAdapters() {
		$default=array(
			"tabs"=>array()
		);

		if (!$this->adapters) {
			$this->adapters=apply_filters("cashier_adapters",array());

			foreach ($this->adapters as $k=>$adapter)
				$this->adapters[$k]=array_merge($default,$adapter);
		}

		return $this->adapters;
	}

	public function getSessionNotices() {
		return $this->notices;
	}

	public function getRevServer() {
		return $this->revServer;
	}
}
