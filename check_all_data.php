<?php
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT id, name, email FROM users");
while($row = $res->fetch_assoc()) {
    echo "USER: {$row['id']} {$row['name']} {$row['email']}\n";
}
echo "\n======== REstaurants ====\n";
$res = $db->query("SELECT id, name FROM restaurants");
while($row = $res->fetch_assoc()) {
    echo "REST: {$row['id']} {$row['name']}\n";
}
?>
