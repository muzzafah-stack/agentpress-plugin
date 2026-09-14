<?php
namespace AgentPress\Tools\Filesystem;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * List Directory Tool
 */
class ListDirectoryTool extends BaseTool {

    public function get_name(): string {
        return 'list_directory';
    }

    public function get_description(): string {
        return 'Lists files and directories inside the sandbox with support for recursive scan and glob filtering.';
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
                    'description' => 'Relative path to directory inside sandbox. Leave empty for root.',
                    'default' => '',
                ],
                'recursive' => [
                    'type' => 'boolean',
                    'description' => 'If true, recursively scan all subdirectories.',
                    'default' => false,
                ],
                'glob' => [
                    'type' => 'string',
                    'description' => 'Optional filename pattern filter (e.g. *.php, *.json).',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $path = (string) ($params['path'] ?? '');
        $recursive = !empty($params['recursive']);
        $glob = !empty($params['glob']) ? (string) $params['glob'] : null;

        $sandbox = Plugin::get_instance()->sandbox;
        $result = $sandbox->list_directory($path, $recursive, $glob);

        if (!$result['success']) {
            return $this->error_response('list_failed', $result['error']);
        }

        return $this->success_response($result);
    }
}
