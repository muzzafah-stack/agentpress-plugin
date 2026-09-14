<?php
namespace AgentPress\Tools\Admin;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Persistent Project Memory Tool
 */
class ManageMemoryTool extends BaseTool {

    public function get_name(): string {
        return 'manage_memory';
    }

    public function get_description(): string {
        return 'Store, retrieve, list, or delete persistent project context, conventions, and task memory.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_WRITE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['get', 'set', 'list', 'delete', 'clear_all'],
                    'description' => 'Memory action to perform.',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Unique memory key (required for get, set, delete).',
                ],
                'value' => [
                    'description' => 'Value to store (string, array, or object) for set action.',
                ],
                'scope' => [
                    'type' => 'string',
                    'enum' => ['project', 'architecture', 'conventions', 'tasks', 'general'],
                    'description' => 'Scope category for set or list action.',
                    'default' => 'project',
                ],
                'is_locked' => [
                    'type' => 'boolean',
                    'description' => 'Lock memory entry so it cannot be overwritten accidentally.',
                    'default' => false,
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Confirmation required for clear_all action.',
                    'default' => false,
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $action = (string) $params['action'];
        $memory = Plugin::get_instance()->memory_manager;

        switch ($action) {
            case 'get':
                $key = sanitize_key((string) ($params['key'] ?? ''));
                if (empty($key)) {
                    return $this->error_response('missing_key', 'Parameter `key` is required for get action.');
                }
                $item = $memory->get($key);
                if (!$item) {
                    return $this->error_response('not_found', sprintf('Memory key `%s` does not exist.', $key));
                }
                return $this->success_response($item);

            case 'set':
                $key = sanitize_key((string) ($params['key'] ?? ''));
                if (empty($key)) {
                    return $this->error_response('missing_key', 'Parameter `key` is required for set action.');
                }
                if (!isset($params['value'])) {
                    return $this->error_response('missing_value', 'Parameter `value` is required for set action.');
                }
                $scope = (string) ($params['scope'] ?? 'project');
                $is_locked = !empty($params['is_locked']);

                $saved = $memory->set($key, $params['value'], $scope, $is_locked, 'ai_agent');
                if (!$saved) {
                    return $this->error_response('save_failed', 'Failed to save memory. Entry may be locked.');
                }
                return $this->success_response(['key' => $key, 'saved' => true]);

            case 'list':
                $scope = !empty($params['scope']) ? (string) $params['scope'] : null;
                $items = $memory->list($scope);
                return $this->success_response(['total' => count($items), 'items' => $items]);

            case 'delete':
                $key = sanitize_key((string) ($params['key'] ?? ''));
                if (empty($key)) {
                    return $this->error_response('missing_key', 'Parameter `key` is required for delete action.');
                }
                $deleted = $memory->delete($key);
                return $this->success_response(['key' => $key, 'deleted' => $deleted]);

            case 'clear_all':
                if (empty($params['confirm'])) {
                    return $this->error_response('confirmation_required', 'Set `confirm: true` to clear all project memory.');
                }
                $cleared = $memory->clear_all();
                return $this->success_response(['cleared' => $cleared]);

            default:
                return $this->error_response('invalid_action', 'Unsupported memory action.');
        }
    }
}
