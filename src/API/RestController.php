<?php
namespace AgentPress\API;

use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API Controller for AgentPress
 */
class RestController {

    private const NAMESPACE = 'agentpress/v1';

    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // 1. Connection Handshake
        register_rest_route(self::NAMESPACE, '/connection', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_connection'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);

        // 2. Tools Discovery (MCP schema compliant)
        register_rest_route(self::NAMESPACE, '/tools', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_get_tools'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);

        // 3. Tool Execution
        register_rest_route(self::NAMESPACE, '/tools/(?P<tool>[a-zA-Z0-9_-]+)', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_execute_tool'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);

        // 4. Project Memory
        register_rest_route(self::NAMESPACE, '/memory', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle_get_memory'],
                'permission_callback' => [$this, 'authenticate_request'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handle_save_memory'],
                'permission_callback' => [$this, 'authenticate_request'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/memory/(?P<key>[a-zA-Z0-9_-]+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'handle_delete_memory'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);

        // 5. Skills
        register_rest_route(self::NAMESPACE, '/skills', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle_get_skills'],
                'permission_callback' => [$this, 'authenticate_request'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handle_save_skill'],
                'permission_callback' => [$this, 'authenticate_request'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/skills/(?P<slug>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_get_single_skill'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);

        // 6. Audit Logs
        register_rest_route(self::NAMESPACE, '/logs', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_get_logs'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);

        // 7. Temporary File Upload Receiver
        register_rest_route(self::NAMESPACE, '/upload/file', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_file_upload'],
            'permission_callback' => '__return_true', // Validated via temporary upload token
        ]);

        // 8. One-Time Admin Magic Link Generator
        register_rest_route(self::NAMESPACE, '/auth/one-time-admin', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_one_time_admin'],
            'permission_callback' => [$this, 'authenticate_request'],
        ]);
    }

    /**
     * Authenticate incoming REST API request via Bearer Token or Header Key
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function authenticate_request(WP_REST_Request $request) {
        $plugin = Plugin::get_instance();

        // Extract token from Authorization: Bearer <token> or X-AgentPress-Key
        $auth_header = $request->get_header('authorization');
        $raw_token = '';

        if (!empty($auth_header) && preg_match('/Bearer\s+(\S+)/i', $auth_header, $matches)) {
            $raw_token = $matches[1];
        }

        if (empty($raw_token)) {
            $raw_token = $request->get_header('x-agentpress-key');
        }

        if (empty($raw_token)) {
            $raw_token = $request->get_param('api_key');
        }

        if (empty($raw_token)) {
            return new WP_Error(
                'agentpress_unauthorized',
                'Missing AgentPress API Token in Authorization header or X-AgentPress-Key.',
                ['status' => 401]
            );
        }

        $token = $plugin->token_manager->validate_token($raw_token);
        if (!$token) {
            return new WP_Error(
                'agentpress_invalid_token',
                'Invalid, revoked, or expired AgentPress API Token.',
                ['status' => 401]
            );
        }

        // Check Rate Limiting
        $client_ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rate_check = $plugin->rate_limiter->check($client_ip . '_' . $token->id);

        if (!$rate_check['allowed']) {
            return new WP_Error(
                'agentpress_rate_limited',
                sprintf('Rate limit exceeded. Please retry after %d seconds.', $rate_check['retry_after']),
                ['status' => 429]
            );
        }

        // Attach token object to request attributes for downstream handlers
        $request->set_param('_agentpress_token', $token);

        return true;
    }

    /**
     * Helper to get authenticated token from request
     */
    private function get_request_token(WP_REST_Request $request): ?object {
        return $request->get_param('_agentpress_token');
    }

    /**
     * GET /connection
     */
    public function handle_connection(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $token = $this->get_request_token($request);

        $available_adapters = array_keys($plugin->adapter_registry->get_available());

        return new WP_REST_Response([
            'status'           => 'connected',
            'version'          => AGENTPRESS_VERSION,
            'site_name'        => get_bloginfo('name'),
            'site_url'         => site_url(),
            'wp_version'       => get_bloginfo('version'),
            'php_version'      => phpversion(),
            'token_name'       => $token->name ?? '',
            'permissions'      => $token->permissions_array ?? [],
            'sandbox_path'     => $plugin->sandbox::get_sandbox_root(),
            'active_adapters'  => $available_adapters,
            'server_time'      => current_time('mysql', true),
        ], 200);
    }

    /**
     * GET /tools
     */
    public function handle_get_tools(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $token = $this->get_request_token($request);
        $tools = $plugin->tool_registry->export_tools_schema($token);

        return new WP_REST_Response([
            'tools' => $tools,
        ], 200);
    }

    /**
     * POST /tools/{tool}
     */
    public function handle_execute_tool(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $tool_name = sanitize_text_field($request->get_param('tool'));
        $token = $this->get_request_token($request);
        $params = $request->get_json_params() ?: $request->get_body_params() ?: [];

        $result = $plugin->tool_registry->execute_tool($tool_name, $params, $token);
        $status_code = ($result['success'] ?? false) ? 200 : 400;

        if (isset($result['error']['code']) && $result['error']['code'] === 'permission_denied') {
            $status_code = 403;
        }

        return new WP_REST_Response($result, $status_code);
    }

    /**
     * GET /memory
     */
    public function handle_get_memory(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $scope = sanitize_text_field($request->get_param('scope') ?: '');
        $items = $plugin->memory_manager->list($scope ?: null);

        return new WP_REST_Response([
            'total' => count($items),
            'items' => $items,
        ], 200);
    }

    /**
     * POST /memory
     */
    public function handle_save_memory(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $token = $this->get_request_token($request);

        if (!$plugin->permission_manager->has_permission($token, PermissionManager::SCOPE_WRITE)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Write permission required.'], 403);
        }

        $params = $request->get_json_params() ?: [];
        $key = sanitize_key($params['key'] ?? '');
        $value = $params['value'] ?? null;
        $scope = sanitize_text_field($params['scope'] ?? 'project');
        $is_locked = !empty($params['is_locked']);

        if (empty($key) || !isset($value)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Parameters `key` and `value` are required.'], 400);
        }

        $saved = $plugin->memory_manager->set($key, $value, $scope, $is_locked, 'ai_agent');
        return new WP_REST_Response(['success' => $saved, 'key' => $key], $saved ? 200 : 400);
    }

    /**
     * DELETE /memory/{key}
     */
    public function handle_delete_memory(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $token = $this->get_request_token($request);

        if (!$plugin->permission_manager->has_permission($token, PermissionManager::SCOPE_WRITE)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Write permission required.'], 403);
        }

        $key = sanitize_key($request->get_param('key'));
        $deleted = $plugin->memory_manager->delete($key);

        return new WP_REST_Response(['success' => $deleted, 'key' => $key], 200);
    }

    /**
     * GET /skills
     */
    public function handle_get_skills(WP_REST_Request $request): WP_REST_Response {
        $skills = Plugin::get_instance()->skill_manager->list_skills();
        return new WP_REST_Response(['total' => count($skills), 'skills' => $skills], 200);
    }

    /**
     * GET /skills/{slug}
     */
    public function handle_get_single_skill(WP_REST_Request $request): WP_REST_Response {
        $slug = sanitize_title($request->get_param('slug'));
        $skill = Plugin::get_instance()->skill_manager->get_skill($slug);

        if (!$skill) {
            return new WP_REST_Response(['error' => 'Skill not found.'], 404);
        }

        return new WP_REST_Response($skill, 200);
    }

    /**
     * POST /skills
     */
    public function handle_save_skill(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $token = $this->get_request_token($request);

        if (!$plugin->permission_manager->has_permission($token, PermissionManager::SCOPE_WRITE)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Write permission required.'], 403);
        }

        $params = $request->get_json_params() ?: [];
        $slug = sanitize_title($params['slug'] ?? '');
        $content = (string) ($params['content'] ?? '');

        if (empty($slug) || empty($content)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Parameters `slug` and `content` are required.'], 400);
        }

        $saved = $plugin->skill_manager->save_skill($slug, $content);
        return new WP_REST_Response(['success' => $saved, 'slug' => $slug], $saved ? 200 : 400);
    }

    /**
     * GET /logs
     */
    public function handle_get_logs(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $limit = min(100, max(1, (int) ($request->get_param('limit') ?: 50)));
        $offset = max(0, (int) ($request->get_param('offset') ?: 0));
        $status = $request->get_param('status');
        $tool = $request->get_param('tool');

        $logs = $plugin->audit_logger->get_logs($limit, $offset, $status, $tool);
        return new WP_REST_Response(['total' => count($logs), 'logs' => $logs], 200);
    }

    /**
     * POST /upload/file
     */
    public function handle_file_upload(WP_REST_Request $request): WP_REST_Response {
        $upload_token = sanitize_text_field($request->get_param('upload_token'));
        if (empty($upload_token)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Missing upload token.'], 401);
        }

        $token_hash = hash('sha256', $upload_token);
        $transient_key = 'agentpress_upl_' . $token_hash;
        $payload = get_transient($transient_key);

        if (!$payload) {
            return new WP_REST_Response(['success' => false, 'error' => 'Upload token is invalid or expired.'], 403);
        }

        // Invalidate single use token
        delete_transient($transient_key);

        $files = $request->get_file_params();
        if (empty($files) || !isset($files['file'])) {
            return new WP_REST_Response(['success' => false, 'error' => 'No file uploaded in `file` multipart field.'], 400);
        }

        $file = $files['file'];
        $filename = sanitize_file_name($payload['filename'] ?: $file['name']);
        $target_type = $payload['target_type'] ?? 'sandbox_file';

        $plugin = Plugin::get_instance();
        $sandbox_root = $plugin->sandbox::get_sandbox_root();

        $destination = $sandbox_root . '/' . $filename;
        $moved = @move_uploaded_file($file['tmp_name'], $destination);

        if (!$moved) {
            return new WP_REST_Response(['success' => false, 'error' => 'Failed to move uploaded file.'], 500);
        }

        return new WP_REST_Response([
            'success'     => true,
            'filename'    => $filename,
            'target_type' => $target_type,
            'size_bytes'  => filesize($destination),
            'stored_path' => $filename,
        ], 200);
    }

    /**
     * POST /auth/one-time-admin
     */
    public function handle_one_time_admin(WP_REST_Request $request): WP_REST_Response {
        $plugin = Plugin::get_instance();
        $token = $this->get_request_token($request);

        if (!$plugin->permission_manager->has_permission($token, PermissionManager::SCOPE_DESTRUCTIVE)) {
            return new WP_REST_Response(['success' => false, 'error' => 'Destructive permission required.'], 403);
        }

        $params = $request->get_json_params() ?: [];
        $user_id = (int) ($params['user_id'] ?? 0);
        $expires = (int) ($params['expires_in_minutes'] ?? 15);

        $result = $plugin->tool_registry->execute_tool('create_one_time_admin_link', [
            'user_id'            => $user_id,
            'expires_in_minutes' => $expires,
        ], $token);

        return new WP_REST_Response($result, ($result['success'] ?? false) ? 200 : 400);
    }
}
