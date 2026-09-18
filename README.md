# Skia

**A social platform where you play, post, and connect.**

[![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## What is Skia?

Skia is a small social platform built from scratch — posts, comments, likes, profiles, notifications, and two browser games with progression systems.

It started as a whack-a-gold clone. It became something bigger.

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

## Tech Stack

| Layer        | Technology               |
| ------------ | ------------------------ |
| **Backend**  | PHP 8+, MySQL            |
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

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/nyxerebion/Skia.git
cd Skia
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

Create a `.env` file in the project root:

```env
DB_HOST=localhost
DB_NAME=skiadb
DB_USER=root
DB_PASS=

SMTP_HOST=in-v3.mailjet.com
SMTP_PORT=587
SMTP_USERNAME=your-api-key
SMTP_PASSWORD=your-secret
SMTP_FROM=your-email@gmail.com
SMTP_NAME=Skia
```

### 4. Import the database

```bash
mysql -u root -p skiadb < database/schema.sql
```

### 5. Run locally

Place the project in `htdocs/` and open:

```link
http://localhost/skia
```

---

## Live Demo

🔗 **[skia.unaux.com](https://skia.unaux.com)**

## Screenshots

> _Coming soon — the platform is still under active development._

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
