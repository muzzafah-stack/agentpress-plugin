<?php
/**
 * Plugin Name:       AgentPress
 * Plugin URI:        https://github.com/muzzafah-stack
 * Description:       Give AI a Home in WordPress. Secure, structured, and modular bridge connecting AI agents to WordPress via MCP-compatible tools, filesystem sandbox, and REST API.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hipnolink Team Digital
 * Author URI:        https://www.hipnolink.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       agentpress
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('AGENTPRESS_VERSION', '1.0.0');
define('AGENTPRESS_FILE', __FILE__);
define('AGENTPRESS_PATH', plugin_dir_path(__FILE__));
define('AGENTPRESS_URL', plugin_dir_url(__FILE__));
define('AGENTPRESS_BASENAME', plugin_basename(__FILE__));

// Require Autoloader
require_once AGENTPRESS_PATH . 'src/Core/Autoloader.php';

// Register PSR-4 Autoloader
\AgentPress\Core\Autoloader::register();

/**
 * Activation Hook
 */
function agentpress_activate() {
    \AgentPress\Database\Migrator::migrate();
    \AgentPress\Filesystem\Sandbox::init_sandbox();
    
    // Set default options if not present
    if (get_option('agentpress_settings') === false) {
        update_option('agentpress_settings', [
            'sandbox_path' => 'agentpress-sandbox',
            'enable_php_exec' => false,
            'enable_wp_cli' => true,
            'php_timeout' => 15,
            'wp_cli_timeout' => 30,
            'max_file_size_kb' => 2048,
            'rate_limit_requests' => 120,
            'rate_limit_window' => 60,
        ]);
    }

    // Flush rewrite rules for REST endpoints if needed
    flush_rewrite_rules();
}
register_activation_hook(AGENTPRESS_FILE, 'agentpress_activate');

/**
 * Deactivation Hook
 */
function agentpress_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(AGENTPRESS_FILE, 'agentpress_deactivate');

/**
 * Bootstrap Plugin
 */
function agentpress_init() {
    return \AgentPress\Core\Plugin::get_instance();
}

add_action('plugins_loaded', 'agentpress_init');
