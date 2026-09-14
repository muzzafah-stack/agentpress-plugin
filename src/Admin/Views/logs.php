<?php
use AgentPress\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

$plugin = Plugin::get_instance();
$status_filter = sanitize_text_field($_GET['status_filter'] ?? '');
$logs = $plugin->audit_logger->get_logs(100, 0, $status_filter ?: null);
?>

<div class="agentpress-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2><?php esc_html_e('Audit Trail & Execution Logs', 'agentpress'); ?></h2>
        
        <form method="post" action="" onsubmit="return confirm('Clear all audit logs permanently?');">
            <?php wp_nonce_field('agentpress_admin_action', 'agentpress_nonce'); ?>
            <input type="hidden" name="agentpress_action" value="clear_logs" />
            <button type="submit" class="button button-link-delete"><?php esc_html_e('Clear Logs', 'agentpress'); ?></button>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <p><em><?php esc_html_e('No log entries recorded yet.', 'agentpress'); ?></em></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 140px;"><?php esc_html_e('Timestamp', 'agentpress'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Tool', 'agentpress'); ?></th>
                    <th style="width: 80px;"><?php esc_html_e('Status', 'agentpress'); ?></th>
                    <th style="width: 80px;"><?php esc_html_e('Duration', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Details / Error', 'agentpress'); ?></th>
                    <th style="width: 100px;"><?php esc_html_e('IP Address', 'agentpress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><small><?php echo esc_html($log->created_at); ?></small></td>
                        <td><code><?php echo esc_html($log->tool_name); ?></code></td>
                        <td>
                            <?php if ($log->status === 'success'): ?>
                                <span class="agentpress-tag active"><?php esc_html_e('Success', 'agentpress'); ?></span>
                            <?php elseif ($log->status === 'denied'): ?>
                                <span class="agentpress-tag warning"><?php esc_html_e('Denied', 'agentpress'); ?></span>
                            <?php else: ?>
                                <span class="agentpress-tag error"><?php esc_html_e('Error', 'agentpress'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo esc_html($log->execution_time_ms); ?>ms</small></td>
                        <td>
                            <?php if (!empty($log->error_message)): ?>
                                <span class="agentpress-error-text"><strong><?php esc_html_e('Error:', 'agentpress'); ?></strong> <?php echo esc_html($log->error_message); ?></span>
                            <?php elseif (!empty($log->request_params)): ?>
                                <details class="agentpress-details">
                                    <summary><?php esc_html_e('View Request Parameters', 'agentpress'); ?></summary>
                                    <pre class="agentpress-code-block"><code><?php echo esc_html(wp_json_encode($log->request_params, JSON_PRETTY_PRINT)); ?></code></pre>
                                </details>
                            <?php else: ?>
                                <small><em>-</em></small>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo esc_html($log->ip_address); ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
