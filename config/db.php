<?php
/**
 * config/db.php
 * ─────────────────────────────────────────────────────────────
 * Creates a shared PDO database connection using credentials
 * from the .env file in the project root.
 *
 * Usage in any PHP page:
 *   require_once __DIR__ . '/../config/db.php';
 *   // $pdo is now available
 *
 * Why PDO?  PDO supports prepared statements (preventing SQL
 * injection) and works with both MySQL and MariaDB.
 */

// ── Load .env file ──────────────────────────────────────────
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comment lines starting with #
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// ── Read credentials (fall back to XAMPP defaults) ──────────
$dbHost    = $_ENV['DB_HOST'] ?? 'localhost';
$dbName    = $_ENV['DB_NAME'] ?? 'reclaim_db';
$dbUser    = $_ENV['DB_USER'] ?? 'root';
$dbPass    = $_ENV['DB_PASS'] ?? '';
$dbCharset = 'utf8mb4';

// ── DSN (Data Source Name) ───────────────────────────────────
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

// ── PDO options ──────────────────────────────────────────────
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // return arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                     // use native prepared statements
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Log the real error but never show DB details to the browser
    error_log('[ReClaim DB Error] ' . $e->getMessage());
    http_response_code(500);
    die('<h2 style="font-family:sans-serif;color:#c0392b;">Database connection failed.</h2>
         <p style="font-family:sans-serif">Please ensure MySQL is running and <code>reclaim_db</code> exists.<br>
         Check <code>.env</code> credentials. See <code>README.md</code> for setup.</p>');
}
