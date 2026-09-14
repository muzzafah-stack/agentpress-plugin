<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Write File Tool
 */
class WriteFileTool extends BaseTool {

    public function get_name(): string {
        return 'write_file';
    }

    public function get_description(): string {
        return 'Writes content to a file inside the sandbox directory. Can overwrite if explicitly specified.';
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
                    'description' => 'Relative path for the file inside sandbox.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'The exact text content to write to the file.',
                ],
                'overwrite' => [
                    'type' => 'boolean',
                    'description' => 'Set to true if you want to overwrite an existing file.',
                    'default' => false,
                ],
            ],
            'required' => ['path', 'content'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) $params['path'];
        $content = (string) $params['content'];
        $overwrite = !empty($params['overwrite']);

        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->write_file($path, $content, $overwrite);

        if (!$result['success']) {
            return $this->error_response('write_failed', $result['error']);
        }

        return $this->success_response($result);
    }
}
