<?php
/**
 * Custodial bitcoin accounts
 *
 * Plugin Name:       Custodial Accounts
 * Plugin URI:        https://github.com/limikael/wp-custodial
 * Description:       Custodial Accounts.
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Mikael Lindqvist
 */

defined( 'ABSPATH' ) || exit;

define('CUSTODIAL_URL',plugin_dir_url(__FILE__));
define('CUSTODIAL_PATH',plugin_dir_path(__FILE__));

require_once(__DIR__."/inc/plugin/CustodialPlugin.php");

custodial\CustodialPlugin::instance();
