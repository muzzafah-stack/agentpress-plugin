<?php
namespace AgentPress\Tools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Base Class for Tools
 */
abstract class BaseTool implements ToolInterface {

    /**
     * Helper to return structured success payload
     */
    protected function success_response(array $data): array {
        return [
            'success' => true,
            'tool'    => $this->get_name(),
            'data'    => $data,
        ];
    }

    /**
     * Helper to return structured error payload
     */
    protected function error_response(string $code, string $message): array {
        return [
            'success' => false,
            'tool'    => $this->get_name(),
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
    }

    /**
     * Basic required parameters validation
     */
    public function validate(array $params): array {
        $schema = $this->get_schema();
        $required = $schema['required'] ?? [];

        foreach ($required as $field) {
            if (!isset($params[$field]) || (is_string($params[$field]) && trim($params[$field]) === '')) {
                return [
                    'valid' => false,
                    'error' => sprintf('Missing required parameter `%s`.', $field)
                ];
            }
        }

        return ['valid' => true, 'error' => ''];
    }
}
