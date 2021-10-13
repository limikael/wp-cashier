<?php

namespace cashier;

class FifoSignal {
	private $f;

	public function __construct($fn) {
		$this->fn=$fn;
	}

	public function ensureExists() {
		if (!file_exists($this->fn))
			posix_mkfifo($this->fn,0644);

		if (!file_exists($this->fn))
			throw new \Exception("Can't create");
	}

	public function open() {
		$this->ensureExists();
		$this->f=fopen($this->fn,"rn");
		if ($this->f===FALSE)
			throw new \Exception("Unable to open fifo");
	}

	public function wait($timeout=10) {
		if (!$this->f)
			throw new \Exception("Not open");

		$r=array($this->f);
		$w=array();
		$x=array();
		stream_select($r,$w,$x,$timeout);
		if (in_array($this->f,$r)) {
			fclose($this->f);
			$this->f=NULL;
			return TRUE;
		}

		return FALSE;
	}

	public function notify() {
		$this->ensureExists();
		$this->f=fopen($this->fn,"w+");
		if ($this->f===FALSE)
			throw new \Exception("Unable to open fifo");
	}

	public static function waitAll($fifoSignals, $timeout=30) {
		$r=array();
		$w=array();
		$x=array();

		foreach ($fifoSignals as $fifoSignal) {
			if (!$fifoSignal->f)
				throw new \Exception("Not open");

			$r[]=$fifoSignal->f;
		}

		stream_select($r,$w,$x,$timeout);

		$res=array();
		foreach ($fifoSignals as $fifoSignal) {
			if (in_array($fifoSignal->f,$r)) {
				fclose($fifoSignal->f);
				$fifoSignal->f=NULL;
				$res[]=$fifoSignal;
			}
		}

		return $res;
	}
}