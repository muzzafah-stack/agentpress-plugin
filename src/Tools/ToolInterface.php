<?php
namespace AgentPress\Tools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standard Interface for AgentPress Tools (MCP Compatible)
 */
interface ToolInterface {

    /**
     * Get unique tool identifier name (e.g. 'read_file', 'execute_php')
     */
    public function get_name(): string;

    /**
     * Get human/LLM-readable tool description
     */
    public function get_description(): string;

    /**
     * Required capability scope ('read', 'write', 'destructive', 'code_execution')
     */
    public function get_required_permission(): string;

    /**
     * Get JSON Schema for tool parameters (MCP / OpenAI / Anthropic format)
     */
    public function get_schema(): array;

    /**
     * Validate input arguments
     *
     * @param array $params
     * @return array ['valid' => bool, 'error' => string]
     */
    public function validate(array $params): array;

    /**
     * Execute tool logic
     *
     * @param array $params Validated input parameters
     * @param object|null $token Authenticated token object
     * @return array Standard structured response payload
     */
    public function execute(array $params, ?object $token = null): array;
}
