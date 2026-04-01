<?php
require_once 'includes/config.php';
$db = db();
$em = 'vendor_la_piazza_douala@chopdrop.cm';
$res = $db->query("SELECT * FROM users WHERE email='$em'");
$r = $res->fetch_assoc();
if ($r) {
    echo "Found user!\n";
    print_r($r);
    echo "Password check: " . (password_verify('XvVYMqgW', $r['password']) ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "User NOT found!\n";
}
?>
