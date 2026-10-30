# ReClaim — Deployment Guide

## Local Development (XAMPP on Windows)

### Requirements
- XAMPP 8.x (PHP 8.0+, MySQL 5.7+ / MariaDB 10.3+)
- Modern browser

### Steps
```
1. Install XAMPP → https://www.apachefriends.org
2. Start Apache + MySQL from XAMPP Control Panel
3. Copy ReClaim/ → C:\xampp\htdocs\ReClaim\
4. Open phpMyAdmin → http://localhost/phpmyadmin
5. Import database/reclaim.sql
6. Copy .env.example → .env (edit DB_PASS if needed)
7. Browse to http://localhost/ReClaim/
```

---

## Production Deployment (Shared Hosting, e.g. InfinityFree / Hostinger)

### 1. Upload files
Use an FTP client (FileZilla) to upload all ReClaim files to `public_html/ReClaim/`.

### 2. Create database
- Use cPanel → MySQL Databases to create `reclaim_db`
- Import `database/reclaim.sql` via phpMyAdmin in cPanel

### 3. Configure .env
Update `.env` with your hosting credentials:
```
DB_HOST=localhost
DB_NAME=yourusername_reclaim_db
DB_USER=yourusername_dbuser
DB_PASS=your_strong_password
APP_URL=https://yourdomain.com/ReClaim
```

### 4. Update base path
In `includes/header.php` and `includes/footer.php`, change `/ReClaim` to match your production subdirectory (or `/` if at root).

### 5. Set upload permissions
```bash
chmod 755 uploads/
```

### 6. Secure .env (Apache)
Add to project `.htaccess`:
```apache
<Files ".env">
    Order deny,allow
    Deny from all
</Files>
```

---

## GitHub Version Control

```bash
# Initialize repository
git init
git add .
git commit -m "feat: initial ReClaim platform"

# Link to GitHub
git remote add origin https://github.com/YOUR_USERNAME/ReClaim.git
git branch -M main
git push -u origin main

# Future updates
git add .
git commit -m "fix: describe your change"
git push
```

> ⚠️ Never commit `.env`, `uploads/*`, or real user data.
> These are excluded by `.gitignore`.

---

## Changing Admin Password

After deployment, update the admin password via MySQL:
```sql
USE reclaim_db;
UPDATE users SET password_hash = '$2y$12$NEW_HASH_HERE' WHERE email='admin@reclaim.local';
```

Or use PHP to generate a new hash:
```php
echo password_hash('YourNewPassword123!', PASSWORD_BCRYPT, ['cost' => 12]);
```
