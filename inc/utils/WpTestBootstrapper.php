<?php

class WpTestBootstrapper {
	private function __construct($options) {
		$this->options=$options;

		if (!$this->options["db"])
			throw new \Exeption("Database not specified.");

		if (!$this->options["dbuser"])
			throw new \Exeption("Database user not specified.");

		if (!$this->options["dbpass"])
			throw new \Exeption("Database pass not specified.");

		if (!$this->options["wpdir"])
			throw new \Exeption("Need a WordPress dir.");
	}

	public function log($message) {
		if ($this->options["log"])
			echo "[wptest] ".$message."\n";
	}

	private function system($cmd) {
		$this->log($cmd);

		system($cmd,$res);
		if ($res!==0)
			throw new \Exception("Command failed: ".$res);
	}

	private function run() {
		$this->system(sprintf(
			'echo "drop database if exists %s; create database %s" | mysql -u%s -p%s',
			$this->options["db"],
			$this->options["db"],
			$this->options["dbuser"],
			$this->options["dbpass"]
		));

		$this->system(sprintf('rm -f %s/wp-config.php',
			$this->options["wpdir"],
		));

		$this->system(sprintf(
			'wp --path=%s config create --dbname=%s --dbuser=%s --dbpass=%s',
			$this->options["wpdir"],
			$this->options["db"],
			$this->options["dbuser"],
			$this->options["dbpass"]
		));

		if (file_exists($this->options["dbfile"])) {
			$this->log("Importing data from file...");
			$this->system(sprintf(
				'mysql -u%s -p%s %s < %s',
				$this->options["dbuser"],
				$this->options["dbpass"],
				$this->options["db"],
				$this->options["dbfile"]
			));
		}

		else {
			$this->log("Installing...");
			$this->system(sprintf(
				'wp --path=%s core install --url=%s --title=%s --admin_user=%s --admin_email=%s',
				$this->options["wpdir"],
				"http://localhost",
				"Test",
				"admin",
				"admin@example.com",
				"admin"
			));

			$this->system(sprintf(
				'mysqldump -u%s -p%s %s > %s',
				$this->options["dbuser"],
				$this->options["dbpass"],
				$this->options["db"],
				$this->options["dbfile"]
			));
		}

		require $this->options["wpdir"]."/wp-load.php";
	}

	public static function bootstrap($options=array()) {
		$options["log"]=TRUE;

		$bootstrapper=new WpTestBootstrapper($options);
		$bootstrapper->run();
	}
}