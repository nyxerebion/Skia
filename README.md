# Skia

**A social platform where you play, post, and connect.**

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

🔗 **[skia.unaux.com](https://skia.unaux.com)**

---

## Screenshots

<!-- markdownlint-disable MD033 -->
<p align="center">
  <img src="docs/screenshots/login.png" alt="Login" width="48%">
  <img src="docs/screenshots/feed.png" alt="Feed" width="48%">
</p>
<p align="center">
  <img src="docs/screenshots/click-adventure.png" alt="Click Adventure" width="48%">
  <img src="docs/screenshots/settings.png" alt="Settings" width="48%">
</p>
<!-- markdownlint-enable MD033 -->

---

## What is Skia?

Skia is a small social platform built from scratch — posts, comments, likes, profiles, notifications, and two browser games with progression systems.

---

## Features

### 🎮 Games

- **Click Adventure** — clicker RPG with enemies, shop, and leveling
- **Whack Gold** — 30-second reflex game with leaderboard

### 💬 Social

- Posts with likes and comments
- Comment likes with notifications
- Follow system
- Public user profiles
- Avatar upload with crop

### 🔐 Accounts

- Secure login and registration
- Password reset via email
- Username, name, and bio editing with cooldowns
- Role-based access (user / admin / creator)

### 🎨 Interface

- Dark and light themes
- Responsive layout
- Real-time notifications
- Toast messages, modals, and animations

---

## Security

- Session hardening — `use_strict_mode`, `use_only_cookies`, HttpOnly, SameSite=Lax, regeneration on privilege change
- CSRF tokens on all state-changing requests
- Rate limiting on auth endpoints (login, password reset)
- Prepared statements (PDO) throughout — no string-concatenated queries
- Password hashing via `password_hash()` / `password_verify()`
- Upload validation — MIME type, size, dimension, WebP→JPG conversion
- Layered input sanitization and validation
- Activity logging and audit trails for account changes

---

## Tech Stack

| Layer        | Technology               |
| ------------ | ------------------------ |
| **Backend**  | PHP 8+, MySQL / MariaDB  |
| **Frontend** | Vanilla JS, CSS3         |
| **Email**    | PHPMailer + Mailjet SMTP |
| **Auth**     | Sessions + CSRF tokens   |
| **Packages** | Composer                 |

---

## Project Structure

```text
skia/
├── api/           → JSON endpoints
├── backend/       → Config, helpers, email
├── core/          → Bootstrap and head
├── css/           → Stylesheets
├── database/      → Schema and connection
├── games/         → Game pages and logic
├── js/            → Client-side scripts
├── pages/         → Contents, settings, profile
├── posts/         → Post feed
└── security/      → Auth and validation
```

---

## Roadmap

- [x] Posts, comments, likes
- [x] Click Adventure + Whack Gold
- [x] Password reset via email
- [x] Account settings with cooldowns
- [ ] Email verification
- [ ] Email change with verification
- [ ] User achievements

---

## Contributing

This is a personal project. Suggestions and bug reports are welcome — open an issue.

---

## License

MIT — free to use, modify, and learn from.

---

**Built by [Axel](https://github.com/nyxerebion) · 2025–2026**
