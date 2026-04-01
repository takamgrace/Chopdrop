<?php
require_once 'includes/config.php';
$db = db();
$bad_email = "vendor_la_pizza_douala@chopdrop.cm";
$good_email = "vendor_la_piazza_douala@chopdrop.cm";
$pass = password_hash('XvVYMqgW', PASSWORD_DEFAULT);
$db->query("UPDATE users SET email='$good_email', password='$pass' WHERE email='$bad_email'");
echo "Fixed DB mapping.\n";

$bad_email_r1 = "rider1_la_pizza_douala@chopdrop.cm";
$good_email_r1 = "rider1_la_piazza_douala@chopdrop.cm";
$pass_r1 = password_hash('4j2WUTLt', PASSWORD_DEFAULT);
$db->query("UPDATE users SET email='$good_email_r1', password='$pass_r1' WHERE email='$bad_email_r1'");

$bad_email_r2 = "rider2_la_pizza_douala@chopdrop.cm";
$good_email_r2 = "rider2_la_piazza_douala@chopdrop.cm";
$pass_r2 = password_hash('2cDa4ds8', PASSWORD_DEFAULT);
$db->query("UPDATE users SET email='$good_email_r2', password='$pass_r2' WHERE email='$bad_email_r2'");
echo "Fixed Riders.\n";

$db->query("UPDATE restaurants SET name='La Piazza Douala' WHERE name='La Pizza Douala'");
echo "Fixed Restaurant Name.\n";
?>
