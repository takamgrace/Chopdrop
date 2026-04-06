<?php
require_once 'includes/config.php';
require_once 'includes/ai_helper.php';

echo "1. Testing Database Connection...\n";
try {
    $db = db();
    echo "SUCCESS: DB connected.\n";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }

echo "2. Testing Home Page Queries...\n";
$res = $db->query("SELECT * FROM restaurants LIMIT 1");
if ($res) echo "SUCCESS: Restaurants query okay.\n"; else echo "ERROR: Restaurants query failed: " . $db->error . "\n";

$res = $db->query("SELECT f.*,r.name AS rname FROM foods f JOIN restaurants r ON r.id=f.restaurant_id LIMIT 1");
if ($res) echo "SUCCESS: Foods query okay.\n"; else echo "ERROR: Foods query failed: " . $db->error . "\n";

echo "3. Testing AI Assistant connection (Gemini)...\n";
$ai = askChopDropAI("Hello", "Be brief.");
echo "AI Response: " . $ai . "\n";

echo "4. Testing AJAX Chatbot Logic...\n";
// Manually fetch context like ajax_chatbot.php does
$menuStr = "";
$res = $db->query("SELECT f.name, f.price, r.name as rname FROM foods f JOIN restaurants r ON r.id=f.restaurant_id WHERE f.is_available=1 LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo "SUCCESS: Context fetch okay: $row[name]\n";
} else {
    echo "WARNING: No available foods found in DB to provide context.\n";
}
echo "HEALTH CHECK COMPLETE.\n";
