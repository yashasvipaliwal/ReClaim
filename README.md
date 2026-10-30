# ReClaim — Smart Lost & Found Platform

> B.Tech CSE Web Development Minor Project  
> Stack: PHP 8+, MySQL/MariaDB, Vanilla JS, CSS3

---

## 📋 Project Overview

ReClaim is a full-stack web application where users report lost or found items, 
receive smart rule-based match suggestions, submit ownership claims, and track 
the recovery process through an admin-reviewed workflow.

---

## ⚙️ Prerequisites

- [XAMPP](https://www.apachefriends.org/download.html) (includes PHP 8+ and MySQL/MariaDB)
- A modern web browser
- Git (optional, for version control)

---

## 🚀 Setup Instructions

### 1. Install XAMPP
Download and install XAMPP. Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 2. Place project files
Copy the `ReClaim` folder to your XAMPP htdocs directory:
```
C:\xampp\htdocs\ReClaim\
```

### 3. Create the database

**Option A — phpMyAdmin (easiest):**
1. Open `http://localhost/phpmyadmin`
2. Click **Import** → Choose file → select `database/reclaim.sql`
3. Click **Go**

**Option B — MySQL command line:**
```bash
mysql -u root -p < database/reclaim.sql
```

### 4. Configure environment

Copy `.env.example` to `.env`:
```bash
copy .env.example .env
```

Edit `.env` if your MySQL password is not empty (default XAMPP = empty):
```
DB_HOST=localhost
DB_NAME=reclaim_db
DB_USER=root
DB_PASS=          ← your MySQL root password
APP_URL=http://localhost/ReClaim
```

### 5. Run the application

Open your browser and go to:
```
http://localhost/ReClaim/
```

---

## 👤 Demo Accounts

| Role  | Email                   | Password   |
|-------|-------------------------|------------|
| Admin | admin@reclaim.local     | Admin@123  |
| User  | aryan@example.com       | User@123   |
| User  | priya@example.com       | User@123   |

> **Note:** These passwords are pre-hashed in `database/reclaim.sql`.
> Change them before any real deployment.

---

## 📁 Project Structure

```
ReClaim/
├── index.php              Landing page
├── login.php / register.php / logout.php
├── dashboard.php
├── report-create.php / report-list.php / report-view.php / report-edit.php
├── matches.php
├── claim-create.php / my-claims.php
├── notifications.php
├── admin.php
│
├── config/
│   └── db.php             PDO connection
│
├── includes/
│   ├── auth.php           Session helpers & guards
│   ├── csrf.php           CSRF token generation & validation
│   ├── functions.php      Shared utilities
│   ├── header.php         Nav & HTML head
│   └── footer.php         Footer & scripts
│
├── actions/
│   ├── login-action.php
│   ├── register-action.php
│   ├── report-action.php  (create / edit / delete)
│   ├── claim-action.php   (create / approve / reject)
│   ├── admin-action.php
│   └── ajax-search.php    AJAX JSON endpoint
│
├── matching/
│   └── matcher.php        Rule-based scoring algorithm
│
├── uploads/               User-uploaded images (gitignored)
├── css/style.css
├── js/main.js / validate.js
├── database/reclaim.sql
└── docs/
```

---

## 🔐 Security Features

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash()` with bcrypt (cost 12) |
| Password verify  | `password_verify()` |
| Session fixation | `session_regenerate_id(true)` after login |
| SQL injection    | PDO prepared statements everywhere |
| XSS              | `htmlspecialchars()` on all output |
| CSRF             | Token in session verified on every POST |
| Upload security  | MIME check (finfo), extension whitelist, 5 MB limit, random filename |
| Role enforcement | Hardcoded `'user'` on registration; admin only via SQL |
| Transactions     | Claim approval uses `beginTransaction/commit/rollBack` |

---

## 🔗 Key URLs

| URL | Description |
|---|---|
| `/ReClaim/` | Landing page |
| `/ReClaim/register.php` | New account |
| `/ReClaim/login.php` | Login |
| `/ReClaim/dashboard.php` | User dashboard |
| `/ReClaim/report-list.php` | Browse all reports |
| `/ReClaim/report-create.php` | Submit a report |
| `/ReClaim/matches.php` | View match suggestions |
| `/ReClaim/my-claims.php` | My claim history |
| `/ReClaim/notifications.php` | Notifications |
| `/ReClaim/admin.php` | Admin panel (admin only) |

---

## 🧠 Matching Algorithm

Scores are computed by `matching/matcher.php`:

| Criterion | Max Points |
|---|---|
| Same category | 30 |
| Similar item title (similar_text) | 30 |
| Matching location | 20 |
| Close incident dates | 20 |
| **Total** | **100** |

Threshold: ≥ 60 points → saved as a suggestion. Configurable via `MATCH_THRESHOLD` constant.

---

## 🔧 Git Setup

```bash
git init
git add .
git commit -m "Initial commit: ReClaim platform"
git remote add origin https://github.com/YOUR_USERNAME/reclaim.git
git push -u origin main
```

> The `.gitignore` excludes `.env`, `uploads/*`, and log files.

---

## 🌐 Deployment

See `docs/deployment.md` for full XAMPP + shared hosting instructions.

---

## 📚 Technologies Used

- **PHP 8+** — Backend logic, sessions, PDO
- **MySQL/MariaDB** — Relational database
- **HTML5 / CSS3** — Semantic markup, Flexbox, Grid
- **Vanilla JavaScript** — DOM manipulation, Fetch API, form validation
- **AJAX/JSON** — Live search without page reload
- **Inter** (Google Fonts) — Typography
