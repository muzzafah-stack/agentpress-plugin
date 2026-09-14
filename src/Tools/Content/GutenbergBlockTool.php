<?php
namespace AgentPress\Tools\Content;

use AgentPress\Tools\BaseTool;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gutenberg Block Manipulator Tool
 */
class GutenbergBlockTool extends BaseTool {

    public function get_name(): string {
        return 'gutenberg_blocks';
    }

    public function get_description(): string {
        return 'Inspect, validate, and safely edit Gutenberg block structures on posts and pages.';
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
                    'enum' => ['get_blocks', 'update_blocks', 'list_patterns'],
                    'description' => 'Gutenberg action to perform.',
                ],
                'post_id' => [
                    'type' => 'integer',
                    'description' => 'Target post or page ID (required for get_blocks and update_blocks).',
                ],
                'blocks' => [
                    'type' => 'array',
                    'description' => 'Array of Gutenberg block structures to serialize and save (for update_blocks).',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $action = (string) $params['action'];

        switch ($action) {
            case 'get_blocks':
                $post_id = (int) ($params['post_id'] ?? 0);
                if (!$post_id) {
                    return $this->error_response('missing_post_id', 'Parameter `post_id` is required for get_blocks.');
                }

                $post = get_post($post_id);
                if (!$post) {
                    return $this->error_response('post_not_found', 'Post not found.');
                }

                $blocks = parse_blocks($post->post_content);
                return $this->success_response([
                    'post_id'      => $post_id,
                    'post_title'   => $post->post_title,
                    'has_blocks'   => has_blocks($post->post_content),
                    'blocks_count' => count($blocks),
                    'blocks'       => $blocks,
                ]);

            case 'update_blocks':
                $post_id = (int) ($params['post_id'] ?? 0);
                $blocks = $params['blocks'] ?? null;

                if (!$post_id || !is_array($blocks)) {
                    return $this->error_response('invalid_params', 'Parameters `post_id` and `blocks` array are required for update_blocks.');
                }

                $post = get_post($post_id);
                if (!$post) {
                    return $this->error_response('post_not_found', 'Post not found.');
                }

                // Serialize blocks array back into Gutenberg HTML comments
                $serialized = serialize_blocks($blocks);

                $updated = wp_update_post([
                    'ID'           => $post_id,
                    'post_content' => $serialized,
                ], true);

                if (is_wp_error($updated)) {
                    return $this->error_response('update_failed', $updated->get_error_message());
                }

                return $this->success_response([
                    'post_id' => $post_id,
                    'updated' => true,
                ]);

            case 'list_patterns':
                $registry = \WP_Block_Patterns_Registry::get_instance();
                $patterns = $registry ? $registry->get_all_registered() : [];

                $list = [];
                foreach ($patterns as $pattern) {
                    $list[] = [
                        'name'        => $pattern['name'] ?? '',
                        'title'       => $pattern['title'] ?? '',
                        'categories'  => $pattern['categories'] ?? [],
                        'description' => $pattern['description'] ?? '',
                    ];
                }

                return $this->success_response([
                    'total'    => count($list),
                    'patterns' => $list,
                ]);

            default:
                return $this->error_response('invalid_action', 'Unsupported action for Gutenberg tool.');
        }
    }
}
