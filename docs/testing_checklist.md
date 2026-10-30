# ReClaim — Testing Checklist

## Module 1: Authentication

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 1.1 | Register with valid data | Account created, auto-login, redirect to dashboard | |
| 1.2 | Register duplicate email | Error: "account with this email already exists" | |
| 1.3 | Register with short password (<8 chars) | Error: "at least 8 characters" | |
| 1.4 | Register with mismatched passwords | Error: "do not match" | |
| 1.5 | Login with correct credentials | Redirect to dashboard, session set | |
| 1.6 | Login with wrong password | Error: "Invalid email or password" | |
| 1.7 | Login with non-existent email | Error: "Invalid email or password" | |
| 1.8 | Access dashboard.php without login | Redirect to login.php | |
| 1.9 | Access admin.php as normal user | Redirect to dashboard with error | |
| 1.10 | Logout clears session | Redirect to login, session destroyed | |
| 1.11 | Registered user cannot set role=admin via form | Role always set to 'user' by PHP | |

## Module 2: Reports CRUD

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 2.1 | Create report with valid data | Report saved, redirect to report-view | |
| 2.2 | Create report with missing title | Error: "3 and 200 characters" | |
| 2.3 | Create report with future date | Error: "cannot be in the future" | |
| 2.4 | Create report with invalid category | Error: "valid category" | |
| 2.5 | Upload JPEG image | Image saved with random filename | |
| 2.6 | Upload PHP file as image | Error: "Only JPEG, PNG…" | |
| 2.7 | Upload image > 5 MB | Error: "smaller than 5 MB" | |
| 2.8 | Edit own report | Report updated correctly | |
| 2.9 | Edit another user's report (direct URL) | Error: "permission" + redirect | |
| 2.10 | Delete own report | Report soft-deleted (is_deleted=1) | |
| 2.11 | Delete another user's report (direct URL) | Error: "cannot delete" + redirect | |
| 2.12 | Admin can edit/delete any report | Success | |

## Module 3: Search & Filtering

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 3.1 | Search by keyword | Matching reports shown | |
| 3.2 | Filter by type=lost | Only lost reports | |
| 3.3 | Filter by type=found | Only found reports | |
| 3.4 | Filter by category | Only that category | |
| 3.5 | Filter by location | Results with that location | |
| 3.6 | Filter by date range | Reports within date range | |
| 3.7 | AJAX live search (hero bar) | Results appear without page reload | |
| 3.8 | Soft-deleted reports not shown | Only is_deleted=0 appear | |
| 3.9 | Search returns no results | Empty state shown | |
| 3.10 | Pagination works | Multiple pages navigate correctly | |

## Module 4: Matching Algorithm

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 4.1 | Same category → 30 pts | Score includes 30 | |
| 4.2 | Identical titles → ~30 pts | similar_text returns ~100% | |
| 4.3 | Exact location match → 20 pts | Full location score | |
| 4.4 | Same date → 20 pts | Max date score | |
| 4.5 | Score ≥ 60 → match saved | Row inserted in matches table | |
| 4.6 | Score < 60 → match NOT saved | No row in matches table | |
| 4.7 | Duplicate match pair not inserted | INSERT IGNORE works | |
| 4.8 | Both report owners notified | Rows in notifications table | |
| 4.9 | Lost report matches only with Found | No lost-lost or found-found pairs | |

## Module 5: Claims

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 5.1 | Claim own found report | Error: "cannot claim your own" | |
| 5.2 | Claim a lost report | Error: "can only claim open found" | |
| 5.3 | Duplicate claim by same user | Error: "already submitted" | |
| 5.4 | Claim with <20 chars proof | Error: validation fails | |
| 5.5 | Claim closed report | Error: report not eligible | |
| 5.6 | Submit valid claim | Claim created, report status→claimed | |
| 5.7 | Found-item reporter notified | Notification created | |
| 5.8 | Admin approves claim | Status→approved, report→resolved | |
| 5.9 | Other claims auto-rejected on approval | Other pending claims rejected | |
| 5.10 | Claimant notified of approval | Notification: "APPROVED" | |
| 5.11 | Admin rejects claim | Status→rejected, claimant notified | |
| 5.12 | Non-admin cannot approve claim | Redirect + error | |
| 5.13 | Claim history logged | claim_history row inserted | |

## Module 6: Notifications

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 6.1 | Unread badge shown in navbar | Badge count matches unread rows | |
| 6.2 | Opening notifications.php marks all read | is_read=1 in DB | |
| 6.3 | Match notification sent | Row in notifications for report owner | |
| 6.4 | Claim status notification sent | Row for claimant on approve/reject | |

## Module 7: Admin Panel

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 7.1 | Stats counts are accurate | Match counts from DB | |
| 7.2 | Admin can delete any report | Soft-delete succeeds | |
| 7.3 | Admin can toggle user role | Role changes in DB | |
| 7.4 | Admin cannot demote themselves | Error: "Cannot modify your own role" | |
| 7.5 | Claim verification detail visible only in admin | Not shown on public report-view | |

## Module 8: Security

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 8.1 | Submit form without CSRF token | HTTP 403 | |
| 8.2 | Tamper CSRF token | HTTP 403 | |
| 8.3 | SQL injection in search: `' OR 1=1--` | No DB error, safe result | |
| 8.4 | XSS in title: `<script>alert(1)</script>` | Escaped in output | |
| 8.5 | Access actions/*.php directly via GET | Redirect (no operation) | |
| 8.6 | Upload .php disguised as .jpg | Rejected by MIME check | |
| 8.7 | Execute uploaded file via URL | .htaccess blocks execution | |

## Module 9: Responsiveness

| # | Test | Expected Result | ✅ Pass |
|---|------|-----------------|---------|
| 9.1 | Mobile (375px) — navbar | Hamburger menu appears | |
| 9.2 | Mobile — report cards | Stack in single column | |
| 9.3 | Mobile — forms | Inputs full width, touch-friendly | |
| 9.4 | Tablet (768px) | 2-column grids | |
| 9.5 | Desktop (1200px) | 4-column grids, side filters | |

---

## How to Test

1. Import `database/reclaim.sql` in phpMyAdmin.
2. Copy `.env.example` → `.env`, configure DB.
3. Open `http://localhost/ReClaim/`.
4. Log in with demo credentials.
5. Follow each row above and note the result in the ✅ column.
