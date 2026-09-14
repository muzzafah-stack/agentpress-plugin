<?php
use AgentPress\Core\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

$plugin = Plugin::get_instance();
$all_tools = $plugin->tool_registry->get_all_tools();
$active_tools = get_option('agentpress_active_tools', []);
?>

<div class="agentpress-card">
    <h2><?php esc_html_e('AI Tool Management & Registry', 'agentpress'); ?></h2>
    <p><?php esc_html_e('Control which tools are exposed to connected AI agents and MCP clients. Disabled tools cannot be discovered or executed.', 'agentpress'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('agentpress_admin_action', 'agentpress_nonce'); ?>
        <input type="hidden" name="agentpress_action" value="save_tools" />

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 60px;"><?php esc_html_e('Active', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Tool Name', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Description', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Required Scope', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Parameters', 'agentpress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_tools as $tool_name => $tool): ?>
                    <?php 
                    $is_active = !isset($active_tools[$tool_name]) || $active_tools[$tool_name] === true;
                    $schema = $tool->get_schema();
                    $required_params = $schema['required'] ?? [];
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="tool_<?php echo esc_attr($tool_name); ?>" value="1" <?php checked($is_active); ?> />
                        </td>
                        <td><code><?php echo esc_html($tool_name); ?></code></td>
                        <td><?php echo esc_html($tool->get_description()); ?></td>
                        <td>
                            <span class="agentpress-tag"><?php echo esc_html($tool->get_required_permission()); ?></span>
                        </td>
                        <td>
                            <small>
                                <?php echo !empty($required_params) ? esc_html(implode(', ', $required_params)) : '<em>' . esc_html__('None required', 'agentpress') . '</em>'; ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Tool Settings', 'agentpress'); ?></button>
        </p>
    </form>
</div>
