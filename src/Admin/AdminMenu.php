<?php
namespace AgentPress\Admin;

use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu and Settings UI Controller
 */
class AdminMenu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
    }

    /**
     * Register Admin Menu Page
     */
    public function register_admin_menu(): void {
        add_menu_page(
            __('AgentPress - AI Bridge', 'agentpress'),
            'AgentPress',
            'manage_options',
            'agentpress',
            [$this, 'render_admin_page'],
            'dashicons-rest-api',
            80
        );
    }

    /**
     * Enqueue Admin Styles and Scripts
     */
    public function enqueue_admin_assets(string $hook): void {
        if (strpos($hook, 'agentpress') === false) {
            return;
        }

        wp_enqueue_style(
            'agentpress-admin-css',
            AGENTPRESS_URL . 'assets/css/admin.css',
            [],
            AGENTPRESS_VERSION
        );

        wp_enqueue_script(
            'agentpress-admin-js',
            AGENTPRESS_URL . 'assets/js/admin.js',
            ['jquery'],
            AGENTPRESS_VERSION,
            true
        );

        wp_localize_script('agentpress-admin-js', 'AgentPressAdmin', [
            'rest_url' => rest_url('agentpress/v1/'),
            'nonce'    => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Handle Admin Form Submissions (Token creation, settings update, etc.)
     */
    public function handle_form_submissions(): void {
        if (!current_user_can('manage_options') || !isset($_POST['agentpress_action'])) {
            return;
        }

        check_admin_referer('agentpress_admin_action', 'agentpress_nonce');

        $action = sanitize_text_field($_POST['agentpress_action']);
        $plugin = Plugin::get_instance();

        // 1. Create Token
        if ($action === 'create_token') {
            $name = sanitize_text_field($_POST['token_name'] ?? 'AI Agent');
            $scopes = array_map('sanitize_text_field', $_POST['token_scopes'] ?? ['read']);
            $days = !empty($_POST['expires_days']) ? (int) $_POST['expires_days'] : null;

            $created = $plugin->token_manager->create_token($name, $scopes, $days);
            if ($created['success']) {
                set_transient('agentpress_newly_created_token', $created, 120);
                wp_safe_redirect(admin_url('admin.php?page=agentpress&tab=connection&token_created=1'));
                exit;
            }
        }

        // 2. Revoke Token
        if ($action === 'revoke_token') {
            $token_id = (int) ($_POST['token_id'] ?? 0);
            if ($token_id) {
                $plugin->token_manager->revoke_token($token_id);
                wp_safe_redirect(admin_url('admin.php?page=agentpress&tab=connection&token_revoked=1'));
                exit;
            }
        }

        // 3. Save Settings
        if ($action === 'save_settings') {
            $current_settings = get_option('agentpress_settings', []);
            $new_settings = [
                'sandbox_path'        => sanitize_text_field($_POST['sandbox_path'] ?? 'agentpress-sandbox'),
                'enable_php_exec'     => !empty($_POST['enable_php_exec']),
                'enable_wp_cli'       => !empty($_POST['enable_wp_cli']),
                'php_timeout'         => max(5, min(60, (int) ($_POST['php_timeout'] ?? 15))),
                'wp_cli_timeout'      => max(5, min(120, (int) ($_POST['wp_cli_timeout'] ?? 30))),
                'max_file_size_kb'    => max(100, (int) ($_POST['max_file_size_kb'] ?? 2048)),
                'rate_limit_requests' => max(10, (int) ($_POST['rate_limit_requests'] ?? 120)),
                'rate_limit_window'   => max(10, (int) ($_POST['rate_limit_window'] ?? 60)),
            ];

            update_option('agentpress_settings', array_merge($current_settings, $new_settings));
            wp_safe_redirect(admin_url('admin.php?page=agentpress&tab=settings&saved=1'));
            exit;
        }

        // 4. Save Tool Statuses
        if ($action === 'save_tools') {
            $tools_status = [];
            $all_tools = $plugin->tool_registry->get_all_tools();

            foreach ($all_tools as $tool_name => $tool) {
                $tools_status[$tool_name] = !empty($_POST['tool_' . $tool_name]);
            }

            update_option('agentpress_active_tools', $tools_status);
            wp_safe_redirect(admin_url('admin.php?page=agentpress&tab=tools&saved=1'));
            exit;
        }

        // 5. Clear Logs
        if ($action === 'clear_logs') {
            $plugin->audit_logger->clear_logs();
            wp_safe_redirect(admin_url('admin.php?page=agentpress&tab=logs&cleared=1'));
            exit;
        }

        // 6. Clear Memory
        if ($action === 'clear_memory') {
            $plugin->memory_manager->clear_all();
            wp_safe_redirect(admin_url('admin.php?page=agentpress&tab=memory&cleared=1'));
            exit;
        }
    }

    /**
     * Render the main admin dashboard page
     */
    public function render_admin_page(): void {
        $active_tab = sanitize_key($_GET['tab'] ?? 'connection');
        $tabs = [
            'connection' => __('Connection', 'agentpress'),
            'security'   => __('Security', 'agentpress'),
            'tools'      => __('Tools', 'agentpress'),
            'sandbox'    => __('Sandbox', 'agentpress'),
            'logs'       => __('Logs', 'agentpress'),
            'memory'     => __('Project Memory', 'agentpress'),
            'settings'   => __('Settings', 'agentpress'),
        ];

        include AGENTPRESS_PATH . 'src/Admin/Views/main.php';
    }
}
