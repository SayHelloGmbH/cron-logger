<?php

use CronLogger\Plugin;

if (! defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

require_once __DIR__."/plugin.php";

delete_option(Plugin::OPTION_VERSION);

global $wpdb;
$table = Plugin::TABLE_LOGS;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
