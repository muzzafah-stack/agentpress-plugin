<?php
namespace AgentPress\Auth;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles One-Time Admin Magic Login Links
 */
class OneTimeLogin {

    /**
     * Generate a single-use login link
     *
     * @param int $user_id Target administrator user ID
     * @param int $expires_minutes Expiration in minutes (default 15)
     * @return array
     */
    public function generate_link(int $user_id, int $expires_minutes = 15): array {
        $user = get_user_by('id', $user_id);
        if (!$user || !user_can($user, 'manage_options')) {
            return [
                'success' => false,
                'error'   => 'Invalid user. User must exist and have administrator capabilities.'
            ];
        }

        $raw_token = wp_generate_password(64, false, false);
        $token_hash = hash('sha256', $raw_token);
        $transient_key = 'agentpress_otl_' . $token_hash;

        $payload = [
            'user_id'    => $user_id,
            'created_at' => time(),
            'expires_at' => time() + ($expires_minutes * MINUTE_IN_SECONDS),
        ];

        set_transient($transient_key, $payload, $expires_minutes * MINUTE_IN_SECONDS);

        $login_url = add_query_arg([
            'agentpress_otl' => $raw_token
        ], admin_url());

        return [
            'success'     => true,
            'login_url'   => $login_url,
            'user_login'  => $user->user_login,
            'expires_in'  => $expires_minutes * 60,
            'expires_at'  => gmdate('Y-m-d H:i:s', $payload['expires_at']),
        ];
    }

    /**
     * Handle incoming one-time login query string
     */
    public function handle_login_attempt(): void {
        if (!isset($_GET['agentpress_otl']) || empty($_GET['agentpress_otl'])) {
            return;
        }

        $raw_token = sanitize_text_field(wp_unslash($_GET['agentpress_otl']));
        $token_hash = hash('sha256', $raw_token);
        $transient_key = 'agentpress_otl_' . $token_hash;

        $payload = get_transient($transient_key);

        // Delete immediately regardless of outcome (One-Time Guarantee)
        delete_transient($transient_key);

        if (!$payload || !isset($payload['user_id'])) {
            wp_die(
                esc_html__('This one-time admin login link is invalid or has expired.', 'agentpress'),
                esc_html__('Access Denied', 'agentpress'),
                ['response' => 403]
            );
        }

        $user_id = (int) $payload['user_id'];
        $user = get_user_by('id', $user_id);

        if (!$user) {
            wp_die(
                esc_html__('Target user no longer exists.', 'agentpress'),
                esc_html__('User Not Found', 'agentpress'),
                ['response' => 404]
            );
        }

        // Authenticate user
        wp_clear_auth_cookie();
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true);
        do_action('wp_login', $user->user_login, $user);

        // Redirect to admin dashboard
        wp_safe_redirect(admin_url());
        exit;
    }
}
