<?php
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

$plugin = Plugin::get_instance();
$tokens = $plugin->token_manager->list_tokens();
$new_token_data = get_transient('agentpress_newly_created_token');
if ($new_token_data) {
    delete_transient('agentpress_newly_created_token');
}

$rest_endpoint = rest_url('agentpress/v1');
$all_scopes = PermissionManager::get_all_scopes();
?>

<?php if ($new_token_data): ?>
    <div class="agentpress-alert agentpress-alert-success">
        <h3><?php esc_html_e('🎉 New Token Generated Successfully!', 'agentpress'); ?></h3>
        <p><?php esc_html_e('Copy this token now. For security, it will NOT be shown again.', 'agentpress'); ?></p>
        <div class="agentpress-copy-box">
            <input type="text" readonly id="agentpress-raw-token" value="<?php echo esc_attr($new_token_data['raw_token']); ?>" class="regular-text" />
            <button type="button" class="button button-primary agentpress-copy-btn" data-target="agentpress-raw-token"><?php esc_html_e('Copy Token', 'agentpress'); ?></button>
        </div>
    </div>
<?php endif; ?>

<div class="agentpress-grid-2">
    <!-- Left Column: Connection Info & Client Config -->
    <div class="agentpress-card">
        <h2><?php esc_html_e('AI Connection Details', 'agentpress'); ?></h2>
        <p><?php esc_html_e('Use these credentials to connect your AI agent or MCP client to this WordPress site.', 'agentpress'); ?></p>

        <div class="agentpress-field-group">
            <label><strong><?php esc_html_e('REST Endpoint URL:', 'agentpress'); ?></strong></label>
            <div class="agentpress-copy-box">
                <input type="text" readonly id="agentpress-endpoint" value="<?php echo esc_url($rest_endpoint); ?>" class="large-text" />
                <button type="button" class="button agentpress-copy-btn" data-target="agentpress-endpoint"><?php esc_html_e('Copy', 'agentpress'); ?></button>
            </div>
        </div>

        <div class="agentpress-field-group">
            <label><strong><?php esc_html_e('Claude / Cursor MCP Config Snippet:', 'agentpress'); ?></strong></label>
            <pre class="agentpress-code-block"><code>{
  "mcpServers": {
    "agentpress": {
      "url": "<?php echo esc_url($rest_endpoint . '/tools'); ?>",
      "headers": {
        "Authorization": "Bearer YOUR_AGENTPRESS_TOKEN_HERE"
      }
    }
  }
}</code></pre>
        </div>
    </div>

    <!-- Right Column: Create New Token -->
    <div class="agentpress-card">
        <h2><?php esc_html_e('Generate AI Access Token', 'agentpress'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('agentpress_admin_action', 'agentpress_nonce'); ?>
            <input type="hidden" name="agentpress_action" value="create_token" />

            <div class="agentpress-field-group">
                <label for="token_name"><?php esc_html_e('Token Name / Client:', 'agentpress'); ?></label>
                <input type="text" name="token_name" id="token_name" class="regular-text" placeholder="e.g. Claude Desktop / Cursor Agent" required />
            </div>

            <div class="agentpress-field-group">
                <label><strong><?php esc_html_e('Permission Scopes:', 'agentpress'); ?></strong></label>
                <?php foreach ($all_scopes as $scope_key => $scope_label): ?>
                    <label class="agentpress-checkbox-label">
                        <input type="checkbox" name="token_scopes[]" value="<?php echo esc_attr($scope_key); ?>" <?php checked($scope_key, 'read'); ?> />
                        <strong><?php echo esc_html(ucfirst($scope_key)); ?></strong>: <?php echo esc_html($scope_label); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="agentpress-field-group">
                <label for="expires_days"><?php esc_html_e('Expires in (Days):', 'agentpress'); ?></label>
                <input type="number" name="expires_days" id="expires_days" class="small-text" value="30" min="1" max="365" />
                <span class="description"><?php esc_html_e('Leave empty or 0 for never expiring.', 'agentpress'); ?></span>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Generate Token', 'agentpress'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Active Tokens Table -->
<div class="agentpress-card" style="margin-top: 20px;">
    <h2><?php esc_html_e('Active API Tokens', 'agentpress'); ?></h2>
    <?php if (empty($tokens)): ?>
        <p><?php esc_html_e('No API tokens created yet. Generate one above to allow AI agents to connect.', 'agentpress'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Permissions', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Created At', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Last Used', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Expires', 'agentpress'); ?></th>
                    <th><?php esc_html_e('Action', 'agentpress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tokens as $t): ?>
                    <tr>
                        <td><strong><?php echo esc_html($t->name); ?></strong></td>
                        <td>
                            <?php foreach ($t->permissions as $p): ?>
                                <span class="agentpress-tag"><?php echo esc_html($p); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?php echo esc_html($t->created_at); ?></td>
                        <td><?php echo esc_html($t->last_used_at ?: __('Never', 'agentpress')); ?></td>
                        <td><?php echo esc_html($t->expires_at ?: __('Indefinite', 'agentpress')); ?></td>
                        <td>
                            <form method="post" action="" onsubmit="return confirm('Revoke this token? AI connections using this token will stop working immediately.');">
                                <?php wp_nonce_field('agentpress_admin_action', 'agentpress_nonce'); ?>
                                <input type="hidden" name="agentpress_action" value="revoke_token" />
                                <input type="hidden" name="token_id" value="<?php echo esc_attr($t->id); ?>" />
                                <button type="submit" class="button button-link-delete"><?php esc_html_e('Revoke', 'agentpress'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
