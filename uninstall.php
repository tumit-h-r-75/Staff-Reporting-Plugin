<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}staff_reports");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}staff_salary_settings");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}staff_working_days");

delete_option('bsr_version');
