<?php
require_once __DIR__ . '/includes/config.php';
$db = db();
$res = $db->query("SELECT name, email, role FROM users");
while($row = $res->fetch_assoc()) {
    echo "[$row[role]] Name: $row[name], Email: $row[email]\n";
}
echo "\n--- Global Riders (from seeding) ---\n";
echo "rider1@chopdrop.com to rider6@chopdrop.com / Pass: rider123\n";
