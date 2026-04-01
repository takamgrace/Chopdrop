<?php
require_once 'includes/config.php';
$db = db();
echo "Restaurants:\n";
$res = $db->query("SELECT id, name FROM restaurants");
while($row = $res->fetch_assoc()) echo "REST: {$row['id']} {$row['name']}\n";
echo "\nUsers with 'vendor' role:\n";
$res = $db->query("SELECT id, name, email FROM users WHERE role='vendor'");
while($row = $res->fetch_assoc()) echo "USER: {$row['id']} {$row['name']} {$row['email']}\n";
?>
