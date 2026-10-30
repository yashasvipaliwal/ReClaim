<?php
require_once __DIR__ . '/config/db.php';

$email = 'admin@reclaim.local';
$newPassword = 'Reclaim@12345';

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "UPDATE users SET password_hash = ? WHERE email = ? AND role = 'admin'"
);

$stmt->execute([$hash, $email]);

if ($stmt->rowCount() > 0) {
    echo "Admin password reset successfully!";
} else {
    echo "Admin not found or password unchanged.";
}