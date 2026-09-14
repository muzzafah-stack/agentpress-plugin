<?php
namespace AgentPress\Tools\Code;

use AgentPress\Tools\BaseTool;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Execute WP-CLI Tool (Strict Allowlist Runner)
 */
class ExecuteWpCliTool extends BaseTool {

    /**
     * Allowed top-level WP-CLI commands
     */
    private const ALLOWED_COMMANDS = [
        'plugin',
        'theme',
        'post',
        'post-type',
        'taxonomy',
        'term',
        'comment',
        'user',
        'option',
        'cache',
        'transient',
        'rewrite',
        'cron',
        'site',
        'db',
        'maintenance-mode',
        'eval',
    ];

    public function get_name(): string {
        return 'execute_wp_cli';
    }

    public function get_description(): string {
        return 'Executes an allowed WP-CLI command with arguments, capturing stdout, stderr, and exit code.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_CODE_EXECUTION;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'Main WP-CLI command (e.g. "plugin list", "cache flush", "post list --format=json").',
                ],
                'args' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Optional array of command arguments.',
                    'default' => [],
                ],
            ],
            'required' => ['command'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $settings = get_option('agentpress_settings', []);
        if (empty($settings['enable_wp_cli'])) {
            return $this->error_response(
                'wp_cli_disabled',
                'WP-CLI execution is disabled in AgentPress Security Settings.'
            );
        }

        $command_raw = trim((string) $params['command']);
        $args = (array) ($params['args'] ?? []);

        // Parse root sub-command
        $parts = preg_split('/\s+/', $command_raw);
        $root_cmd = strtolower($parts[0] ?? '');

        if (!in_array($root_cmd, self::ALLOWED_COMMANDS, true)) {
            return $this->error_response(
                'command_not_allowed',
                sprintf('WP-CLI command `%s` is not in the allowed command whitelist.', esc_html($root_cmd))
            );
        }

        // Check if WP_CLI class is native in context
        if (class_exists('\WP_CLI') && method_exists('\WP_CLI', 'runcommand')) {
            try {
                $options = [
                    'return'     => true,
                    'parse'      => 'json',
                    'launch'     => false,
                    'exit_error' => false,
                ];
                $output = \WP_CLI::runcommand($command_raw, $options);

                return $this->success_response([
                    'command'   => $command_raw,
                    'stdout'    => $output,
                    'exit_code' => 0,
                ]);
            } catch (\Throwable $e) {
                return $this->error_response('wp_cli_exception', $e->getMessage());
            }
        }

        // Fallback: Check if shell_exec / proc_open is available on system
        if (!function_exists('proc_open')) {
            return $this->error_response(
                'cli_unsupported',
                'WP-CLI native runtime is not active and PHP proc_open() is disabled on this server.'
            );
        }

        // Sanitize and build CLI command
        $wp_bin = defined('WP_CLI_BIN_PATH') ? WP_CLI_BIN_PATH : 'wp';
        $full_cmd = escapeshellcmd($wp_bin . ' ' . $command_raw . ' --path=' . ABSPATH . ' --allow-root');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($full_cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return $this->error_response('proc_open_failed', 'Failed to initialize WP-CLI process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exit_code = proc_close($process);

        return $this->success_response([
            'command'   => $command_raw,
            'stdout'    => $stdout,
            'stderr'    => $stderr,
            'exit_code' => $exit_code,
        ]);
    }
}
