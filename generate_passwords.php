<?php
require_once 'includes/config.php';
$db = db();

function generatePassword($length = 8) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$restaurants = $db->query("SELECT id, name FROM restaurants")->fetch_all();
$creds = [];

$output = "| Restaurant | Role | Email | Password |\n";
$output .= "| :--- | :--- | :--- | :--- |\n";

foreach ($restaurants as $r) {
    $rid = $r['id'];
    $slug = strtolower(str_replace(' ', '_', $r['name']));
    
    // Vendor
    $v_email = "vendor_" . $slug . "@chopdrop.cm";
    $v_pass = generatePassword();
    $v_hash = password_hash($v_pass, PASSWORD_DEFAULT);
    
    // Rider 1
    $r1_email = "rider1_" . $slug . "@chopdrop.cm";
    $r1_pass = generatePassword();
    $r1_hash = password_hash($r1_pass, PASSWORD_DEFAULT);
    
    // Rider 2
    $r2_email = "rider2_" . $slug . "@chopdrop.cm";
    $r2_pass = generatePassword();
    $r2_hash = password_hash($r2_pass, PASSWORD_DEFAULT);
    
    // Update
    $db->query("UPDATE users SET password='$v_hash' WHERE email='$v_email'");
    $db->query("UPDATE users SET password='$r1_hash' WHERE email='$r1_email'");
    $db->query("UPDATE users SET password='$r2_hash' WHERE email='$r2_email'");
    
    $output .= "| **" . $r['name'] . "** | Vendor | `" . $v_email . "` | `" . $v_pass . "` |\n";
    $output .= "| | Rider 1 | `" . $r1_email . "` | `" . $r1_pass . "` |\n";
    $output .= "| | Rider 2 | `" . $r2_email . "` | `" . $r2_pass . "` |\n";
}

file_put_contents('creds_plain.txt', $output);
echo "Generated and saved to creds_plain.txt\n";
