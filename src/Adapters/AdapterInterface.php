<?php
namespace AgentPress\Adapters;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standard Interface for 3rd-Party Plugin / Builder Adapters
 */
interface AdapterInterface {

    /**
     * Unique identifier for the adapter (e.g. 'rank_math', 'elementor', 'code_snippets')
     */
    public function get_id(): string;

    /**
     * Human readable name
     */
    public function get_name(): string;

    /**
     * Category ('seo', 'builder', 'code', 'ecommerce', etc.)
     */
    public function get_category(): string;

    /**
     * Check if the target third-party plugin is currently active and available
     */
    public function is_available(): bool;

    /**
     * Get list of capabilities/actions supported by this adapter
     */
    public function get_capabilities(): array;

    /**
     * Execute an action via the adapter
     *
     * @param string $action Action name
     * @param array $params Parameter payload
     * @return array Result payload
     */
    public function execute(string $action, array $params): array;
}
