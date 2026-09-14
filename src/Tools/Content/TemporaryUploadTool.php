<?php
namespace AgentPress\Tools\Content;

use AgentPress\Tools\BaseTool;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Temporary Upload Tool (Generates signed single-use upload tokens)
 */
class TemporaryUploadTool extends BaseTool {

    public function get_name(): string {
        return 'create_upload_token';
    }

    public function get_description(): string {
        return 'Generates a temporary signed URL/token for uploading plugin ZIPs, theme ZIPs, media, or files.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_WRITE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'target_type' => [
                    'type' => 'string',
                    'enum' => ['sandbox_file', 'plugin_zip', 'theme_zip', 'media'],
                    'description' => 'Target destination type for the upload.',
                    'default' => 'sandbox_file',
                ],
                'filename' => [
                    'type' => 'string',
                    'description' => 'Target filename with extension (e.g. my-plugin.zip, banner.png).',
                ],
                'expires_in_minutes' => [
                    'type' => 'integer',
                    'description' => 'Token expiration in minutes (default 15, max 60).',
                    'default' => 15,
                ],
            ],
            'required' => ['filename'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $filename = sanitize_file_name((string) $params['filename']);
        $target_type = (string) ($params['target_type'] ?? 'sandbox_file');
        $expires_min = min(60, max(1, (int) ($params['expires_in_minutes'] ?? 15)));

        $upload_secret = wp_generate_password(40, false, false);
        $token_hash = hash('sha256', $upload_secret);
        $transient_key = 'agentpress_upl_' . $token_hash;

        $payload = [
            'filename'    => $filename,
            'target_type' => $target_type,
            'created_by'  => $token->id ?? 0,
            'expires_at'  => time() + ($expires_min * MINUTE_IN_SECONDS),
        ];

        set_transient($transient_key, $payload, $expires_min * MINUTE_IN_SECONDS);

        $upload_url = rest_url('agentpress/v1/upload/file?upload_token=' . $upload_secret);

        return $this->success_response([
            'upload_token' => $upload_secret,
            'upload_url'   => $upload_url,
            'filename'     => $filename,
            'target_type'  => $target_type,
            'expires_in'   => $expires_min * 60,
        ]);
    }
}
