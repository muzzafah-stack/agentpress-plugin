<?php
namespace AgentPress\Tools\Adapters;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Page Builder Adapter Tool (Elementor and future builders)
 */
class BuilderTool extends BaseTool {

    public function get_name(): string {
        return 'builder_operations';
    }

    public function get_description(): string {
        return 'Interact with page builder structures, widgets, and templates (Elementor, etc.).';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_WRITE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'builder' => [
                    'type' => 'string',
                    'description' => 'Target builder ID (default: elementor).',
                    'default' => 'elementor',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['get_status', 'get_data', 'update_data', 'list_templates'],
                    'description' => 'Builder action to perform.',
                ],
                'post_id' => [
                    'type' => 'integer',
                    'description' => 'Post ID (for get_data and update_data).',
                ],
                'elements' => [
                    'type' => 'array',
                    'description' => 'Structured element JSON tree for update_data.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $builder_id = (string) ($params['builder'] ?? 'elementor');
        $action = (string) $params['action'];

        $registry = Plugin::get_instance()->adapter_registry;
        $adapter = $registry->get_adapter($builder_id);

        if (!$adapter || !$adapter->is_available()) {
            return $this->error_response(
                'builder_unavailable',
                sprintf('Page builder `%s` is not active on this WordPress installation.', esc_html($builder_id))
            );
        }

        $result = $adapter->execute($action, $params);
        if (!($result['success'] ?? false)) {
            return $this->error_response('builder_error', $result['error'] ?? 'Builder action failed.');
        }

        return $this->success_response($result);
    }
}
