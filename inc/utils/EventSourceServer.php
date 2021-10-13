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
	}

	public function ping() {
		$this->send(array("event"=>"ping"));

		return true;
	}

	public function send($data) {
		echo "data: ".json_encode($data)."\n\n";
		@ob_flush();
		flush();
	}
}