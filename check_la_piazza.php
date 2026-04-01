<?php
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT * FROM users WHERE name LIKE '%la piazza%' OR email LIKE '%la_piazza%' OR email LIKE '%piazza%'");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | NAME: {$row['name']} | EMAIL: {$row['email']} | ROLE: {$row['role']} | RID: {$row['restaurant_id']}\n";
    echo "Password verify 'XvVYMqgW' / '4j2WUTLt' / '2cDa4ds8' => " . (password_verify('XvVYMqgW', $row['password'])?'XvVYMqgW':'(no)') . "\n";
}
?>
