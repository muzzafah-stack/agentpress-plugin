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
        'posix_setgid',
        'posix_seteuid',
        'posix_setegid',
        'posix_mkfifo',
        'apache_child_terminate',
        'curl_multi_exec',
        'parse_ini_file',
        'show_source',
        'highlight_file',
        'phpinfo',
        'putenv',
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
        'authorization',
        'bearer',
        'private_key',
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

        if ($path === '') {
            return false;
        }

        // Ensure base directory is real and normalized
        $real_base = realpath($base_directory);
        if (!$real_base || !is_dir($real_base)) {
            return false;
        }

        // Normalize base path and establish base boundary prefix
        $real_base = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $real_base);
        $base_prefix = rtrim($real_base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Normalize path separators
        $normalized_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Disallow Alternate Data Streams (ADS) or invalid colons
        if (DIRECTORY_SEPARATOR === '\\') {
            $path_without_drive = preg_replace('/^[a-zA-Z]:/', '', $normalized_path);
            if (strpos($path_without_drive, ':') !== false) {
                return false;
            }
        } elseif (strpos($normalized_path, ':') !== false) {
            return false;
        }

        // Check if path is already absolute within base boundary
        if ($normalized_path === $real_base || strpos($normalized_path, $base_prefix) === 0) {
            $target = $normalized_path;
        } elseif (preg_match('/^[a-zA-Z]:/', $normalized_path)) {
            // Windows absolute path on a different drive or directory outside base
            return false;
        } else {
            $target = $base_prefix . ltrim($normalized_path, DIRECTORY_SEPARATOR);
        }

        // If file or target directory already exists, verify its realpath directly
        $real_target = realpath($target);
        if ($real_target !== false) {
            $real_target = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $real_target);
            if (strpos($real_target, $base_prefix) === 0 || $real_target === $real_base) {
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
            $real_parent = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $real_parent);
            if (strpos($real_parent, $base_prefix) === 0 || $real_parent === $real_base) {
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

                $is_name_token = ($token_type === T_STRING)
                    || (defined('T_NAME_FULLY_QUALIFIED') && $token_type === T_NAME_FULLY_QUALIFIED)
                    || (defined('T_NAME_QUALIFIED') && $token_type === T_NAME_QUALIFIED)
                    || (defined('T_NAME_RELATIVE') && $token_type === T_NAME_RELATIVE);

                // Check function calls (including namespace-qualified and fully-qualified)
                if ($is_name_token) {
                    $func_name = strtolower(ltrim($token_value, '\\'));
                    if (in_array($func_name, self::DANGEROUS_PHP_FUNCTIONS, true)) {
                        return [
                            'is_safe' => false,
                            'error'   => sprintf('Security violation: Forbidden dangerous function `%s()` detected.', $func_name)
                        ];
                    }
                }

                // Check eval construct
                if ($token_type === T_EVAL) {
                    return [
                        'is_safe' => false,
                        'error'   => 'Security violation: Forbidden language construct `eval()` detected.'
                    ];
                }
            } elseif (is_string($token) && $token === '`') {
                // Check backtick execution operator
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
