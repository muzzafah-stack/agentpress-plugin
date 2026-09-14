<?php
namespace AgentPress\Core;

use AgentPress\Database\Migrator;
use AgentPress\Auth\TokenManager;
use AgentPress\Auth\OneTimeLogin;
use AgentPress\Security\PermissionManager;
use AgentPress\Security\Sanitizer;
use AgentPress\Security\RateLimiter;
use AgentPress\Logging\AuditLogger;
use AgentPress\Memory\MemoryManager;
use AgentPress\Skills\SkillManager;
use AgentPress\Filesystem\Sandbox;
use AgentPress\Tools\ToolRegistry;
use AgentPress\Adapters\AdapterRegistry;
use AgentPress\API\RestController;
use AgentPress\Admin\AdminMenu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main AgentPress Plugin Coordinator (Singleton)
 */
class Plugin {

    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?Plugin $instance = null;

    /**
     * Service Containers
     */
    public TokenManager $token_manager;
    public OneTimeLogin $one_time_login;
    public PermissionManager $permission_manager;
    public Sanitizer $sanitizer;
    public RateLimiter $rate_limiter;
    public AuditLogger $audit_logger;
    public MemoryManager $memory_manager;
    public SkillManager $skill_manager;
    public Sandbox $sandbox;
    public ToolRegistry $tool_registry;
    public AdapterRegistry $adapter_registry;
    public RestController $rest_controller;
    public ?AdminMenu $admin_menu = null;

    /**
     * Get singleton instance
     */
    public static function get_instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct() {
        $this->init_services();
        $this->init_hooks();
    }

    /**
     * Initialize all services
     */
    private function init_services(): void {
        $this->token_manager = new TokenManager();
        $this->one_time_login = new OneTimeLogin();
        $this->permission_manager = new PermissionManager();
        $this->sanitizer = new Sanitizer();
        $this->rate_limiter = new RateLimiter();
        $this->audit_logger = new AuditLogger();
        $this->memory_manager = new MemoryManager();
        $this->skill_manager = new SkillManager();
        $this->sandbox = new Sandbox();
        $this->tool_registry = new ToolRegistry();
        $this->adapter_registry = new AdapterRegistry();
        $this->rest_controller = new RestController();

        if (is_admin()) {
            $this->admin_menu = new AdminMenu();
        }
    }

    /**
     * Register WordPress hooks
     */
    private function init_hooks(): void {
        // Run DB schema check if needed
        add_action('init', [$this, 'check_database_version'], 5);

        // One-time magic link login handler
        add_action('init', [$this->one_time_login, 'handle_login_attempt'], 1);

        // REST API routes registration
        add_action('rest_api_init', [$this->rest_controller, 'register_routes']);

        // Register default tools & adapters
        add_action('init', [$this, 'bootstrap_tools_and_adapters'], 10);
    }

    /**
     * Check and run database migrations on update
     */
    public function check_database_version(): void {
        $installed_ver = get_option('agentpress_db_version', '0.0.0');
        if (version_compare($installed_ver, AGENTPRESS_VERSION, '<')) {
            Migrator::migrate();
        }
    }

    /**
     * Register core tools and adapters
     */
    public function bootstrap_tools_and_adapters(): void {
        // Register Adapters
        $this->adapter_registry->register_default_adapters();

        // Register Tools
        $this->tool_registry->register_default_tools();
    }
}
