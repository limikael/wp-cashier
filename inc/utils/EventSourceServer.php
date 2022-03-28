<?php

namespace cashier;

class EventSourceServer {
	public function __construct() {
		$this->init();
	}

	public function init() {
		if ($_SERVER["HTTP_ACCEPT"]!="text/event-stream")
			throw new \Exception("Not an event stream request");

		set_time_limit(0);
		ignore_user_abort(null);
		header("Content-type: text/event-stream");

		session_write_close();

		while (ob_get_level())
			ob_end_flush();
	}

	public function ping() {
		$this->send(array("event"=>"ping"));

		return true;
	}

	public function send($data) {
		//error_log("sending: ".print_r($data,TRUE));

		echo "data: ".json_encode($data)."\n\n";
		flush();
	}
}