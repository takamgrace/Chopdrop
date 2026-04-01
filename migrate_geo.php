<?php require_once 'includes/config.php'; $db=db(); 
// Check if lat exists
$res = $db->query("SHOW COLUMNS FROM restaurants LIKE 'lat'");
if ($res->num_rows === 0) {
    if ($db->query("ALTER TABLE restaurants ADD COLUMN lat DECIMAL(10,8) DEFAULT 4.05000000, ADD COLUMN lng DECIMAL(11,8) DEFAULT 9.70000000")) {
        echo "Columns 'lat' and 'lng' added successfully to 'restaurants' table.\n";
    } else {
        echo "Error adding columns: " . $db->error . "\n";
    }
} else {
    echo "Columns already exist.\n";
}
?>
