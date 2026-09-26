# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `SECURITY.md` with security controls, vulnerability notes, and scan results
- `CHANGELOG.md`
- `.env.example` template
- Enforced Content Security Policy
- `Strict-Transport-Security` header on production
- Server-side validation for registration (username, email, password)
- CSRF validation on `click-save-beforeunload.php`
- Duplicate-pending check in `posts/edit.php` and `posts/delete.php`
- Rate limiting on password verification endpoint
- Batched follow lookups in `api/get-online-users.php`
- Transaction wrapper with `SELECT ... FOR UPDATE` in `api/games/click-attack.php`

### Changed

- Unified login error message to prevent account enumeration
- Login failure now records a rate-limit attempt for both unknown users and wrong passwords
- `sanitizeString()` and `sanitizeTextarea()` no longer apply `htmlspecialchars` (escaping is output-only)
- Post, note, and archive previews use `mb_substr` / `mb_strlen` for multibyte safety
- PHPMailer constraint fixed to `^6.9`
- SMTP TLS verification re-enabled in `backend/email.php`
- Trusted proxy list emptied in `security/functions.php` (no reverse proxy in use)
- HSTS header is conditional on non-localhost environments
- Follow notification is now written before the response is sent
- `api/get-online-users.php` batches follow lookups instead of one query per user
- `admin/logs.php` table markup corrected (removed `<div>` inside `<table>`)
- `admin/panel.php` avatar cell markup corrected
- `pages/view-profile.php` current points displayed with `number_format` instead of `formatTime`

### Fixed

- Rate limit no longer cleared on successful follow or unfollow
- Follow insert now handles duplicate key (`SQLSTATE 23000`) without a 500
- Level-up health cap in `api/games/click-attack.php` uses `$newMaxHealth` (was `$newEnemyHealth`)
- `UPDAE` typo in `api/games/click-save-stats.php`
- CSRF token access typo in `creator/send-notification.php`
- Undefined `$id` before use in `admin/archived.php`
- `content.disabled` and `"errors"` typos in `js/posts.js`
- Removed duplicate `markRead` handler in `index.php` (event delegation already handles clicks)

### Removed

- `admin/pass-update.php` (exposed plaintext password and hashes)
- `security/change-password.php` (dead page, no POST handler)
- `pages/dump.php`, `pages/test.php`, `pages/test-design.php`
- `pages/under-dev.php`
- `creator/notif.sql`, `games/click.sql`, `games/whack.sql` (superseded by `database/schema.sql`)
- Editor copy files (`api/mark-all-read copy.php`, `css/contents copy.css`, `js/settings copy.js`, `security/register copy.php`)
- `ini_set('expose_php', 0)` from `core/bootstrap.php` (cannot be set at runtime)

```

```
