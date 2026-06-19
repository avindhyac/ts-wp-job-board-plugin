# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A standalone WordPress plugin (`wp-job-board`) that adds a `job_listing` custom post type, a front-end job board via shortcode, and a custom admin dashboard. No build step — pure PHP, vanilla JS, and CSS.

## Installation / Development

Drop the `wp-job-board/` folder into `wp-content/plugins/` on a WordPress install and activate it. There are no npm packages, Composer dependencies, or compilation steps.

To test changes:
- PHP: edit files directly; WordPress loads them on each request (no cache to bust in dev mode).
- CSS/JS: version strings are hardcoded in `includes/enqueue.php` — bump them when you want to break browser cache in a production deployment.

## Architecture

### Entry point
`wp-job-board.php` — defines `WJB_PATH` and `WJB_URL` constants, then `require_once`s every file in `includes/`.

### includes/

| File | Responsibility |
|------|----------------|
| `post-type.php` | Registers `job_listing` CPT (private, no public archive/rewrite) |
| `meta-boxes.php` | Job Details meta box, save handler, and custom admin list columns |
| `shortcode.php` | `[job_board]` shortcode — queries, HTML output, modal markup |
| `enqueue.php` | Registers Archivo font from Google Fonts + `assets/job-board.css` + `assets/job-board.js` on every front-end page |
| `admin-dashboard.php` | Dashboard submenu page with stat cards, sector bar chart, type/level breakdowns, and recent listings table; CSS is injected inline via `wp_add_inline_style` |

### Post meta keys
All stored under the `job_listing` post type:

| Key | Values |
|-----|--------|
| `_wjb_sector` | Free text |
| `_wjb_location` | Free text |
| `_wjb_level` | Entry Level / Mid Level / Senior / Lead / Management / Director / C-Level |
| `_wjb_type` | Full-Time / Part-Time / Contract / Freelance / Internship |
| `_wjb_apply` | URL or `mailto:email` |
| `_wjb_active` | `'1'` (shown) or `'0'` (hidden without deleting) |

### Front-end shortcode
`[job_board]` — all attributes are optional:

```
[job_board per_page="10" sector="Technology" location="Remote"
           show_hero="yes" eyebrow="..." heading="..." subheading="..."]
```

Filtering (search, sector pills, location/level/type dropdowns) is entirely client-side in `assets/job-board.js` — no AJAX. The shortcode renders all matching jobs in one pass and the JS hides/shows cards. Pagination (`?wjb_page=N`) only activates when `per_page` is set.

### Front-end styling
`assets/job-board.css` uses a dark violet brand system. All rules are scoped to `.wjb-wrap` so nothing leaks into the theme. CSS custom properties are declared at `.wjb-wrap` level (`--wjb-purple`, `--wjb-card`, `--wjb-border`, etc.). Font is Archivo (loaded from Google Fonts).

## Client Portal

A standalone admin UI for the client at `/job-board-admin` (slug configurable in **wp-admin → Settings → Job Board Portal**). The client never needs wp-admin access.

### How it works
- A WP rewrite rule intercepts the slug and calls `wjb_portal_render_page()` via `template_redirect`, bypassing the theme entirely and outputting a full HTML document.
- Auth is a **standalone password** (no WP user required), stored as `wp_hash_password()` in `wp_options`. The session cookie is an HMAC-SHA256 token signed with `AUTH_KEY`, expires after 12 hours, HttpOnly + SameSite=Strict.
- CRUD actions are `wp_ajax_nopriv_*` handlers, each verified by both the session cookie and a WP nonce.

### Portal files

| File | Responsibility |
|------|----------------|
| `includes/portal-settings.php` | wp-admin Settings page — set portal password + URL slug |
| `includes/portal-auth.php` | Rewrite rules, `template_redirect`, cookie set/verify/clear |
| `includes/portal-render.php` | Full HTML page: login screen or dashboard (stats + job table + modals) |
| `includes/portal-ajax.php` | `wjb_portal_add_job`, `wjb_portal_update_job`, `wjb_portal_toggle_job`, `wjb_portal_delete_job` |
| `assets/portal.css` | Dark/violet portal styles, all prefixed `.wjbp-` |
| `assets/portal.js` | Fetch-based CRUD, modal/overlay logic, toast notifications, stat sync |

### First-time setup
1. Activate/reactivate the plugin so `flush_rewrite_rules()` runs via the activation hook.
2. Go to **wp-admin → Settings → Job Board Portal**, set a password and optionally change the URL slug.
3. Share the portal URL (shown on that settings page) with the client.

### Naming conventions
- PHP functions: `wjb_` prefix
- Meta keys: `_wjb_` prefix  
- CSS classes: `wjb-` prefix
- JS IDs: `wjb-` prefix

### Sector icon mapping
`wjb_sector_icon()` in `shortcode.php` keyword-matches the free-text sector value against a table of inline SVG icons. Matching is case-insensitive substring (`strpos`). Unknown sectors fall back to a briefcase icon.
