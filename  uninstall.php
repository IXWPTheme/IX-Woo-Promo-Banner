<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('ix_wpb_settings');

// Remove custom tables if any
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ix_wpb_data");