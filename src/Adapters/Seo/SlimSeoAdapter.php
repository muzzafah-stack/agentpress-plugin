<?php
namespace AgentPress\Adapters\Seo;

use AgentPress\Adapters\AdapterInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter for Slim SEO
 */
class SlimSeoAdapter implements AdapterInterface {

    public function get_id(): string {
        return 'slim_seo';
    }

    public function get_name(): string {
        return 'Slim SEO';
    }

    public function get_category(): string {
        return 'seo';
    }

    public function is_available(): bool {
        return defined('SLIM_SEO_VER') || function_exists('slim_seo');
    }

    public function get_capabilities(): array {
        return ['get_meta', 'set_meta'];
    }

    public function execute(string $action, array $params): array {
        $post_id = (int) ($params['post_id'] ?? 0);
        if (!$post_id) {
            return ['success' => false, 'error' => 'Missing post_id parameter.'];
        }

        $meta = get_post_meta($post_id, 'slim_seo', true) ?: [];

        switch ($action) {
            case 'get_meta':
                return [
                    'success' => true,
                    'post_id' => $post_id,
                    'seo'     => [
                        'title'          => $meta['title'] ?? '',
                        'description'    => $meta['description'] ?? '',
                        'canonical_url'  => $meta['canonical'] ?? '',
                        'noindex'        => !empty($meta['noindex']),
                    ],
                ];

            case 'set_meta':
                if (isset($params['title'])) {
                    $meta['title'] = sanitize_text_field($params['title']);
                }
                if (isset($params['description'])) {
                    $meta['description'] = sanitize_textarea_field($params['description']);
                }
                if (isset($params['canonical_url'])) {
                    $meta['canonical'] = esc_url_raw($params['canonical_url']);
                }
                if (isset($params['noindex'])) {
                    $meta['noindex'] = !empty($params['noindex']) ? 1 : 0;
                }

                update_post_meta($post_id, 'slim_seo', $meta);
                return ['success' => true, 'post_id' => $post_id, 'updated' => true];

            default:
                return ['success' => false, 'error' => 'Unsupported action for Slim SEO adapter.'];
        }
    }
}
