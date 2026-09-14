<?php
namespace AgentPress\Security;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages Scopes and Capabilities for Tools
 */
class PermissionManager {

    const SCOPE_READ = 'read';
    const SCOPE_WRITE = 'write';
    const SCOPE_DESTRUCTIVE = 'destructive';
    const SCOPE_CODE_EXECUTION = 'code_execution';

    /**
     * Get all available capability scopes
     */
    public static function get_all_scopes(): array {
        return [
            self::SCOPE_READ => __('Read (Inspect files, memory, skills, site status)', 'agentpress'),
            self::SCOPE_WRITE => __('Write (Modify files, Gutenberg blocks, write memory, skills)', 'agentpress'),
            self::SCOPE_DESTRUCTIVE => __('Destructive (Delete files, wipe memory, admin magic link)', 'agentpress'),
            self::SCOPE_CODE_EXECUTION => __('Code Execution (Run isolated PHP snippets & WP-CLI)', 'agentpress'),
        ];
    }

    /**
     * Check if a token has required permission scope
     *
     * @param object|null $token Token object from TokenManager
     * @param string $required_scope Required permission constant
     * @return bool
     */
    public function has_permission(?object $token, string $required_scope): bool {
        if (!$token) {
            return false;
        }

        $scopes = $token->permissions_array ?? (json_decode($token->permissions, true) ?: []);

        // Superadmin scope
        if (in_array('admin', $scopes, true) || in_array('all', $scopes, true)) {
            return true;
        }

        return in_array($required_scope, $scopes, true);
    }
}
