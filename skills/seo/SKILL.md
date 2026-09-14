---
name: WordPress SEO Optimizer
description: Inspect and optimize SEO titles, descriptions, and focus keywords using active SEO plugins.
tools: [seo_operations, gutenberg_blocks, manage_memory]
version: 1.0.0
---

# WordPress SEO Optimizer Skill

## Purpose
Guide AI agents to analyze and optimize SEO parameters across WordPress posts and pages without overwriting valid existing metadata.

## Instructions
1. First, call `seo_operations` with `action: "detect_plugin"` to identify which SEO plugin (Rank Math, SEOPress, Slim SEO) is active.
2. Read target post's current SEO data using `seo_operations` with `action: "get_meta"` and `post_id`.
3. Inspect post block content using `gutenberg_blocks` (`action: "get_blocks"`) to evaluate keyword placement in headings and first paragraphs.
4. Prepare optimized metadata (title < 60 chars, description < 160 chars, relevant focus keyword).
5. Apply metadata updates using `seo_operations` (`action: "set_meta"`).
6. Store optimization summary in persistent memory using `manage_memory` (`scope: "tasks"`).
