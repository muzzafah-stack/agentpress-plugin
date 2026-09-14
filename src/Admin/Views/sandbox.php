<?php
use AgentPress\Core\Plugin;
use AgentPress\Filesystem\Sandbox;

if (!defined('ABSPATH')) {
    exit;
}

$plugin = Plugin::get_instance();
$sandbox_root = Sandbox::get_sandbox_root();
$dir_contents = $plugin->sandbox->list_directory('', true);
?>

<div class="agentpress-card">
    <h2><?php esc_html_e('Filesystem Sandbox Explorer', 'agentpress'); ?></h2>
    <p><?php esc_html_e('All files generated or modified by AI agents are safely confined inside this isolated sandbox folder.', 'agentpress'); ?></p>

    <div class="agentpress-field-group">
        <label><strong><?php esc_html_e('Sandbox Root Path:', 'agentpress'); ?></strong></label>
        <code><?php echo esc_html($sandbox_root); ?></code>
    </div>

    <h3><?php esc_html_e('Sandbox Files', 'agentpress'); ?> (<?php echo esc_html($dir_contents['total'] ?? 0); ?> items)</h3>

    <?php if (empty($dir_contents['items'])): ?>
        <p><em><?php esc_html_e('The sandbox directory is currently empty.', 'agentpress'); ?></em></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Path', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Type', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Size', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Last Modified', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Status', 'agentpress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dir_contents['items'] as $item): ?>
                    <?php 
                    $is_disabled = substr($item['name'], -9) === '.disabled';
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($item['path']); ?></code></td>
                        <td><?php echo $item['is_dir'] ? esc_html__('Directory', 'agentpress') : esc_html__('File', 'agentpress'); ?></td>
                        <td><?php echo $item['is_dir'] ? '-' : esc_html(size_format($item['size_bytes'])); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $item['last_modified'])); ?></td>
                        <td>
                            <?php if ($is_disabled): ?>
                                <span class="agentpress-tag warning"><?php esc_html_e('Disabled', 'agentpress'); ?></span>
                            <?php else: ?>
                                <span class="agentpress-tag active"><?php esc_html_e('Active', 'agentpress'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
