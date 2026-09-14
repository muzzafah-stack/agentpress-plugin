<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Edit File Tool
 */
class EditFileTool extends BaseTool {

    public function get_name(): string {
        return 'edit_file';
    }

    public function get_description(): string {
        return 'Replaces target text content inside an existing file with new content. Automatically creates a timestamped backup before modification.';
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
                    'description' => 'Relative path to the target file inside sandbox.',
                ],
                'target_content' => [
                    'type' => 'string',
                    'description' => 'Exact string to find and replace in the file.',
                ],
                'replacement_content' => [
                    'type' => 'string',
                    'description' => 'New string to replace the target content with.',
                ],
                'allow_multiple' => [
                    'type' => 'boolean',
                    'description' => 'If true, replaces all matching occurrences. If false, fails if more than one match exists.',
                    'default' => false,
                ],
            ],
            'required' => ['path', 'target_content', 'replacement_content'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) $params['path'];
        $target = (string) $params['target_content'];
        $replacement = (string) $params['replacement_content'];
        $allow_multiple = !empty($params['allow_multiple']);

        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->edit_file($path, $target, $replacement, $allow_multiple);

        if (!$result['success']) {
            return $this->error_response('edit_failed', $result['error']);
        }

        return $this->success_response($result);
    }
}
