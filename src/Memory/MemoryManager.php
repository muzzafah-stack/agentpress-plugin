<?php
namespace AgentPress\Memory;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages Persistent Project Memory for AI Agent
 */
class MemoryManager {

    /**
     * Store or update a memory item
     *
     * @param string $key Unique identifier
     * @param mixed $value Any serializable data or string
     * @param string $scope Category/scope
     * @param bool $is_locked If locked, cannot be overwritten by AI unless explicitly unlocked
     * @param string $updated_by Identifier
     * @return bool
     */
    public function set(string $key, $value, string $scope = 'project', bool $is_locked = false, string $updated_by = 'ai_agent'): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_memory';

        $key = sanitize_key($key);
        $scope = sanitize_text_field($scope ?: 'project');
        $json_value = is_string($value) ? $value : wp_json_encode($value);

        // Check if existing record is locked
        $existing = $wpdb->get_row($wpdb->prepare("SELECT is_locked FROM {$table} WHERE memory_key = %s", $key));
        if ($existing && (int)$existing->is_locked === 1 && $updated_by === 'ai_agent') {
            return false; // Locked by admin
        }

        $now = current_time('mysql', true);

        if ($existing) {
            $updated = $wpdb->update(
                $table,
                [
                    'scope'        => $scope,
                    'memory_value' => $json_value,
                    'is_locked'    => $is_locked ? 1 : 0,
                    'updated_by'   => sanitize_text_field($updated_by),
                    'updated_at'   => $now,
                ],
                ['memory_key' => $key],
                ['%s', '%s', '%d', '%s', '%s'],
                ['%s']
            );
            return $updated !== false;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'memory_key'   => $key,
                'scope'        => $scope,
                'memory_value' => $json_value,
                'is_locked'    => $is_locked ? 1 : 0,
                'updated_by'   => sanitize_text_field($updated_by),
                'updated_at'   => $now,
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        return (bool) $inserted;
    }

    /**
     * Get a memory item by key
     */
    public function get(string $key) {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_memory';
        $key = sanitize_key($key);

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE memory_key = %s", $key));
        if (!$row) {
            return null;
        }

        $decoded = json_decode($row->memory_value, true);
        return [
            'key'        => $row->memory_key,
            'scope'      => $row->scope,
            'value'      => $decoded !== null ? $decoded : $row->memory_value,
            'is_locked'  => (bool) $row->is_locked,
            'updated_by' => $row->updated_by,
            'updated_at' => $row->updated_at,
        ];
    }

    /**
     * Delete a memory item
     */
    public function delete(string $key): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_memory';
        $key = sanitize_key($key);
        return (bool) $wpdb->delete($table, ['memory_key' => $key], ['%s']);
    }

    /**
     * List all memory items optionally filtered by scope
     */
    public function list(?string $scope = null): array {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_memory';

        if ($scope) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE scope = %s ORDER BY updated_at DESC", $scope));
        } else {
            $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY updated_at DESC");
        }

        $result = [];
        foreach ($rows as $row) {
            $decoded = json_decode($row->memory_value, true);
            $result[] = [
                'key'        => $row->memory_key,
                'scope'      => $row->scope,
                'value'      => $decoded !== null ? $decoded : $row->memory_value,
                'is_locked'  => (bool) $row->is_locked,
                'updated_by' => $row->updated_by,
                'updated_at' => $row->updated_at,
            ];
        }

        return $result;
    }

    /**
     * Wipe all project memory (requires admin/destructive permission)
     */
    public function clear_all(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_memory';
        return (bool) $wpdb->query("TRUNCATE TABLE {$table}");
    }
}
