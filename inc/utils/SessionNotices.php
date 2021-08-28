<?php

namespace cashier;

class SessionNotices {
	private $sessionKey;

	public function __construct($sessionKey) {
		$this->sessionKey=$sessionKey;

		if (!session_id())
			session_start();
	}

	public function notice($message, $class="success") {

		if ($class=="error")
			$class="danger";

		if (!array_key_exists("tonopah_account_notices",$_SESSION))
			$_SESSION[$this->sessionKey]=array();

		$_SESSION[$this->sessionKey][]=array(
			"message"=>$message,
			"class"=>$class
		);
	}

	public function renderNotices() {
		if (!array_key_exists($this->sessionKey,$_SESSION))
			return;

		$notices=$_SESSION[$this->sessionKey];
		if (!$notices)
			$notices=array();

		$res="";
		foreach ($notices as $notice) {
			$t=new Template(__DIR__."/SessionNotices.tpl.php");
			$res.=$t->render($notice);
		}

		unset($_SESSION[$this->sessionKey]);
		return $res;
	}
}