<?php
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT * FROM users");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | NAME: {$row['name']} | EMAIL: {$row['email']} | ROLE: {$row['role']} | RID: {$row['restaurant_id']}\n";
}
?>
