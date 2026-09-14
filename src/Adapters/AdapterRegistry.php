<?php
namespace AgentPress\Adapters;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registry for Third-Party Plugin & Builder Adapters
 */
class AdapterRegistry {

    /**
     * @var array<string, AdapterInterface>
     */
    private array $adapters = [];

    /**
     * Register an adapter
     */
    public function register_adapter(AdapterInterface $adapter): void {
        $this->adapters[$adapter->get_id()] = $adapter;
    }

    /**
     * Get adapter by ID
     */
    public function get_adapter(string $id): ?AdapterInterface {
        return $this->adapters[$id] ?? null;
    }

    /**
     * Get all registered adapters
     *
     * @return array<string, AdapterInterface>
     */
    public function get_all(): array {
        return $this->adapters;
    }

    /**
     * Get all available (active on WordPress) adapters
     */
    public function get_available(?string $category = null): array {
        $available = [];
        foreach ($this->adapters as $id => $adapter) {
            if ($adapter->is_available()) {
                if ($category === null || $adapter->get_category() === $category) {
                    $available[$id] = $adapter;
                }
            }
        }
        return $available;
    }

    /**
     * Register default V1 adapters
     */
    public function register_default_adapters(): void {
        $this->register_adapter(new Seo\RankMathAdapter());
        $this->register_adapter(new Seo\SeoPressAdapter());
        $this->register_adapter(new Seo\SlimSeoAdapter());
        $this->register_adapter(new Builders\ElementorAdapter());
        $this->register_adapter(new Code\CodeSnippetsAdapter());
    }
}
