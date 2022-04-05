<?php
/**
 * Custodial bitcoin accounts
 *
 * Plugin Name:       Cashier
 * Plugin URI:        https://github.com/limikael/wp-cashier
 * Description:       Custodial Accounts.
 * Version:           0.0.21
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Mikael Lindqvist
 */

defined('ABSPATH') || exit;

define('CASHIER_URL',plugin_dir_url(__FILE__));
define('CASHIER_PATH',plugin_dir_path(__FILE__));

require_once(__DIR__."/inc/plugin/CashierPlugin.php");

function cashier_activate() {
	cashier\CashierPlugin::instance()->activate();
}
register_activation_hook(__FILE__,'cashier_activate');

function cashier_deactivate() {
	cashier\CashierPlugin::instance()->deactivate();
}
register_deactivation_hook( __FILE__, 'cashier_deactivate' );

function cashier_uninstall() {
	cashier\CashierPlugin::instance()->uninstall();
}
register_uninstall_hook( __FILE__, 'cashier_uninstall' );

function cashier_api() {
	return cashier\CashierApi::instance();
}

cashier\CashierPlugin::instance();
