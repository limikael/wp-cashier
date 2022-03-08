<?php

require_once __DIR__."/../../inc/utils/WpTestBootstrapper.php";

WpTestBootstrapper::bootstrap(array(
	"db"=>"wptest",
	"dbuser"=>"mysql",
	"dbpass"=>"mysql",
	"dbfile"=>__DIR__."/wordpress.sql",
	"wpfile"=>__DIR__."/wordpress.zip",
	"wpdir"=>__DIR__."/wordpress"
));

require_once __DIR__."/../../wp-cashier.php";
cashier_activate();

echo "bootstrap done...\n";