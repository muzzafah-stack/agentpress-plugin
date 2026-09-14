<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delete File Tool (Requires Destructive Permission & Confirmation)
 */
class DeleteFileTool extends BaseTool {

    public function get_name(): string {
        return 'delete_file';
    }

    public function get_description(): string {
        return 'Deletes a file inside the sandbox. Requires destructive permission and explicit confirm flag.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_DESTRUCTIVE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path to the file inside sandbox.',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Explicit confirmation parameter required to perform deletion.',
                ],
            ],
            'required' => ['path', 'confirm'],
        ];
    }

    public function validate(array $params): array {
        $parent_check = parent::validate($params);
        if (!$parent_check['valid']) {
            return $parent_check;
        }

        if (empty($params['confirm'])) {
            return [
                'valid' => false,
                'error' => 'Destructive operation rejected: `confirm` parameter must be explicitly set to true.',
            ];
        }

        return ['valid' => true, 'error' => ''];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) $params['path'];

        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->delete_file($path);

        if (!$result['success']) {
            return $this->error_response('delete_failed', $result['error'] ?? 'Failed to delete file.');
        }

        return $this->success_response($result);
    }
}
