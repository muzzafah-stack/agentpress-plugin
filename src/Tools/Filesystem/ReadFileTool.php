<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read File Tool
 */
class ReadFileTool extends BaseTool {

    public function get_name(): string {
        return 'read_file';
    }

    public function get_description(): string {
        return 'Reads the content of a file within the allowed sandbox directory.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_READ;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative path to the file inside sandbox.',
                ],
                'max_bytes' => [
                    'type' => 'integer',
                    'description' => 'Optional maximum bytes to read (default 1048576 = 1MB).',
                    'default' => 1048576,
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) $params['path'];
        $max_bytes = isset($params['max_bytes']) ? (int) $params['max_bytes'] : 1048576;

        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->read_file($path, $max_bytes);

        if (!$result['success']) {
            return $this->error_response('read_failed', $result['error']);
        }

        return $this->success_response($result);
    }
}
