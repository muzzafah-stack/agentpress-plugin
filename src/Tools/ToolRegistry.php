<?php
namespace AgentPress\Tools;

use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registry and Dispatcher for AgentPress Tools
 */
class ToolRegistry {

    /**
     * Registered tools map: [name => ToolInterface]
     *
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    /**
     * Register a new tool
     */
    public function register_tool(ToolInterface $tool): void {
        $this->tools[$tool->get_name()] = $tool;
    }

    /**
     * Get a specific tool by name
     */
    public function get_tool(string $name): ?ToolInterface {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered tools
     *
     * @return array<string, ToolInterface>
     */
    public function get_all_tools(): array {
        return $this->tools;
    }

    /**
     * Export tools formatted for MCP / LLM Tool Discovery
     */
    public function export_tools_schema(?object $token = null): array {
        $plugin = Plugin::get_instance();
        $exported = [];
        $active_tools = get_option('agentpress_active_tools', []);

        foreach ($this->tools as $name => $tool) {
            // Check if disabled by admin
            if (isset($active_tools[$name]) && $active_tools[$name] === false) {
                continue;
            }

            // Check if token has permission to discover/execute this tool
            $required_scope = $tool->get_required_permission();
            if ($token && !$plugin->permission_manager->has_permission($token, $required_scope)) {
                continue;
            }

            $exported[] = [
                'name'        => $tool->get_name(),
                'description' => $tool->get_description(),
                'inputSchema' => $tool->get_schema(),
                'permission'  => $required_scope,
            ];
        }

        return $exported;
    }

    /**
     * Execute a tool by name with safety checks and audit logging
     *
     * @param string $tool_name
     * @param array $params
     * @param object|null $token
     * @return array
     */
    public function execute_tool(string $tool_name, array $params, ?object $token = null): array {
        $start_time = microtime(true);
        $plugin = Plugin::get_instance();

        $tool = $this->get_tool($tool_name);
        if (!$tool) {
            $execution_time = (int) round((microtime(true) - $start_time) * 1000);
            $plugin->audit_logger->log($token->id ?? null, $tool_name, 'error', $params, null, 'Tool not found', $execution_time);
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'tool_not_found',
                    'message' => sprintf('Tool `%s` is not registered or supported.', esc_html($tool_name)),
                ]
            ];
        }

        // Check if tool is disabled in settings
        $active_tools = get_option('agentpress_active_tools', []);
        if (isset($active_tools[$tool_name]) && $active_tools[$tool_name] === false) {
            $execution_time = (int) round((microtime(true) - $start_time) * 1000);
            $plugin->audit_logger->log($token->id ?? null, $tool_name, 'denied', $params, null, 'Tool disabled by administrator', $execution_time);
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'tool_disabled',
                    'message' => sprintf('Tool `%s` has been disabled by site administrator.', esc_html($tool_name)),
                ]
            ];
        }

        // Check permissions
        $required_scope = $tool->get_required_permission();
        if (!$plugin->permission_manager->has_permission($token, $required_scope)) {
            $execution_time = (int) round((microtime(true) - $start_time) * 1000);
            $plugin->audit_logger->log($token->id ?? null, $tool_name, 'denied', $params, null, 'Insufficient permission scope: ' . $required_scope, $execution_time);
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'permission_denied',
                    'message' => sprintf('Token lacks required permission scope `%s` for tool `%s`.', $required_scope, $tool_name),
                ]
            ];
        }

        // Validate parameters
        $validation = $tool->validate($params);
        if (!$validation['valid']) {
            $execution_time = (int) round((microtime(true) - $start_time) * 1000);
            $plugin->audit_logger->log($token->id ?? null, $tool_name, 'error', $params, null, $validation['error'], $execution_time);
            return [
                'success' => false,
                'error'   => [
                    'code'    => 'invalid_parameters',
                    'message' => $validation['error'],
                ]
            ];
        }

        // Execute tool inside try-catch
        try {
            $response = $tool->execute($params, $token);
            $execution_time = (int) round((microtime(true) - $start_time) * 1000);
            $status = ($response['success'] ?? false) ? 'success' : 'error';
            $error_msg = $response['error']['message'] ?? null;

            $plugin->audit_logger->log($token->id ?? null, $tool_name, $status, $params, $response, $error_msg, $execution_time);
            $response['execution_time_ms'] = $execution_time;

            return $response;
        } catch (\Throwable $e) {
            $execution_time = (int) round((microtime(true) - $start_time) * 1000);
            $plugin->audit_logger->log($token->id ?? null, $tool_name, 'error', $params, null, $e->getMessage(), $execution_time);

            return [
                'success' => false,
                'error'   => [
                    'code'    => 'execution_exception',
                    'message' => 'Runtime error during tool execution: ' . $e->getMessage(),
                ],
                'execution_time_ms' => $execution_time,
            ];
        }
    }

    /**
     * Register all standard V1 tools
     */
    public function register_default_tools(): void {
        // Filesystem Tools
        $this->register_tool(new Filesystem\ReadFileTool());
        $this->register_tool(new Filesystem\WriteFileTool());
        $this->register_tool(new Filesystem\EditFileTool());
        $this->register_tool(new Filesystem\DeleteFileTool());
        $this->register_tool(new Filesystem\DisableFileTool());
        $this->register_tool(new Filesystem\EnableFileTool());
        $this->register_tool(new Filesystem\ListDirectoryTool());

        // Code Execution Tools
        $this->register_tool(new Code\ExecutePhpTool());
        $this->register_tool(new Code\ExecuteWpCliTool());

        // Content & Upload Tools
        $this->register_tool(new Content\GutenbergBlockTool());
        $this->register_tool(new Content\TemporaryUploadTool());

        // Admin, Memory & Skills Tools
        $this->register_tool(new Admin\OneTimeAdminLinkTool());
        $this->register_tool(new Admin\ManageMemoryTool());
        $this->register_tool(new Admin\ManageSkillsTool());

        // Adapter Dynamic Tools
        $this->register_tool(new Adapters\SeoTool());
        $this->register_tool(new Adapters\BuilderTool());
    }
}
