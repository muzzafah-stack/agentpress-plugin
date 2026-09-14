<?php
namespace AgentPress\Skills;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages Markdown-based Skills
 */
class SkillManager {

    /**
     * Get directories where skills are stored
     */
    public function get_skills_directories(): array {
        $dirs = [
            AGENTPRESS_PATH . 'skills',
        ];

        $upload_dir = wp_upload_dir();
        $custom_skills = $upload_dir['basedir'] . '/agentpress-sandbox/skills';
        if (is_dir($custom_skills)) {
            $dirs[] = $custom_skills;
        }

        return $dirs;
    }

    /**
     * List all available skills
     */
    public function list_skills(): array {
        $skills = [];
        $dirs = $this->get_skills_directories();

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile() && strtolower($item->getFilename()) === 'skill.md') {
                    $relative_path = substr($item->getPath(), strlen($dir));
                    $slug = trim(str_replace(['/', '\\'], '-', $relative_path), '-');
                    if (empty($slug)) {
                        $slug = basename($item->getPath());
                    }

                    $parsed = $this->parse_skill_file($item->getPathname());
                    if ($parsed) {
                        $parsed['slug'] = $slug;
                        $parsed['file_path'] = $item->getPathname();
                        $skills[$slug] = $parsed;
                    }
                }
            }
        }

        return array_values($skills);
    }

    /**
     * Get a specific skill by slug
     */
    public function get_skill(string $slug): ?array {
        $all = $this->list_skills();
        foreach ($all as $skill) {
            if ($skill['slug'] === $slug) {
                return $skill;
            }
        }
        return null;
    }

    /**
     * Parse markdown file with YAML frontmatter
     *
     * @param string $file_path
     * @return array|null
     */
    public function parse_skill_file(string $file_path): ?array {
        if (!file_exists($file_path)) {
            return null;
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return null;
        }

        return $this->parse_markdown_content($content);
    }

    /**
     * Parse markdown raw text
     */
    public function parse_markdown_content(string $content): array {
        $metadata = [
            'name'        => '',
            'description' => '',
            'tools'       => [],
            'version'     => '1.0.0',
        ];
        $body = $content;

        // Extract Frontmatter --- ... ---
        if (preg_match('/^---\s*[\r\n]+([\s\S]*?)[\r\n]+---\s*[\r\n]+([\s\S]*)$/', $content, $matches)) {
            $frontmatter = $matches[1];
            $body = trim($matches[2]);

            $lines = explode("\n", $frontmatter);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                if (strpos($line, ':') !== false) {
                    [$key, $val] = explode(':', $line, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");

                    if ($key === 'tools') {
                        // Check if array format [a, b]
                        if (strpos($val, '[') === 0) {
                            $val = trim($val, '[]');
                            $metadata['tools'] = array_map('trim', explode(',', $val));
                        } else {
                            $metadata['tools'] = array_filter([$val]);
                        }
                    } else {
                        $metadata[$key] = $val;
                    }
                }
            }
        }

        return [
            'meta'         => $metadata,
            'instructions' => $body,
            'raw_content'  => $content,
        ];
    }

    /**
     * Create or update a custom skill markdown file
     */
    public function save_skill(string $slug, string $content): bool {
        $slug = sanitize_title($slug);
        if (empty($slug)) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/agentpress-sandbox/skills/' . $slug;

        if (!is_dir($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        $target_file = $target_dir . '/SKILL.md';
        return (bool) file_put_contents($target_file, $content);
    }
}
