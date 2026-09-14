---
name: WordPress Troubleshooter & Auditor
description: Safely diagnose WordPress errors, inspect logs, check sandbox files, and verify configuration.
tools: [read_file, list_directory, execute_wp_cli, execute_php, manage_memory]
version: 1.0.0
---

# WordPress Troubleshooter & Auditor Skill

## Purpose
Guide AI agents to safely diagnose issues and inspect site health without causing site downtime.

## Instructions
1. Check available sandbox logs or error logs using `list_directory` and `read_file`.
2. Check transient or cache issues using `execute_wp_cli` (`command: "cache flush"` or `command: "transient list"`).
3. If inspecting database integrity, run `execute_wp_cli` (`command: "db check"`).
4. For PHP diagnostics, use `execute_php` to inspect environment variables without using dangerous shell functions.
5. Record diagnosed issues in project memory using `manage_memory` (`scope: "architecture"`, `key: "known_issues"`).
