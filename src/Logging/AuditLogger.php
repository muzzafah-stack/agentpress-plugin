<?php
namespace AgentPress\Logging;

use AgentPress\Security\Sanitizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Audit Logger for Tool & API operations
 */
class AuditLogger {

    private Sanitizer $sanitizer;

    public function __construct() {
        $this->sanitizer = new Sanitizer();
    }

    /**
     * Record an audit log entry
     *
     * @param int|null $token_id
     * @param string $tool_name
     * @param string $status 'success', 'error', 'denied'
     * @param mixed $request_params
     * @param mixed $response_data
     * @param string|null $error_message
     * @param int $execution_time_ms
     * @return int Insert ID
     */
    public function log(
        ?int $token_id,
        string $tool_name,
        string $status,
        $request_params = null,
        $response_data = null,
        ?string $error_message = null,
        int $execution_time_ms = 0
    ): int {
        global $wpdb;

        $redacted_params = $this->sanitizer->redact_secrets($request_params);
        $redacted_response = $this->sanitizer->redact_secrets($response_data);

        // Summarize response if too large
        $response_json = wp_json_encode($redacted_response);
        if (strlen($response_json) > 10000) {
            $response_json = substr($response_json, 0, 10000) . '... [TRUNCATED]';
        }

        $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');

        $table = $wpdb->prefix . 'agentpress_logs';
        $inserted = $wpdb->insert(
            $table,
            [
                'token_id'          => $token_id,
                'tool_name'         => sanitize_text_field($tool_name),
                'status'            => sanitize_text_field($status),
                'request_params'    => wp_json_encode($redacted_params),
                'response_summary'  => $response_json,
                'error_message'     => $error_message ? sanitize_text_field($error_message) : null,
                'execution_time_ms' => $execution_time_ms,
                'ip_address'        => $ip_address,
                'created_at'        => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * Get paginated logs
     */
    public function get_logs(int $limit = 50, int $offset = 0, ?string $status = null, ?string $tool_name = null): array {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_logs';

        $where = [];
        $params = [];

        if ($status) {
            $where[] = "status = %s";
            $params[] = $status;
        }

        if ($tool_name) {
            $where[] = "tool_name = %s";
            $params[] = $tool_name;
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM {$table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $prepared = $wpdb->prepare($sql, ...$params);
        $rows = $wpdb->get_results($prepared);

        foreach ($rows as $row) {
            $row->request_params = json_decode($row->request_params, true);
            $row->response_summary = json_decode($row->response_summary, true) ?: $row->response_summary;
        }

        return $rows ?: [];
    }

    /**
     * Clear all logs
     */
    public function clear_logs(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_logs';
        return (bool) $wpdb->query("TRUNCATE TABLE {$table}");
    }
}
