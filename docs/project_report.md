# ReClaim — Project Report Outline

**Project Title:** ReClaim — Smart Lost & Found Platform  
**Course:** B.Tech CSE — Web Development Minor Project  
**Technology Stack:** PHP, MySQL, HTML5, CSS3, JavaScript (Vanilla)

---

## 1. Introduction

ReClaim is a centralized web-based Lost & Found platform designed to help people report, search for, and recover lost items through a structured, technology-assisted process. The platform combines user-submitted reports with a transparent rule-based matching algorithm, an admin-reviewed claims workflow, and real-time notifications.

---

## 2. Problem Statement

When items are lost in public spaces (colleges, markets, transport hubs), there is no efficient system connecting the person who lost the item with the person who found it. Existing methods rely on notice boards or word-of-mouth, which are slow and error-prone.

**ReClaim solves this by:**
- Providing a searchable database of lost and found reports
- Automatically suggesting possible matches using a scoring algorithm
- Allowing ownership claims with private verification details
- Ensuring human admin oversight before items are returned

---

## 3. Objectives

1. Build a secure, multi-user web application using PHP and MySQL.
2. Implement user authentication (register, login, session management, logout).
3. Enable CRUD operations on reports with image upload.
4. Design and implement a transparent, explainable matching algorithm.
5. Build a claim workflow with admin review and audit trail.
6. Implement in-app notifications.
7. Create a role-based access control system (user / admin).
8. Apply web security best practices (CSRF, XSS prevention, prepared statements, bcrypt).
9. Design a responsive, modern UI using CSS3 Flexbox and Grid.
10. Document, test, and prepare the project for GitHub submission.

---

## 4. Requirements

### Functional Requirements
- User registration and login
- Create, read, update, delete (CRUD) reports
- Image upload with validation
- Keyword, category, location, and date search/filter
- Rule-based matching with score explanation
- Claim submission and admin review
- Claim history and audit trail
- Notification system

### Non-Functional Requirements
- Responsive design (mobile, tablet, desktop)
- Secure: no SQL injection, XSS, CSRF vulnerabilities
- Fast: AJAX-powered live search
- Maintainable: modular PHP architecture

---

## 5. Architecture

### Directory Structure
```
ReClaim/
├── config/          — Database connection (PDO)
├── includes/        — Shared PHP: auth, csrf, header, footer, functions
├── actions/         — POST endpoint handlers (not full pages)
├── matching/        — Scoring algorithm module
├── uploads/         — User images (restricted by .htaccess)
├── css/ js/         — Frontend assets
├── database/        — SQL schema + data
└── docs/            — Documentation
```

### MVC-inspired separation
- **Model** = PDO queries in action files and page files
- **View** = PHP templates (page files + header/footer)
- **Controller** = `actions/*.php` (POST handlers)

---

## 6. ER Diagram (Text Representation)

```
users (1) ────< reports (many)
users (1) ────< claims (many) [as claimant]
users (0..1) ──< claims (many) [as reviewer]
reports (1) ───< matches (many) [as lost_id]
reports (1) ───< matches (many) [as found_id]
reports (1) ───< claims (many)
claims (1) ────< claim_history (many)
users (1) ────< notifications (many)
```

---

## 7. Database Design

### Table: users
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK | Auto-increment |
| name | VARCHAR(100) | Full name |
| email | VARCHAR(150) UNIQUE | Login identifier |
| password_hash | VARCHAR(255) | bcrypt hash |
| role | ENUM('user','admin') | Default: 'user' |
| phone | VARCHAR(30) | Optional |
| created_at, updated_at | TIMESTAMP | Audit fields |

### Table: reports
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK | |
| user_id | FK → users | Reporter |
| type | ENUM('lost','found') | |
| title | VARCHAR(200) | Item name |
| category | VARCHAR(100) | Indexed |
| description | TEXT | |
| location | VARCHAR(200) | Indexed |
| incident_date | DATE | Indexed |
| status | ENUM('open','claimed','resolved','closed') | |
| image_path | VARCHAR(500) | Stored filename |
| is_deleted | TINYINT(1) | Soft delete flag |

### Table: matches
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK | |
| lost_id | FK → reports | |
| found_id | FK → reports | |
| score | TINYINT UNSIGNED | 0–100 |
| matched_fields | JSON | e.g. ["category","title"] |
| status | ENUM | pending/confirmed/dismissed |
| UNIQUE(lost_id, found_id) | | No duplicate pairs |

### Table: claims
| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK | |
| report_id | FK → reports | The found report |
| claimant_id | FK → users | Who is claiming |
| verification_detail | TEXT | Private ownership proof |
| status | ENUM('pending','approved','rejected') | |
| reviewer_id | FK → users (nullable) | Admin who reviewed |
| UNIQUE(report_id, claimant_id) | | One pending claim per pair |

---

## 8. Implementation Notes

### Security
- `password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12])` — secure bcrypt
- `session_regenerate_id(true)` — prevents session fixation
- `verify_csrf()` — called in every POST action handler
- PDO `prepare/execute` — no string concatenation in SQL
- `htmlspecialchars()` aliased as `h()` — called on every output
- Upload: `finfo_file()` → MIME check, extension whitelist, `sanitize_filename()` (hex random)
- `uploads/.htaccess` — blocks PHP execution in upload folder

### Matching Algorithm
See `matching/matcher.php`. The algorithm is deterministic and explainable:
1. Category: exact string match → 30 pts
2. Title: PHP `similar_text()` + keyword overlap → 0–30 pts
3. Location: exact / substring / word overlap → 0–20 pts
4. Date: difference in days → 0–20 pts (0 days=20, ≤3=15, ≤7=10, ≤14=5, >14=0)

### Transactions
Claim approval uses `PDO::beginTransaction()` to ensure:
- Claim updated → approved
- Report updated → resolved
- Other claims → rejected
- Notifications created
All atomically. If any step fails, `rollBack()` reverses all changes.

---

## 9. Screenshots

> Add screenshots of:
> - Landing page (hero, report cards)
> - Login / Register pages
> - Dashboard
> - Report creation form
> - Report listing with filters
> - Report detail page with matches
> - Claim submission form
> - My Claims with timeline
> - Notifications page
> - Admin panel (all 4 tabs)
> - Mobile responsive views

---

## 10. Testing

See `docs/testing_checklist.md` for 60+ test cases across 9 modules.

**Key tests verified:**
- Auth: registration, duplicate email, login, session, logout
- Reports: CRUD, image upload security
- Search: keyword, filters, AJAX
- Matching: score correctness, threshold, duplicate prevention
- Claims: own-report prevention, duplicate claim prevention, approval transaction
- Security: CSRF, SQLi, XSS, upload attack vectors

---

## 11. Conclusion

ReClaim demonstrates a complete, production-ready web application built with standard PHP/MySQL technologies. It addresses a real problem with a practical, secure, and user-friendly solution.

**Key learning outcomes:**
- Full-stack PHP development with MVC-inspired architecture
- MySQL relational database design with foreign keys and indexes
- Session-based authentication and role-based access control
- Web security fundamentals (CSRF, XSS, SQL injection, upload safety)
- AJAX/JSON integration for dynamic UI without page reloads
- Responsive CSS design using Flexbox and Grid

---

## 12. Future Enhancements

1. Email notifications via PHPMailer (SMTP)
2. Google Maps integration for location pinpointing
3. Real-time chat between claimants and finders
4. Machine learning image similarity matching (as an optional plugin)
5. Mobile app using React Native or Flutter
6. Public API for integration with campus management systems
7. QR code generation for found items
8. Multi-language (i18n) support
