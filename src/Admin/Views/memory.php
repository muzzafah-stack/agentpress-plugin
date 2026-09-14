<?php
use AgentPress\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

$plugin = Plugin::get_instance();
$memory_items = $plugin->memory_manager->list();
?>

<div class="agentpress-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2><?php esc_html_e('Persistent Project Memory', 'agentpress'); ?></h2>

        <form method="post" action="" onsubmit="return confirm('Wipe all project memory entries permanently?');">
            <?php wp_nonce_field('agentpress_admin_action', 'agentpress_nonce'); ?>
            <input type="hidden" name="agentpress_action" value="clear_memory" />
            <button type="submit" class="button button-link-delete"><?php esc_html_e('Wipe Memory', 'agentpress'); ?></button>
        </form>
    </div>

    <p><?php esc_html_e('This table holds long-term memory for AI agents (goals, conventions, tasks, known issues) across multiple sessions.', 'agentpress'); ?></p>

    <?php if (empty($memory_items)): ?>
        <p><em><?php esc_html_e('No memory entries recorded yet. AI agents can store project context using the `manage_memory` tool.', 'agentpress'); ?></em></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 160px;"><?php esc_html_e('Key', 'agentpress'); ?></th>
                    <th style="width: 100px;"><?php esc_html_e('Scope', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Value', 'agentpress'); ?></th>
                    <th style="width: 80px;"><?php esc_html_e('Locked', 'agentpress'); ?></th>
                    <th style="width: 140px;"><?php esc_html_e('Last Updated', 'agentpress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memory_items as $item): ?>
                    <tr>
                        <td><strong><code><?php echo esc_html($item['key']); ?></code></strong></td>
                        <td><span class="agentpress-tag"><?php echo esc_html($item['scope']); ?></span></td>
                        <td>
                            <?php if (is_array($item['value']) || is_object($item['value'])): ?>
                                <pre class="agentpress-code-block"><code><?php echo esc_html(wp_json_encode($item['value'], JSON_PRETTY_PRINT)); ?></code></pre>
                            <?php else: ?>
                                <span><?php echo esc_html($item['value']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_locked']): ?>
                                <span class="agentpress-tag warning"><?php esc_html_e('Locked', 'agentpress'); ?></span>
                            <?php else: ?>
                                <span class="agentpress-tag active"><?php esc_html_e('Unlocked', 'agentpress'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo esc_html($item['updated_at']); ?> (<?php echo esc_html($item['updated_by']); ?>)</small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
