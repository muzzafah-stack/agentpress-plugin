<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable File Tool (renames to .disabled)
 */
class DisableFileTool extends BaseTool {

    public function get_name(): string {
        return 'disable_file';
    }

    public function get_description(): string {
        return 'Safely disables a file in the sandbox by appending .disabled to its extension.';
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
                    'description' => 'Relative path to file in sandbox.',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) $params['path'];
        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->disable_file($path);

        if (!$result['success']) {
            return $this->error_response('disable_failed', $result['error'] ?? 'Failed to disable file.');
        }

        return $this->success_response($result);
    }
}
