<?php require_once 'includes/config.php'; $db=db(); 
// Check if is_active exists
$res = $db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($res->num_rows === 0) {
    if ($db->query("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1")) {
        echo "Column 'is_active' added successfully to 'users' table.\n";
    } else {
        echo "Error adding column: " . $db->error . "\n";
    }
} else {
    echo "Column 'is_active' already exists.\n";
}
?>
