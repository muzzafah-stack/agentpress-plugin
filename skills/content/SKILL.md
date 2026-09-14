---
name: Gutenberg Content Editor
description: Safely inspect, draft, and modify structured Gutenberg blocks without breaking post design.
tools: [gutenberg_blocks, read_file, manage_memory]
version: 1.0.0
---

# Gutenberg Content Editor Skill

## Purpose
Assist AI agents in creating and editing WordPress content utilizing native Gutenberg block structures.

## Instructions
1. Call `gutenberg_blocks` (`action: "get_blocks"`) with `post_id` to inspect existing blocks.
2. If adding new sections, verify registered patterns with `gutenberg_blocks` (`action: "list_patterns"`).
3. Validate block JSON structure before updating. Ensure every block has valid `blockName`, `attrs`, and `innerBlocks`.
4. Update post content using `gutenberg_blocks` (`action: "update_blocks"`).
5. Always preserve existing layout styling and avoid removing custom classes.
