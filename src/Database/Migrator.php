<?php
namespace AgentPress\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Migrator for AgentPress
 */
class Migrator {

    /**
     * Run table migrations
     */
    public static function migrate(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Tokens Table
        $table_tokens = $wpdb->prefix . 'agentpress_tokens';
        $sql_tokens = "CREATE TABLE {$table_tokens} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            permissions LONGTEXT NOT NULL,
            created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
            expires_at DATETIME NULL,
            last_used_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY status (status)
        ) {$charset_collate};";

        // 2. Audit Logs Table
        $table_logs = $wpdb->prefix . 'agentpress_logs';
        $sql_logs = "CREATE TABLE {$table_logs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token_id BIGINT UNSIGNED NULL,
            tool_name VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL,
            request_params LONGTEXT NULL,
            response_summary LONGTEXT NULL,
            error_message TEXT NULL,
            execution_time_ms INT UNSIGNED NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tool_name (tool_name),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // 3. Project Memory Table
        $table_memory = $wpdb->prefix . 'agentpress_memory';
        $sql_memory = "CREATE TABLE {$table_memory} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            memory_key VARCHAR(100) NOT NULL,
            scope VARCHAR(50) NOT NULL DEFAULT 'project',
            memory_value LONGTEXT NOT NULL,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            updated_by VARCHAR(50) NOT NULL DEFAULT 'ai_agent',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY memory_key (memory_key),
            KEY scope (scope)
        ) {$charset_collate};";

        dbDelta($sql_tokens);
        dbDelta($sql_logs);
        dbDelta($sql_memory);

        update_option('agentpress_db_version', AGENTPRESS_VERSION);
    }
}
