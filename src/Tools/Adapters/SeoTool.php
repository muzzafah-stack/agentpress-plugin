<?php
namespace AgentPress\Tools\Adapters;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO Adapter Tool (Auto-detects Rank Math, SEOPress, Slim SEO)
 */
class SeoTool extends BaseTool {

    public function get_name(): string {
        return 'seo_operations';
    }

    public function get_description(): string {
        return 'Read and update SEO metadata (title, description, focus keywords, canonical) using active SEO plugins (Rank Math, SEOPress, Slim SEO).';
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
                    'enum' => ['detect_plugin', 'get_meta', 'set_meta'],
                    'description' => 'Action to perform.',
                ],
                'post_id' => [
                    'type' => 'integer',
                    'description' => 'Target post ID (for get_meta / set_meta).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Custom SEO title.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Custom meta description.',
                ],
                'focus_keyword' => [
                    'type' => 'string',
                    'description' => 'Primary focus keyword.',
                ],
                'canonical_url' => [
                    'type' => 'string',
                    'description' => 'Custom canonical URL.',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $action = (string) $params['action'];
        $registry = Plugin::get_instance()->adapter_registry;
        $available_seo = $registry->get_available('seo');

        if (empty($available_seo)) {
            return $this->error_response('no_seo_plugin', 'No supported SEO plugin (Rank Math, SEOPress, Slim SEO) is active on this site.');
        }

        // Use the first active SEO adapter
        $adapter = reset($available_seo);

        if ($action === 'detect_plugin') {
            return $this->success_response([
                'active_plugin_adapter' => $adapter->get_id(),
                'adapter_name'          => $adapter->get_name(),
                'capabilities'          => $adapter->get_capabilities(),
            ]);
        }

        $result = $adapter->execute($action, $params);
        if (!($result['success'] ?? false)) {
            return $this->error_response('adapter_error', $result['error'] ?? 'SEO operation failed.');
        }

        return $this->success_response($result);
    }
}
