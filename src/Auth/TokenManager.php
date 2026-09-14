<?php
namespace AgentPress\Auth;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages Bearer Tokens for AI Agent Authentication
 */
class TokenManager {

    /**
     * Generate and save a new API token
     *
     * @param string $name Token descriptive name
     * @param array $permissions List of allowed capability scopes
     * @param int|null $expires_in_days Expiration period in days (null for indefinite)
     * @param int $user_id WordPress user ID creating this token
     * @return array Array containing raw token and token database record info
     */
    public function create_token(string $name, array $permissions = ['read', 'write'], ?int $expires_in_days = 30, int $user_id = 0): array {
        global $wpdb;

        // Generate high entropy random token
        $raw_token = 'ap_' . wp_generate_password(48, false, false);
        $token_hash = hash('sha256', $raw_token);

        $expires_at = null;
        if ($expires_in_days !== null && $expires_in_days > 0) {
            $expires_at = gmdate('Y-m-d H:i:s', time() + ($expires_in_days * DAY_IN_SECONDS));
        }

        $table = $wpdb->prefix . 'agentpress_tokens';
        $inserted = $wpdb->insert(
            $table,
            [
                'name'         => sanitize_text_field($name),
                'token_hash'   => $token_hash,
                'permissions'  => wp_json_encode($permissions),
                'created_by'   => $user_id ?: get_current_user_id(),
                'expires_at'   => $expires_at,
                'status'       => 'active',
                'created_at'   => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return ['success' => false, 'error' => 'Database error creating token'];
        }

        return [
            'success'     => true,
            'token_id'    => $wpdb->insert_id,
            'name'        => $name,
            'raw_token'   => $raw_token, // Only shown once
            'permissions' => $permissions,
            'expires_at'  => $expires_at,
        ];
    }

    /**
     * Validate raw token from HTTP request header
     *
     * @param string $raw_token
     * @return object|null Token record object if valid, null otherwise
     */
    public function validate_token(string $raw_token): ?object {
        global $wpdb;

        if (empty($raw_token)) {
            return null;
        }

        $token_hash = hash('sha256', trim($raw_token));
        $table = $wpdb->prefix . 'agentpress_tokens';

        $token = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE token_hash = %s AND status = 'active' LIMIT 1",
                $token_hash
            )
        );

        if (!$token) {
            return null;
        }

        // Check expiration
        if (!empty($token->expires_at)) {
            $expiry_time = strtotime($token->expires_at . ' UTC');
            if (time() > $expiry_time) {
                return null;
            }
        }

        // Update last used timestamp
        $wpdb->update(
            $table,
            ['last_used_at' => current_time('mysql', true)],
            ['id' => $token->id],
            ['%s'],
            ['%d']
        );

        $token->permissions_array = json_decode($token->permissions, true) ?: [];

        return $token;
    }

    /**
     * Revoke / Delete a token by ID
     */
    public function revoke_token(int $token_id): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_tokens';
        return (bool) $wpdb->delete($table, ['id' => $token_id], ['%d']);
    }

    /**
     * List all tokens (without hashes)
     */
    public function list_tokens(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'agentpress_tokens';
        $rows = $wpdb->get_results("SELECT id, name, permissions, created_by, expires_at, last_used_at, status, created_at FROM {$table} ORDER BY id DESC");

        foreach ($rows as $row) {
            $row->permissions = json_decode($row->permissions, true) ?: [];
        }

        return $rows ?: [];
    }
}
