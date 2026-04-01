<?php
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT id, name, email FROM users WHERE email LIKE '%douala%' OR name LIKE '%douala%'");
while($row = $res->fetch_assoc()) {
    echo "USER: {$row['id']} | {$row['name']} | {$row['email']}\n";
}
?>
