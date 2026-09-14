<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('agentpress_settings', []);
?>

<div class="agentpress-card">
    <h2><?php esc_html_e('AgentPress General Settings', 'agentpress'); ?></h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('agentpress_admin_action', 'agentpress_nonce'); ?>
        <input type="hidden" name="agentpress_action" value="save_settings" />

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="sandbox_path"><?php esc_html_e('Sandbox Relative Path', 'agentpress'); ?></label></th>
                    <td>
                        <code>wp-content/uploads/</code>
                        <input name="sandbox_path" type="text" id="sandbox_path" value="<?php echo esc_attr($settings['sandbox_path'] ?? 'agentpress-sandbox'); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Directory name inside wp-content/uploads where AI file operations are restricted.', 'agentpress'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('PHP Execution Engine', 'agentpress'); ?></th>
                    <td>
                        <fieldset>
                            <label for="enable_php_exec">
                                <input name="enable_php_exec" type="checkbox" id="enable_php_exec" value="1" <?php checked(!empty($settings['enable_php_exec'])); ?> />
                                <?php esc_html_e('Enable safe PHP execution tool (`execute_php`)', 'agentpress'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, AI agents with `code_execution` permission can run isolated PHP snippets with dangerous functions filtered out.', 'agentpress'); ?></p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="php_timeout"><?php esc_html_e('PHP Execution Timeout (Seconds)', 'agentpress'); ?></label></th>
                    <td>
                        <input name="php_timeout" type="number" id="php_timeout" value="<?php echo esc_attr($settings['php_timeout'] ?? 15); ?>" min="5" max="60" class="small-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('WP-CLI Execution Engine', 'agentpress'); ?></th>
                    <td>
                        <fieldset>
                            <label for="enable_wp_cli">
                                <input name="enable_wp_cli" type="checkbox" id="enable_wp_cli" value="1" <?php checked(!empty($settings['enable_wp_cli'])); ?> />
                                <?php esc_html_e('Enable WP-CLI execution tool (`execute_wp_cli`)', 'agentpress'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('When enabled, AI agents with `code_execution` permission can run allowlisted WP-CLI commands.', 'agentpress'); ?></p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="wp_cli_timeout"><?php esc_html_e('WP-CLI Timeout (Seconds)', 'agentpress'); ?></label></th>
                    <td>
                        <input name="wp_cli_timeout" type="number" id="wp_cli_timeout" value="<?php echo esc_attr($settings['wp_cli_timeout'] ?? 30); ?>" min="5" max="120" class="small-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="max_file_size_kb"><?php esc_html_e('Max File Size Limit (KB)', 'agentpress'); ?></label></th>
                    <td>
                        <input name="max_file_size_kb" type="number" id="max_file_size_kb" value="<?php echo esc_attr($settings['max_file_size_kb'] ?? 2048); ?>" min="100" class="regular-text" />
                        <p class="description"><?php esc_html_e('Maximum size in Kilobytes for file reading and writing within the sandbox.', 'agentpress'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="rate_limit_requests"><?php esc_html_e('Rate Limiting', 'agentpress'); ?></label></th>
                    <td>
                        <input name="rate_limit_requests" type="number" id="rate_limit_requests" value="<?php echo esc_attr($settings['rate_limit_requests'] ?? 120); ?>" min="10" class="small-text" />
                        <?php esc_html_e('requests per', 'agentpress'); ?>
                        <input name="rate_limit_window" type="number" id="rate_limit_window" value="<?php echo esc_attr($settings['rate_limit_window'] ?? 60); ?>" min="10" class="small-text" />
                        <?php esc_html_e('seconds window.', 'agentpress'); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save General Settings', 'agentpress'); ?></button>
        </p>
    </form>
</div>
