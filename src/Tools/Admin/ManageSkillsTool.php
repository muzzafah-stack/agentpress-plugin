<?php
namespace AgentPress\Tools\Admin;

use AgentPress\Tools\BaseTool;
use AgentPress\Core\Plugin;
use AgentPress\Security\PermissionManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Skills Tool (Inspect and Create Markdown Skills)
 */
class ManageSkillsTool extends BaseTool {

    public function get_name(): string {
        return 'manage_skills';
    }

    public function get_description(): string {
        return 'List available Markdown skills, inspect skill instructions, or create new skills.';
    }

    public function get_required_permission(): string {
        return PermissionManager::SCOPE_WRITE;
    }

    public function get_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['list', 'get', 'save'],
                    'description' => 'Skill action to perform.',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'Skill slug/identifier (required for get and save).',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Full Markdown skill content with YAML frontmatter (for save action).',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $params, ?object $token = null): array {
        $action = (string) $params['action'];
        $skill_manager = Plugin::get_instance()->skill_manager;

        switch ($action) {
            case 'list':
                $skills = $skill_manager->list_skills();
                return $this->success_response([
                    'total'  => count($skills),
                    'skills' => $skills,
                ]);

            case 'get':
                $slug = sanitize_title((string) ($params['slug'] ?? ''));
                if (empty($slug)) {
                    return $this->error_response('missing_slug', 'Parameter `slug` is required.');
                }
                $skill = $skill_manager->get_skill($slug);
                if (!$skill) {
                    return $this->error_response('not_found', sprintf('Skill `%s` not found.', $slug));
                }
                return $this->success_response($skill);

            case 'save':
                $slug = sanitize_title((string) ($params['slug'] ?? ''));
                $content = (string) ($params['content'] ?? '');
                if (empty($slug) || empty($content)) {
                    return $this->error_response('invalid_params', 'Parameters `slug` and `content` are required.');
                }
                $saved = $skill_manager->save_skill($slug, $content);
                return $this->success_response([
                    'slug'  => $slug,
                    'saved' => $saved,
                ]);

            default:
                return $this->error_response('invalid_action', 'Unsupported skill action.');
        }
    }
}
