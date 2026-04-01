<?php
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT u.*, r.name as rname FROM users u LEFT JOIN restaurants r ON r.id=u.restaurant_id WHERE u.email LIKE '%piazza%' OR u.name LIKE '%piazza%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
