<?php
// update_rider_status_schema.php
require_once __DIR__ . '/includes/config.php';

try {
    $db = db();
    
    echo "Adding is_online to users table...\n";
    $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) DEFAULT 1 AFTER restaurant_id");
    
    echo "SUCCESS: Database schema updated for rider status.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
