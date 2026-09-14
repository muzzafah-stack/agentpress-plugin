<?php
namespace AgentPress\Tools\Admin;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One-Time Admin Magic Link Tool
 */
class OneTimeAdminLinkTool extends BaseTool {

    public function get_name(): string {
        return 'create_one_time_admin_link';
    }

    public function get_description(): string {
        return 'Generates a secure, single-use, auto-expiring login link for site administration.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_DESTRUCTIVE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'Target administrator user ID (defaults to current or first admin).',
                ],
                'expires_in_minutes' => [
                    'type' => 'integer',
                    'description' => 'Expiration window in minutes (default 15, max 60).',
                    'default' => 15,
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $user_id = isset($params['user_id']) ? (int) $params['user_id'] : 0;
        $expires = isset($params['expires_in_minutes']) ? (int) $params['expires_in_minutes'] : 15;

        if (!$user_id) {
            $admins = get_users(['role' => 'administrator', 'number' => 1]);
            if (!empty($admins)) {
                $user_id = $admins[0]->ID;
            }
        }

        if (!$user_id) {
            return $this->error_response('no_admin_found', 'No administrator user found to generate magic link.');
        }

        $one_time_login = Plugin::get_instance()->one_time_login;
        $result = $one_time_login->generate_link($user_id, $expires);

        if (!$result['success']) {
            return $this->error_response('generation_failed', $result['error']);
        }

        return $this->success_response($result);
    }
}
