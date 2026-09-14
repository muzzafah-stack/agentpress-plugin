<?php
namespace AgentPress\Adapters\Builders;

use AgentPress\Adapters\AdapterInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter for Elementor Page Builder
 */
class ElementorAdapter implements AdapterInterface {

    public function get_id(): string {
        return 'elementor';
    }

    public function get_name(): string {
        return 'Elementor';
    }

    public function get_category(): string {
        return 'builder';
    }

    public function is_available(): bool {
        return defined('ELEMENTOR_VERSION') || class_exists('\Elementor\Plugin');
    }

    public function get_capabilities(): array {
        return ['get_status', 'get_data', 'update_data', 'list_templates'];
    }

    public function execute(string $action, array $params): array {
        switch ($action) {
            case 'get_status':
                return [
                    'success'   => true,
                    'available' => $this->is_available(),
                    'version'   => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'unknown',
                ];

            case 'get_data':
                $post_id = (int) ($params['post_id'] ?? 0);
                if (!$post_id) {
                    return ['success' => false, 'error' => 'Missing post_id parameter.'];
                }

                $is_elementor = get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder';
                $raw_data = get_post_meta($post_id, '_elementor_data', true);
                $elements = !empty($raw_data) ? json_decode($raw_data, true) : [];

                return [
                    'success'              => true,
                    'post_id'              => $post_id,
                    'is_built_with_elementor' => $is_elementor,
                    'elements_count'       => is_array($elements) ? count($elements) : 0,
                    'elements'             => $elements,
                ];

            case 'update_data':
                $post_id = (int) ($params['post_id'] ?? 0);
                $elements = $params['elements'] ?? null;

                if (!$post_id || !is_array($elements)) {
                    return ['success' => false, 'error' => 'Parameters `post_id` and `elements` array are required.'];
                }

                update_post_meta($post_id, '_elementor_edit_mode', 'builder');
                update_post_meta($post_id, '_elementor_data', wp_slash(wp_json_encode($elements)));

                // Clear Elementor CSS cache if class exists
                if (class_exists('\Elementor\Plugin')) {
                    \Elementor\Plugin::$instance->files_manager->clear_cache();
                }

                return ['success' => true, 'post_id' => $post_id, 'updated' => true];

            case 'list_templates':
                $templates = get_posts([
                    'post_type'      => 'elementor_library',
                    'posts_per_page' => 50,
                    'post_status'    => 'publish',
                ]);

                $list = [];
                foreach ($templates as $t) {
                    $list[] = [
                        'id'    => $t->ID,
                        'title' => $t->post_title,
                        'type'  => get_post_meta($t->ID, '_elementor_template_type', true),
                    ];
                }

                return ['success' => true, 'total' => count($list), 'templates' => $list];

            default:
                return ['success' => false, 'error' => 'Unsupported action for Elementor adapter.'];
        }
    }
}
