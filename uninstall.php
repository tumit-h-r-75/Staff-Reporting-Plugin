<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bsr_reports");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bsr_salary_settings");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bsr_working_days");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bsr_holidays");

delete_option('bsr_version');
