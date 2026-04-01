<?php require_once 'includes/config.php'; $db=db(); 
// Check if is_reported exists
$res = $db->query("SHOW COLUMNS FROM orders LIKE 'is_reported'");
if ($res->num_rows === 0) {
    if ($db->query("ALTER TABLE orders ADD COLUMN is_reported TINYINT(1) DEFAULT 0, ADD COLUMN report_at DATETIME DEFAULT NULL")) {
        echo "Columns 'is_reported' and 'report_at' added successfully to 'orders' table.\n";
    } else {
        echo "Error adding columns: " . $db->error . "\n";
    }
} else {
    echo "Columns already exist.\n";
}
?>
