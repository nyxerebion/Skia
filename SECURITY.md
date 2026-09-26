# Security Policy

## Supported Versions

Only the latest commit on `main` is supported.

## Reporting a Vulnerability

If you find a security issue, please open a [private security advisory](https://github.com/nyxerebion/Skia/security/advisories/new) on GitHub. Do not open a public issue.

Include:

- A description of the issue
- Steps to reproduce
- The affected URL or file
- Any proof-of-concept code

You will get a response within 7 days.

---

## Security Controls

Security was treated as a first-class concern throughout the project.

### Authentication and Sessions

- Password hashing via `password_hash()` / `password_verify()`
- Session hardening: `use_strict_mode`, `use_only_cookies`, HttpOnly, SameSite=Lax
- Session regeneration on privilege change and after logout
- Remember-me token hashed before storage
- Password reset tokens expire after 1 hour
- Cooldowns on name (24h) and username (48h) changes

### Request Integrity

- CSRF tokens on all state-changing requests, including `sendBeacon` calls
- Unified error responses to prevent account enumeration
- Rate limiting on login, registration, password reset, password verification, and follow actions
- Server-side validation for all inputs; client-side validation is for UX only

### Data Handling

- Prepared statements (PDO) throughout — no string-concatenated queries
- `PDO::ATTR_EMULATE_PREPARES => false`
- Output escaping via `htmlspecialchars()` at every render point
- Upload validation: extension whitelist, 5MB size limit, WebP→JPG conversion via GD
- Hashids-encoded public IDs to avoid exposing sequential database IDs

### Concurrency

- Transactions with `SELECT ... FOR UPDATE` on game state writes to prevent lost updates

### HTTP Headers

- `Content-Security-Policy` (enforced)
- `Strict-Transport-Security` (production only)
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`

### Access Control

- Direct web access blocked for `backend/`, `core/`, `database/`, and helper files in `security/`
- Sensitive file extensions (`.env`, `.sql`, `.log`, `.ini`, `.json`, `.lock`, `.md`, `.bak`, `.old`, `.swp`) blocked via `.htaccess`
- Dotfiles blocked except `.well-known/`

### Logging and Audit

- Activity log for user actions
- Audit trails for changes to name, username, bio, and avatar
- Admin approval workflow for cross-user moderation actions

---

## Security Notes

Vulnerabilities found and fixed during development.

### Account enumeration in login

The login flow initially returned distinct error messages for unknown users ("Username doesn't exist") and known users with wrong passwords ("Invalid password"). An attacker could use this to enumerate valid usernames. Fixed by returning a single "Invalid credentials" error and recording a rate-limit attempt in both cases. See `security/login.php`.

### Rate limit cleared on success

The follow endpoint cleared its rate-limit counter after every successful request, so a user who ever succeeded could exceed the intended limit. Fixed by removing the `clearRateLimit` calls from the success paths. See `api/follow.php`.

### Lost updates on concurrent attacks

Two tabs attacking the same enemy could both read the same health, compute damage, and one write would overwrite the other. Fixed by wrapping the read-modify-write in a transaction with `SELECT ... FOR UPDATE`. See `api/games/click-attack.php`.

### Double-escaping in input sanitization

`sanitizeString()` and `sanitizeTextarea()` applied `htmlspecialchars()` at input time, then again at output. This corrupted any user input containing `&`, `<`, or `>`. Fixed by removing `htmlspecialchars()` from the sanitizers; escaping now happens only at output. See `security/input-sanitization.php`.

### CSRF gap in click-save-beforeunload

The `beforeunload` beacon endpoint accepted state-changing requests without validating a CSRF token. Fixed by adding `validateCSRFToken()` and sending the token from the client. See `api/games/click-save-beforeunload.php`.

### Duplicate pending actions

Admins could queue multiple edit or delete requests for the same post. Fixed by checking for an existing pending action before inserting a new one. See `posts/edit.php`, `posts/delete.php`.

---

## Verification

### Manual testing

| Test                                                                                                                  | Result            |
| --------------------------------------------------------------------------------------------------------------------- | ----------------- |
| Direct access to sensitive files (`.env`, `schema.sql`, `config.php`, `bootstrap.php`, helper files, `composer.json`) | All blocked — 403 |
| CSRF token validation on state-changing endpoints                                                                     | Pass              |
| Rate limiting on login (6th attempt blocked)                                                                          | Pass              |
| Admin/creator pages inaccessible to standard users                                                                    | Pass              |
| Session cookie flags (`HttpOnly`, `Secure`, `SameSite=Lax`)                                                           | Pass              |
| Session ID regeneration on login and logout                                                                           | Pass              |

### OWASP ZAP automated scan

Target: `https://skia.unaux.com/guest-page.php`

| Severity      | Count | Notes                                                                  |
| ------------- | ----- | ---------------------------------------------------------------------- |
| High          | 0     | —                                                                      |
| Medium        | 5     | All on static files bypassing PHP, or CSP `unsafe-inline` (deliberate) |
| Low           | 3     | Static files missing HSTS / `X-Content-Type-Options`                   |
| Informational | 6     | Session management, timestamp disclosure                               |

No High severity findings. Medium and Low findings on static assets are expected — those files bypass PHP and therefore do not receive the headers set in `security/headers.php`. The CSP `unsafe-inline` allowance is deliberate; removing it requires migrating all inline event handlers to external listeners.

---

## Known Limitations

- CSP allows `'unsafe-inline'` for scripts and styles. Removing it requires migrating inline `onclick` handlers to `addEventListener`.
- No automated security tests in CI.
- No 2FA.
- No email verification.

---

**Last updated: 2026-09-26**
