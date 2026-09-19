<?php
namespace AgentPress\Security;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Sanitizer, Path Normalizer, Secret Redactor, and Code Checker
 */
class Sanitizer {

    /**
     * Dangerous PHP functions forbidden in safe execution
     */
    private const DANGEROUS_PHP_FUNCTIONS = [
        'exec',
        'system',
        'passthru',
        'shell_exec',
        'proc_open',
        'popen',
        'pcntl_exec',
        'dl',
        'disk_free_space',
        'disk_total_space',
        'posix_getpwuid',
        'posix_kill',
        'posix_setuid',
        'apache_child_terminate',
        'curl_multi_exec',
        'parse_ini_file',
        'show_source',
    ];

    /**
     * Keys containing sensitive secrets to be redacted in logs
     */
    private const SECRET_KEY_PATTERNS = [
        'token',
        'password',
        'secret',
        'api_key',
        'apikey',
        'auth_key',
        'sec_key',
        'nonce',
        'db_password',
    ];

    /**
     * Normalize and validate path inside a base directory
     *
     * @param string $path Target relative or absolute path
     * @param string $base_directory Allowed base directory
     * @return string|false Normalized absolute path if safe, false if traversal attempt or outside base
     */
    public function validate_sandbox_path(string $path, string $base_directory) {
        // Strip null bytes
        $path = str_replace(chr(0), '', $path);
        $path = trim($path);

        if (empty($path)) {
            return false;
        }

        // Ensure base directory is real and normalized
        $real_base = realpath($base_directory);
        if (!$real_base || !is_dir($real_base)) {
            return false;
        }

        // Handle both relative and absolute paths
        if (strpos($path, $real_base) === 0) {
            $target = $path;
        } else {
            $target = $real_base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        }

        // Normalize separators
        $target = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $target);
        $real_base = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $real_base);

        $real_target = realpath($target);
        if ($real_target !== false) {
            if (strpos($real_target, $real_base . DIRECTORY_SEPARATOR) === 0 || $real_target === $real_base) {
                return $real_target;
            }
            return false;
        }

        // For non-existent files (e.g. write_file in new directory), walk up to find nearest real parent
        $parent = dirname($target);
        $real_parent = realpath($parent);

        while ($real_parent === false && $parent !== dirname($parent)) {
            $parent = dirname($parent);
            $real_parent = realpath($parent);
        }

        if ($real_parent !== false) {
            if (strpos($real_parent, $real_base . DIRECTORY_SEPARATOR) === 0 || $real_parent === $real_base) {
                // Ensure the remaining non-existent path doesn't contain directory traversal
                $remainder = substr($target, strlen($parent));
                $remainder = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $remainder);
                $parts = explode(DIRECTORY_SEPARATOR, $remainder);
                foreach ($parts as $part) {
                    if ($part === '..') {
                        return false;
                    }
                }
                return $target;
            }
        }

        return false;
    }

    /**
     * Check if PHP code contains forbidden functions or syntax errors
     *
     * @param string $code Raw PHP code
     * @return array ['is_safe' => bool, 'error' => string]
     */
    public function check_php_code_safety(string $code): array {
        // Wrap in PHP tags if missing for tokenizer
        $tokens_code = (strpos($code, '<?php') === false) ? "<?php\n" . $code : $code;

        try {
            $tokens = @token_get_all($tokens_code);
        } catch (\Throwable $e) {
            return [
                'is_safe' => false,
                'error'   => 'PHP syntax parsing failed: ' . $e->getMessage()
            ];
        }

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $token_type = $token[0];
                $token_value = strtolower(trim($token[1]));

                // Check function calls
                if ($token_type === T_STRING) {
                    if (in_array($token_value, self::DANGEROUS_PHP_FUNCTIONS, true)) {
                        return [
                            'is_safe' => false,
                            'error'   => sprintf('Security violation: Forbidden dangerous function `%s()` detected.', $token_value)
                        ];
                    }
                }

                // Check backtick execution operator
                if ($token_type === T_ENCAPSED_AND_WHITESPACE && strpos($token[1], '`') !== false) {
                    return [
                        'is_safe' => false,
                        'error'   => 'Security violation: Execution backticks operator is strictly forbidden.'
                    ];
                }
            } else if (is_string($token) && $token === '`') {
                return [
                    'is_safe' => false,
                    'error'   => 'Security violation: Execution backticks operator is strictly forbidden.'
                ];
            }
        }

        return ['is_safe' => true, 'error' => ''];
    }

    /**
     * Redact sensitive secrets from request / response payloads before logging
     *
     * @param mixed $data Array, object, or string
     * @return mixed
     */
    public function redact_secrets($data) {
        if (is_array($data)) {
            $redacted = [];
            foreach ($data as $k => $v) {
                $is_secret = false;
                $key_lower = strtolower((string)$k);

                foreach (self::SECRET_KEY_PATTERNS as $pattern) {
                    if (strpos($key_lower, $pattern) !== false) {
                        $is_secret = true;
                        break;
                    }
                }

                if ($is_secret) {
                    $redacted[$k] = '***REDACTED***';
                } else {
                    $redacted[$k] = $this->redact_secrets($v);
                }
            }
            return $redacted;
        }

        if (is_object($data)) {
            return (object) $this->redact_secrets((array) $data);
        }

        return $data;
    }
}
