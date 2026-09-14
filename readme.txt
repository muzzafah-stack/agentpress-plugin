=== AgentPress ===
Contributors: muzzafah-stack, hipnolink
Tags: ai, mcp, claude, cursor, agent, automation, rest api, developer tools
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Give AI a Home in WordPress. Connect AI agents safely via structured REST API, MCP tool layer, and filesystem sandbox.

== Description ==

AgentPress is a production-ready WordPress plugin that creates a secure, structured, and modular bridge between your WordPress site and modern AI agents (Claude, ChatGPT, Cursor, custom LLM agents, and MCP clients).

= Features =
* **Native AI Connection Layer:** Authenticate and interact with AI agents using scoped SHA-256 Bearer tokens.
* **MCP Compatible Tool Discovery:** Exposes structured JSON-Schema tools compliant with Model Context Protocol.
* **Secure Filesystem Sandbox:** Safe read/write/edit operations strictly confined to an isolated directory with path traversal protection.
* **Safe PHP & WP-CLI Execution:** Run controlled PHP code snippets with timeout guards, error capture, and dangerous function blocking.
* **Gutenberg Block Manipulator:** Read, update, and validate block structures natively.
* **Markdown Skills Engine:** Define and execute structured skills stored in Markdown files.
* **Persistent Project Memory:** Maintain context, tasks, and project goals across AI sessions.
* **One-Time Admin Access:** Generate cryptographically secure single-use login links.
* **Extensible Adapters:** Modular integration with Rank Math, SEOPress, Slim SEO, Elementor, and Code Snippets.

== Installation ==

1. Upload the `agentpress` folder to the `/wp-content/plugins/` directory, or install via Plugins > Add New > Upload Plugin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **AgentPress > Connection** to generate your API token and get your endpoint URL.
4. Add the endpoint and token to your AI client configuration.

== Changelog ==

= 1.0.0 =
* Initial release.
