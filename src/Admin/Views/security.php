<?php
use AgentPress\Core\Plugin;
use AgentPress\Filesystem\Sandbox;

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('agentpress_settings', []);
$sandbox_root = Sandbox::get_sandbox_root();
?>

<div class="agentpress-card">
    <h2><?php esc_html_e('Security Boundaries & Status', 'agentpress'); ?></h2>
    <p><?php esc_html_e('AgentPress enforces multiple defensive layers to keep your WordPress installation secure from untrusted operations.', 'agentpress'); ?></p>

    <div class="agentpress-status-grid">
        <div class="agentpress-status-card">
            <h4><?php esc_html_e('Filesystem Sandbox', 'agentpress'); ?></h4>
            <p class="status-badge active"><?php esc_html_e('Enforced', 'agentpress'); ?></p>
            <p class="description"><?php echo esc_html($sandbox_root); ?></p>
        </div>

        <div class="agentpress-status-card">
            <h4><?php esc_html_e('PHP Execution Engine', 'agentpress'); ?></h4>
            <?php if (!empty($settings['enable_php_exec'])): ?>
                <p class="status-badge warning"><?php esc_html_e('Enabled (Isolated Runtime)', 'agentpress'); ?></p>
            <?php else: ?>
                <p class="status-badge active"><?php esc_html_e('Disabled (Safe Default)', 'agentpress'); ?></p>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('Blocks dangerous shell functions (exec, system, passthru).', 'agentpress'); ?></p>
        </div>

        <div class="agentpress-status-card">
            <h4><?php esc_html_e('WP-CLI Command Whitelist', 'agentpress'); ?></h4>
            <?php if (!empty($settings['enable_wp_cli'])): ?>
                <p class="status-badge active"><?php esc_html_e('Active (Allowlist Only)', 'agentpress'); ?></p>
            <?php else: ?>
                <p class="status-badge"><?php esc_html_e('Disabled', 'agentpress'); ?></p>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('Restricted to safe WordPress management commands.', 'agentpress'); ?></p>
        </div>

        <div class="agentpress-status-card">
            <h4><?php esc_html_e('Secret Redaction', 'agentpress'); ?></h4>
            <p class="status-badge active"><?php esc_html_e('Automatic', 'agentpress'); ?></p>
            <p class="description"><?php esc_html_e('Redacts passwords, DB credentials, and API keys before logging.', 'agentpress'); ?></p>
        </div>
    </div>
</div>

<div class="agentpress-card" style="margin-top: 20px;">
    <h2><?php esc_html_e('Security Principles Implemented', 'agentpress'); ?></h2>
    <ul class="agentpress-checklist">
        <li><strong><?php esc_html_e('Principle of Least Privilege:', 'agentpress'); ?></strong> <?php esc_html_e('Tokens must be granted explicit scopes (Read, Write, Destructive, Code Execution).', 'agentpress'); ?></li>
        <li><strong><?php esc_html_e('Traversal & Symlink Protection:', 'agentpress'); ?></strong> <?php esc_html_e('All file operations validate realpath boundaries before read/write/edit.', 'agentpress'); ?></li>
        <li><strong><?php esc_html_e('Explicit Destructive Confirmation:', 'agentpress'); ?></strong> <?php esc_html_e('Actions like file deletion or memory wipe strictly require `confirm: true`.', 'agentpress'); ?></li>
        <li><strong><?php esc_html_e('Self-Expiring Tokens:', 'agentpress'); ?></strong> <?php esc_html_e('One-time admin access links and temporary upload tokens expire automatically in minutes.', 'agentpress'); ?></li>
    </ul>
</div>
