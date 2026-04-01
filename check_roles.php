<?php 
require_once 'includes/config.php';
$db = db();
$res = $db->query("SELECT id, email, role, is_active FROM users WHERE role = 'vendor' OR role = 'restaurant'");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
