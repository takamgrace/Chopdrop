<?php
// update_roles_schema.php
require_once __DIR__ . '/includes/config.php';

try {
    $db = db();
    
    // 1. Update roles in users table
    // Note: We use ALTER TABLE to modify the ENUM
    echo "Updating user roles...\n";
    $db->query("ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'vendor', 'rider') DEFAULT 'customer'");
    
    // 2. Add restaurant_id to users table for vendors
    echo "Adding restaurant_id to users table...\n";
    $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS restaurant_id INT NULL AFTER role");
    $db->query("ALTER TABLE users ADD CONSTRAINT fk_user_restaurant FOREIGN KEY IF NOT EXISTS (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL");
    
    // 3. Add rider_id to orders table for rider assignment
    echo "Adding rider_id to orders table...\n";
    $db->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS rider_id INT NULL AFTER user_id");
    $db->query("ALTER TABLE orders ADD CONSTRAINT fk_order_rider FOREIGN KEY IF NOT EXISTS (rider_id) REFERENCES users(id) ON DELETE SET NULL");
    
    echo "SUCCESS: Database schema updated for multi-role support.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
