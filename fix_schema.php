<?php
require_once 'includes/config.php';
$db = db();
$sql = "ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1";
if ($db->query($sql)) {
    echo "SUCCESS: is_active column added to restaurants table.";
} else {
    echo "ERROR: " . $db->error;
}
?>
