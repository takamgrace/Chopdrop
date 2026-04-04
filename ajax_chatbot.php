<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/ai_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['message'] ?? '');

if (empty($query)) {
    echo json_encode(['error' => 'Question cannot be empty.']);
    exit;
}

$db = db();
$s = $db->real_escape_string($query);

// 1. Fetch Highly Relevant Items based on User Query
$relevantRes = $db->query("
    SELECT f.name, f.price, r.name as rname, f.category 
    FROM foods f 
    JOIN restaurants r ON r.id=f.restaurant_id 
    WHERE f.is_available=1 
    AND (f.name LIKE '%$s%' OR f.description LIKE '%$s%' OR f.category LIKE '%$s%' OR r.cuisine LIKE '%$s%')
    LIMIT 20
");

// 2. Fetch "Premium/Best" Items (Most Expensive) across all restaurants
$premiumRes = $db->query("
    SELECT f.name, f.price, r.name as rname 
    FROM foods f 
    JOIN restaurants r ON r.id=f.restaurant_id 
    WHERE f.is_available=1 AND r.is_open=1
    ORDER BY f.price DESC LIMIT 15
");

$menuStr = "--- RELEVANT MENU ITEMS ---\n";
while($row = $relevantRes->fetch_assoc()) {
    $menuStr .= "- $row[name] ($row[category]) for $row[price] XAF at $row[rname]\n";
}

$menuStr .= "\n--- PREMIUM/LUXURY SELECTIONS (HIGHLIGHT THESE AS THE BEST) ---\n";
while($row = $premiumRes->fetch_assoc()) {
    $menuStr .= "- $row[name] for $row[price] XAF at $row[rname] (TOP QUALITY)\n";
}

$systemPrompt = "SYSTEM ROLE:
You are the \"ChopDrop AI Concierge,\" a sophisticated and culturally proud food expert in Cameroon. You manage a diverse menu featuring Traditional Cameroonian, Italian, European, and Fast Food (Burgers/Ice Cream).

CORE MISSION:
Provide appetizing, culturally-rich recommendations that feel like a personal concierge service.

--- 🍱 PAIRING & CULTURAL LOGIC ---
1. TRADITIONAL: If a user selects a Traditional dish (Ndolé, Achu, Suya), strongly suggest 'Palm Wine' for an authentic local experience. Mention it is a \"fresh, traditional Cameroonian favorite.\"
2. FAST FOOD: For Burgers, always suggest a 'Cold Coca-Cola' or a side of 'Fries'.
3. THE SWEET FINISH: Recommend 'Ice Cream' as a dessert after spicy meals or as a treat for kids.
4. USER VIBE: 
   - If Hot/Tired: Suggest Ice Cream or a Cold Coke.
   - If Celebrating: Suggest a \"Family Feast\" with multiple dishes and Palm Wine.

--- 💎 QUALITY & VALUE RULES ---
- VALUE OVER PRICE: Do not judge by price alone. A 1,500 CFA item can be \"better\" than a 5,000 CFA item if it has higher quality/ratings. Focus on excellence.
- TONE: Energetic, appetizing, and culturally proud. Use phrases like: \"The perfect match,\" \"A local classic,\" or \"Cool down with...\"

--- 📜 AVAILABLE MENU CONTEXT ---
$menuStr

--- 💬 USER INTERACTION ---
Based on the rules above and the menu provided, assist the customer now:";

$response = askChopDropAI($query, $systemPrompt);
echo json_encode(['reply' => $response]);
