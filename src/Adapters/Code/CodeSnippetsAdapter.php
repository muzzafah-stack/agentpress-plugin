<?php
namespace AgentPress\Adapters\Code;

use AgentPress\Adapters\AdapterInterface;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapter for Code Snippets Plugin
 */
class CodeSnippetsAdapter implements AdapterInterface {

    public function get_id(): string {
        return 'code_snippets';
    }

    public function get_name(): string {
        return 'Code Snippets';
    }

    public function get_category(): string {
        return 'code';
    }

    public function is_available(): bool {
        return defined('CODE_SNIPPETS_VERSION') || function_exists('code_snippets');
    }

    public function get_capabilities(): array {
        return ['list_snippets', 'get_snippet', 'save_snippet', 'toggle_snippet'];
    }

    public function execute(string $action, array $params): array {
        global $wpdb;
        $table = $wpdb->prefix . 'snippets';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return ['success' => false, 'error' => 'Code Snippets database table does not exist.'];
        }

        switch ($action) {
            case 'list_snippets':
                $snippets = $wpdb->get_results("SELECT id, name, description, active, modified FROM {$table} ORDER BY id DESC");
                return ['success' => true, 'total' => count($snippets), 'snippets' => $snippets];

            case 'get_snippet':
                $id = (int) ($params['id'] ?? 0);
                $snippet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
                if (!$snippet) {
                    return ['success' => false, 'error' => 'Snippet not found.'];
                }
                return ['success' => true, 'snippet' => $snippet];

            case 'toggle_snippet':
                $id = (int) ($params['id'] ?? 0);
                $active = !empty($params['active']) ? 1 : 0;
                $updated = $wpdb->update($table, ['active' => $active], ['id' => $id], ['%d'], ['%d']);
                return ['success' => $updated !== false, 'id' => $id, 'active' => $active];

            default:
                return ['success' => false, 'error' => 'Unsupported action for Code Snippets adapter.'];
        }
    }
}
