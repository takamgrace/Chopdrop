<?php
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT id, name, email, role, is_active FROM users");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | ROLE: {$row['role']} | ACTIVE: {$row['is_active']} | EMAIL: {$row['email']}\n";
}
?>
