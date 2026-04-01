
<?php
require_once __DIR__ . '/includes/config.php';
$db = db();

// 1. Check a rider (Pick one from Mama Africa Kitchen)
$rider = $db->query("SELECT id, name, is_online FROM users WHERE role='rider' AND restaurant_id=1 LIMIT 1")->fetch_assoc();
if (!$rider) die("No rider found for restaurant 1.\n");

$rid = $rider['id'];
echo "Original Status for {$rider['name']} (ID: $rid): " . ($rider['is_online'] ? 'Online' : 'Offline') . "\n";

// 2. Toggle Status (Simulate rider action)
$db->query("UPDATE users SET is_online=0 WHERE id=$rid");
echo "Updated Status (Toggle to 0): " . ($db->query("SELECT is_online FROM users WHERE id=$rid")->fetch_assoc()['is_online'] ? 'Online' : 'Offline') . "\n";

// 3. Verify Vendor List (Should NOT show this rider)
$riders = $db->query("SELECT id, name FROM users WHERE role='rider' AND restaurant_id=1 AND is_active=1 AND is_online=1")->fetch_all(MYSQLI_ASSOC);
$found = false;
foreach ($riders as $r) {
    if ($r['id'] == $rid) $found = true;
}
echo "Vendor can see this rider?: " . ($found ? 'YES (Fail)' : 'NO (Pass)') . "\n";

// 4. Reset Status
$db->query("UPDATE users SET is_online=1 WHERE id=$rid");
echo "Final Status (Reset to 1): " . ($db->query("SELECT is_online FROM users WHERE id=$rid")->fetch_assoc()['is_online'] ? 'Online' : 'Offline') . "\n";

// 5. Verify Vendor List (Should show this rider)
$riders = $db->query("SELECT id, name FROM users WHERE role='rider' AND restaurant_id=1 AND is_active=1 AND is_online=1")->fetch_all(MYSQLI_ASSOC);
$found = false;
foreach ($riders as $r) {
    if ($r['id'] == $rid) $found = true;
}
echo "Vendor can see this rider now?: " . ($found ? 'YES (Pass)' : 'NO (Fail)') . "\n";
