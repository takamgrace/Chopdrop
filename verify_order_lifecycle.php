<?php
require_once __DIR__ . '/includes/config.php';
$db = db();

// 1. Setup a test order
$db->query("INSERT INTO orders (user_id, restaurant_id, total_amount, status) VALUES (1, 1, 5000, 'pending')");
$oid = $db->insert_id;
echo "Created Test Order #$oid (Status: pending)\n";

// 2. Mock Vendor Session
$_SESSION['user_id'] = 2; // Assume ID 2 is the vendor for restaurant 1
$_SESSION['role'] = 'vendor';
$_SESSION['restaurant_id'] = 1;

// 3. Confirm Order
$db->query("UPDATE orders SET status='confirmed' WHERE id=$oid");
$status = $db->query("SELECT status FROM orders WHERE id=$oid")->fetch_assoc()['status'];
echo "Updated Status (Confirm): $status\n";

// 4. Start Preparing
$db->query("UPDATE orders SET status='preparing' WHERE id=$oid");
$status = $db->query("SELECT status FROM orders WHERE id=$oid")->fetch_assoc()['status'];
echo "Updated Status (Prepare): $status\n";

// 5. Dispatch (Ready)
$rider = $db->query("SELECT id FROM users WHERE role='rider' LIMIT 1")->fetch_assoc();
$ridVal = $rider['id'];
$db->query("UPDATE orders SET status='ready', rider_id=$ridVal WHERE id=$oid");
$order = $db->query("SELECT status, rider_id FROM orders WHERE id=$oid")->fetch_assoc();
echo "Updated Status (Dispatch): {$order['status']} (Rider: {$order['rider_id']})\n";

// 6. Cleanup
$db->query("DELETE FROM orders WHERE id=$oid");
echo "Test Order #$oid deleted.\n";
