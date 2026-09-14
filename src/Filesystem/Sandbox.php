<?php
namespace AgentPress\Filesystem;

use AgentPress\Security\Sanitizer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filesystem Sandbox Engine with Traversal Protection and Safe Operations
 */
class Sandbox {

    private Sanitizer $sanitizer;

    public function __construct() {
        $this->sanitizer = new Sanitizer();
    }

    /**
     * Get sandbox root directory path
     */
    public static function get_sandbox_root(): string {
        $upload_dir = wp_upload_dir();
        $settings = get_option('agentpress_settings', []);
        $relative = $settings['sandbox_path'] ?? 'agentpress-sandbox';

        $root = $upload_dir['basedir'] . '/' . trim($relative, '/\\');
        return wp_normalize_path($root);
    }

    /**
     * Initialize sandbox directory
     */
    public static function init_sandbox(): void {
        $root = self::get_sandbox_root();
        if (!is_dir($root)) {
            wp_mkdir_p($root);
        }

        // Add index.php blank protector
        $index_file = $root . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, "<?php\n// Silence is golden.\n");
        }
    }

    /**
     * Resolve and validate a relative path inside the sandbox
     *
     * @param string $path
     * @return string|false Absolute safe path or false
     */
    public function resolve_path(string $path) {
        $root = self::get_sandbox_root();
        if (!is_dir($root)) {
            self::init_sandbox();
        }

        return $this->sanitizer->validate_sandbox_path($path, $root);
    }

    /**
     * Read a file securely within sandbox
     */
    public function read_file(string $path, int $max_bytes = 1048576): array {
        $safe_path = $this->resolve_path($path);
        if (!$safe_path || !is_file($safe_path)) {
            return [
                'success' => false,
                'error'   => 'File does not exist or is outside the sandbox boundary.'
            ];
        }

        $size = filesize($safe_path);
        if ($size > $max_bytes) {
            return [
                'success' => false,
                'error'   => sprintf('File size (%d bytes) exceeds maximum allowed read limit (%d bytes).', $size, $max_bytes)
            ];
        }

        $content = file_get_contents($safe_path);
        if ($content === false) {
            return ['success' => false, 'error' => 'Failed to read file content.'];
        }

        return [
            'success'       => true,
            'path'          => $path,
            'content'       => $content,
            'size_bytes'    => $size,
            'last_modified' => filemtime($safe_path),
        ];
    }

    /**
     * Write file content within sandbox
     */
    public function write_file(string $path, string $content, bool $overwrite = false): array {
        $safe_path = $this->resolve_path($path);
        if (!$safe_path) {
            return [
                'success' => false,
                'error'   => 'Invalid path or path is outside the sandbox boundary.'
            ];
        }

        if (file_exists($safe_path) && !$overwrite) {
            return [
                'success' => false,
                'error'   => 'File already exists. Set `overwrite` to true to overwrite.'
            ];
        }

        $parent = dirname($safe_path);
        if (!is_dir($parent)) {
            wp_mkdir_p($parent);
        }

        $written = file_put_contents($safe_path, $content);
        if ($written === false) {
            return ['success' => false, 'error' => 'Failed to write file to disk.'];
        }

        return [
            'success'       => true,
            'path'          => $path,
            'bytes_written' => $written,
        ];
    }

    /**
     * Edit file by replacing target substring
     */
    public function edit_file(string $path, string $target_content, string $replacement, bool $allow_multiple = false): array {
        $safe_path = $this->resolve_path($path);
        if (!$safe_path || !is_file($safe_path)) {
            return ['success' => false, 'error' => 'Target file does not exist.'];
        }

        $content = file_get_contents($safe_path);
        if ($content === false) {
            return ['success' => false, 'error' => 'Failed to read file.'];
        }

        $occurrences = substr_count($content, $target_content);
        if ($occurrences === 0) {
            return ['success' => false, 'error' => 'Target content not found in file.'];
        }

        if ($occurrences > 1 && !$allow_multiple) {
            return [
                'success' => false,
                'error'   => sprintf('Found %d occurrences of target content. Set `allow_multiple` to true or provide more surrounding context.', $occurrences)
            ];
        }

        // Automatic backup before edit
        $backup_path = $safe_path . '.bak.' . time();
        @copy($safe_path, $backup_path);

        $new_content = str_replace($target_content, $replacement, $content);
        $written = file_put_contents($safe_path, $new_content);

        return [
            'success'       => $written !== false,
            'path'          => $path,
            'replacements'  => $occurrences,
            'backup_file'   => basename($backup_path),
        ];
    }

    /**
     * Delete file in sandbox
     */
    public function delete_file(string $path): array {
        $safe_path = $this->resolve_path($path);
        if (!$safe_path || !file_exists($safe_path)) {
            return ['success' => false, 'error' => 'File does not exist.'];
        }

        $deleted = is_dir($safe_path) ? @rmdir($safe_path) : @unlink($safe_path);
        return [
            'success' => (bool) $deleted,
            'path'    => $path,
        ];
    }

    /**
     * Disable a file (rename to .disabled)
     */
    public function disable_file(string $path): array {
        $safe_path = $this->resolve_path($path);
        if (!$safe_path || !file_exists($safe_path)) {
            return ['success' => false, 'error' => 'File does not exist.'];
        }

        if (substr($safe_path, -9) === '.disabled') {
            return ['success' => true, 'message' => 'File is already disabled.'];
        }

        $disabled_path = $safe_path . '.disabled';
        $renamed = @rename($safe_path, $disabled_path);

        return [
            'success' => (bool) $renamed,
            'path'    => $path . '.disabled',
        ];
    }

    /**
     * Enable a disabled file (remove .disabled extension)
     */
    public function enable_file(string $path): array {
        $safe_path = $this->resolve_path($path);
        if (!$safe_path || !file_exists($safe_path)) {
            return ['success' => false, 'error' => 'File does not exist.'];
        }

        if (substr($safe_path, -9) !== '.disabled') {
            return ['success' => true, 'message' => 'File is already active.'];
        }

        $enabled_path = substr($safe_path, 0, -9);
        $renamed = @rename($safe_path, $enabled_path);

        return [
            'success' => (bool) $renamed,
            'path'    => substr($path, 0, -9),
        ];
    }

    /**
     * List files in directory with recursive & glob filtering
     */
    public function list_directory(string $path = '', bool $recursive = false, ?string $glob = null): array {
        $root = self::get_sandbox_root();
        $target = empty($path) ? $root : $this->resolve_path($path);

        if (!$target || !is_dir($target)) {
            return ['success' => false, 'error' => 'Directory does not exist or is outside sandbox.'];
        }

        $items = [];
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST)
            : new \DirectoryIterator($target);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $filename = $item->getFilename();
            if ($glob && !fnmatch($glob, $filename)) {
                continue;
            }

            $relative = str_replace(['/', '\\'], '/', substr($item->getPathname(), strlen($root) + 1));

            $items[] = [
                'name'          => $filename,
                'path'          => $relative,
                'is_dir'        => $item->isDir(),
                'size_bytes'    => $item->isDir() ? 0 : $item->getSize(),
                'last_modified' => $item->getMTime(),
            ];
        }

        return [
            'success' => true,
            'base'    => empty($path) ? '/' : $path,
            'total'   => count($items),
            'items'   => $items,
        ];
    }
}
