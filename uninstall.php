<?php
/**
 * AgentPress Uninstall Handler
 *
 * Fired when the plugin is deleted via the WordPress Admin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Clean custom database tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}agentpress_tokens");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}agentpress_logs");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}agentpress_memory");

// Delete options
delete_option('agentpress_settings');
delete_option('agentpress_active_tools');
delete_option('agentpress_db_version');

// Clean transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_agentpress_%' OR option_name LIKE '_transient_timeout_agentpress_%'");
