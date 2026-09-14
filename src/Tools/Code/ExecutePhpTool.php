<?php
namespace AgentPress\Tools\Code;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Execute PHP Tool (Safe, Isolated WordPress Context Runner)
 */
class ExecutePhpTool extends BaseTool {

    public function get_name(): string {
        return 'execute_php';
    }

    public function get_description(): string {
        return 'Executes PHP code snippet safely in WordPress context with error interception, output buffering, and dangerous function filtering.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_CODE_EXECUTION;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'string',
                    'description' => 'PHP code to execute. Can be a snippet or function calls.',
                ],
                'timeout' => [
                    'type' => 'integer',
                    'description' => 'Execution timeout in seconds (default 15, max 60).',
                    'default' => 15,
                ],
            ],
            'required' => ['code'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $settings = get_option('agentpress_settings', []);
        if (empty($settings['enable_php_exec'])) {
            return $this->error_response(
                'php_exec_disabled',
                'PHP execution is currently disabled in AgentPress Security Settings. An administrator must enable it in the WordPress admin dashboard.'
            );
        }

        $code = (string) $params['code'];
        $timeout = min(60, max(1, (int) ($params['timeout'] ?? ($settings['php_timeout'] ?? 15))));

        $sanitizer = Plugin::get_instance()->sanitizer;
        $safety = $sanitizer->check_php_code_safety($code);

        if (!$safety['is_safe']) {
            return $this->error_response('security_violation', $safety['error']);
        }

        // Clean opening PHP tags
        $clean_code = preg_replace('/^\s*<\?(php)?/i', '', $code);
        $clean_code = preg_replace('/\?>\s*$/i', '', $clean_code);

        // Captured warnings and errors array
        $runtime_errors = [];
        $custom_error_handler = function ($errno, $errstr, $errfile, $errline) use (&$runtime_errors) {
            $runtime_errors[] = [
                'type'    => $errno,
                'message' => $errstr,
                'line'    => $errline,
            ];
            return true;
        };

        set_error_handler($custom_error_handler);
        @set_time_limit($timeout);

        ob_start();
        $return_value = null;
        $exception_error = null;

        try {
            // Execute in local scope
            $return_value = eval($clean_code);
        } catch (\Throwable $e) {
            $exception_error = [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => basename($e->getFile()),
            ];
        }

        $output = ob_get_clean();
        restore_error_handler();

        if ($exception_error !== null) {
            return $this->error_response(
                'runtime_exception',
                sprintf('PHP Exception: %s (Line %d)', $exception_error['message'], $exception_error['line'])
            );
        }

        return $this->success_response([
            'output'         => $output,
            'return_value'   => $return_value,
            'runtime_notices'=> $runtime_errors,
            'timeout_limit'  => $timeout,
        ]);
    }
}
