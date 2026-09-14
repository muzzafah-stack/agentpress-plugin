<?php
namespace AgentPress\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PSR-4 Compliant Autoloader for AgentPress
 */
class Autoloader {

    /**
     * Register autoloader
     */
    public static function register(): void {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload callback
     *
     * @param string $class Full class name with namespace.
     */
    public static function autoload(string $class): void {
        $prefix = 'AgentPress\\';
        $base_dir = AGENTPRESS_PATH . 'src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
