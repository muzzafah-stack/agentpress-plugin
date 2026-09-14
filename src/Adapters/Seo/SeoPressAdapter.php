<?php
namespace AgentPress\Adapters\Seo;

use AgentPress\Adapters\AdapterInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter for SEOPress
 */
class SeoPressAdapter implements AdapterInterface {

    public function get_id(): string {
        return 'seopress';
    }

    public function get_name(): string {
        return 'SEOPress';
    }

    public function get_category(): string {
        return 'seo';
    }

    public function is_available(): bool {
        return defined('SEOPRESS_VERSION') || function_exists('seopress_init');
    }

    public function get_capabilities(): array {
        return ['get_meta', 'set_meta'];
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
                        'title'          => get_post_meta($post_id, '_seopress_titles_title', true),
                        'description'    => get_post_meta($post_id, '_seopress_titles_desc', true),
                        'focus_keyword'  => get_post_meta($post_id, '_seopress_analysis_target_kw', true),
                        'canonical_url'  => get_post_meta($post_id, '_seopress_robots_canonical', true),
                    ],
                ];

            case 'set_meta':
                if (isset($params['title'])) {
                    update_post_meta($post_id, '_seopress_titles_title', sanitize_text_field($params['title']));
                }
                if (isset($params['description'])) {
                    update_post_meta($post_id, '_seopress_titles_desc', sanitize_textarea_field($params['description']));
                }
                if (isset($params['focus_keyword'])) {
                    update_post_meta($post_id, '_seopress_analysis_target_kw', sanitize_text_field($params['focus_keyword']));
                }
                if (isset($params['canonical_url'])) {
                    update_post_meta($post_id, '_seopress_robots_canonical', esc_url_raw($params['canonical_url']));
                }
                return ['success' => true, 'post_id' => $post_id, 'updated' => true];

            default:
                return ['success' => false, 'error' => 'Unsupported action for SEOPress adapter.'];
        }
    }
}
