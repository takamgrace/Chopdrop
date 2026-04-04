<?php
require_once __DIR__ . '/includes/config.php';
$db = db();

// Remove all current riders to start fresh
$db->query("DELETE FROM users WHERE role='rider'");

$pass = password_hash('rider123', PASSWORD_DEFAULT);
$riders = [
    ['rider 1', 'rider1@gmail.com', '670000001'],
    ['rider 2', 'rider2@gmail.com', '670000002'],
    ['rider 3', 'rider3@gmail.com', '670000003'],
    ['rider 4', 'rider4@gmail.com', '670000004'],
    ['rider 5', 'rider5@gmail.com', '670000005'],
    ['rider 6', 'rider6@gmail.com', '670000006'],
];

foreach ($riders as $r) {
    $name = $db->real_escape_string($r[0]);
    $email = $db->real_escape_string($r[1]);
    $phone = $db->real_escape_string($r[2]);
    $db->query("INSERT INTO users (name, email, password, role, is_online, is_active, phone) 
                VALUES ('$name', '$email', '$pass', 'rider', 1, 1, '$phone')");
}

echo "Created 6 global riders successfully.\n";
echo "Password for all riders is: rider123\n";
