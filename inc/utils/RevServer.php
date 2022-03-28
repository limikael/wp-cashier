<?php

namespace cashier;

require_once __DIR__."/EventSourceServer.php";
require_once __DIR__."/FifoSignal.php";

class RevServer {
	private $dir;
	private $eventSourceServer;
	private $channels;
	private $timeout;

	public function __construct($dir) {
		if (!file_exists($dir))
			mkdir($dir);

		if (!is_dir($dir))
			throw new \Exception("Unable to open dir");

		$this->dir=$dir;
		$this->channels=array();
		$this->timeout=30;
	}

	public function initAjax($action) {
		add_action("wp_ajax_$action",array($this,"dispatch"));
		add_action("wp_ajax_nopriv_$action",array($this,"dispatch"));
	}

	public function setTimeout($timeout) {
		$this->timeout=$timeout;
	}

	public function dispatch() {
		$channelNames=array();
		$channelNames=apply_filters("sse_init",$channelNames);

		$this->channels=array();
		foreach ($channelNames as $channelName)
			$this->channels[$channelName]=NULL;

		session_write_close();
		$this->run();
	}

	public function run() {
		$this->eventSourceServer=new EventSourceServer();

		//error_log("running event soruce...");

		$lastPing=time();
		while (TRUE) {
			wp_cache_flush();

			foreach ($this->channels as $key=>$fifo) {
				if (!$fifo) {
					$this->channels[$key]=new FifoSignal($this->dir."/".$key);
					$this->channels[$key]->open();

					$data=apply_filters("sse_data",array(),$key);
					$this->eventSourceServer->send($data);
				}
			}

			$fifoSignals=array();
			foreach ($this->channels as $key=>$fifo)
				$fifoSignals[]=$fifo;

			if (count($fifoSignals)) {
				$triggered=FifoSignal::waitAll($fifoSignals,$this->timeout);
				foreach ($this->channels as $key=>$fifo) {
					if (in_array($fifo,$triggered))
						$this->channels[$key]=NULL;
				}
			}

			else {
				$triggered=array();
				sleep($this->timeout);
			}

			if (!count($triggered) || time()>=$lastPing+$this->timeout) {
				$lastPing=time();
				$data=apply_filters("sse_ping",array());
				if (!$data)
					$data=array("event"=>"ping");

				$this->eventSourceServer->send($data);
			}
		}
	}

	public function notify($key) {
		$fifo=new FifoSignal($this->dir."/".$key);
		$fifo->notify();
	}
}