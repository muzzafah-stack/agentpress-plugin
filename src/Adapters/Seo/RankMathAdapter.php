<?php
namespace AgentPress\Adapters\Seo;

use AgentPress\Adapters\AdapterInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter for Rank Math SEO
 */
class RankMathAdapter implements AdapterInterface {

    public function get_id(): string {
        return 'rank_math';
    }

    public function get_name(): string {
        return 'Rank Math SEO';
    }

    public function get_category(): string {
        return 'seo';
    }

    public function is_available(): bool {
        return defined('RANK_MATH_VERSION') || class_exists('\RankMath');
    }

    public function get_capabilities(): array {
        return ['get_meta', 'set_meta', 'get_focus_keyword', 'set_focus_keyword'];
    }

    public function execute(string $action, array $params): array {
        $post_id = (int) ($params['post_id'] ?? 0);
        if (!$post_id) {
            return ['success' => false, 'error' => 'Missing post_id parameter.'];
        }

        switch ($action) {
            case 'get_meta':
                return [
                    'success' => true,
                    'post_id' => $post_id,
                    'seo'     => [
                        'title'          => get_post_meta($post_id, 'rank_math_title', true),
                        'description'    => get_post_meta($post_id, 'rank_math_description', true),
                        'focus_keyword'  => get_post_meta($post_id, 'rank_math_focus_keyword', true),
                        'canonical_url'  => get_post_meta($post_id, 'rank_math_canonical_url', true),
                        'robots'         => get_post_meta($post_id, 'rank_math_robots', true),
                    ],
                ];

            case 'set_meta':
                if (isset($params['title'])) {
                    update_post_meta($post_id, 'rank_math_title', sanitize_text_field($params['title']));
                }
                if (isset($params['description'])) {
                    update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($params['description']));
                }
                if (isset($params['focus_keyword'])) {
                    update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($params['focus_keyword']));
                }
                if (isset($params['canonical_url'])) {
                    update_post_meta($post_id, 'rank_math_canonical_url', esc_url_raw($params['canonical_url']));
                }
                return ['success' => true, 'post_id' => $post_id, 'updated' => true];

            default:
                return ['success' => false, 'error' => 'Unsupported action for Rank Math adapter.'];
        }
    }
}
