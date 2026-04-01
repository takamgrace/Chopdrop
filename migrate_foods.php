<?php require_once 'includes/config.php'; $db=db(); 
// Check if is_available exists
$res = $db->query("SHOW COLUMNS FROM foods LIKE 'is_available'");
if ($res->num_rows === 0) {
    if ($db->query("ALTER TABLE foods ADD COLUMN is_available TINYINT(1) DEFAULT 1")) {
        echo "Column 'is_available' added successfully to 'foods' table.\n";
    } else {
        echo "Error adding column: " . $db->error . "\n";
    }
} else {
    echo "Column 'is_available' already exists.\n";
}
?>
