<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enable File Tool (restores from .disabled)
 */
class EnableFileTool extends BaseTool {

    public function get_name(): string {
        return 'enable_file';
    }

    public function get_description(): string {
        return 'Re-enables a previously disabled file by removing its .disabled extension.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_WRITE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path to the disabled file (with .disabled extension).',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) $params['path'];
        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->enable_file($path);

        if (!$result['success']) {
            return $this->error_response('enable_failed', $result['error'] ?? 'Failed to enable file.');
        }

        return $this->success_response($result);
    }
}
