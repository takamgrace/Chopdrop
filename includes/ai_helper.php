<?php
function askChopDropAI($userQuery, $systemPrompt = "You are the ChopDrop Assistant.") {
    $apiKey = "AIzaSyD4k9Sw8RgjZ3YKLFp_k8i7VciMYWZ9gIc";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [[ "parts" => [[ "text" => $systemPrompt . "\n\nUser Question: " . $userQuery ]] ]]
    ];

    $maxRetries = 1;
    $attempt = 0;

    do {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($httpCode === 200) {
            return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } 
        
        if ($httpCode === 429 && $attempt < $maxRetries) {
            $attempt++;
            sleep(1);
            continue; 
        }
        break;
    } while ($attempt <= $maxRetries);

    // --- SMART FALLBACK (IF API FAILS OR QUOTA HIT) ---
    $db = db();
    $q = strtolower(trim($userQuery));
    
    // 0. Extract Budget (Look for numbers between 100 and 100,000)
    $budget = null;
    if (preg_match('/\b(\d{3,6})\b/', $q, $matches)) {
        $budget = (int)$matches[1];
    }
    $priceFilter = $budget ? " AND f.price <= $budget " : "";

    // 1. Identify "Craving" Keyword (Smart Extraction)
    $keywords = ['burger', 'pizza', 'achu', 'eru', 'fish', 'chicken', 'ndole', 'fries', 'dessert', 'drink', 'water', 'rice'];
    $foundKeyword = null;
    foreach ($keywords as $kw) {
        if (str_contains($q, $kw)) {
            $foundKeyword = $kw;
            break;
        }
    }

    if ($foundKeyword) {
        $s = $db->real_escape_string($foundKeyword);
        // FETCH THE SINGLE MOST EXPENSIVE (BEST QUALITY) OPTION WITHIN BUDGET FOR THIS CRAVING
        $res = $db->query("
            SELECT f.name, f.price, r.name as rname 
            FROM foods f 
            JOIN restaurants r ON r.id=f.restaurant_id 
            WHERE (f.name LIKE '%$s%' OR f.category LIKE '%$s%') AND f.is_available=1 $priceFilter
            ORDER BY f.price DESC LIMIT 1
        ");
        
        if ($res && $row = $res->fetch_assoc()) {
            $budgetMsg = $budget ? " (within your budget of $budget XAF)" : "";
            return "For an elite $foundKeyword experience$budgetMsg, I highly recommend the **$row[name]** at **$row[rname]**. At " . number_format($row['price']) . " XAF, it represents the absolute top-tier quality available for this selection right now. 🍱✨";
        }
    }

    // 2. Default recommendation for general queries or if budget prevents keyword match
    $res = $db->query("SELECT f.name, f.price, r.name as rname FROM foods f JOIN restaurants r ON r.id=f.restaurant_id WHERE f.is_available=1 $priceFilter ORDER BY f.price DESC LIMIT 3");
    
    if ($budget) {
        $reply = "I've carefully curated the city's finest options that fit perfectly within your **$budget XAF** budget:\n\n";
    } else {
        $reply = "Based on our current curated list of the city's most exclusive culinary offerings, here are the top-tier selections for a VIP like yourself:\n\n";
    }

    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            $reply .= "✨ **$row[name]** at $row[rname] (" . number_format($row['price']) . " XAF)\n";
        }
        return $reply . "\nEven within a budget, we only suggest the absolute best quality available! 🥂";
    }

    return "I couldn't find any premium options in that specific range right now, but I recommend checking out our main menu for some amazing value deals! 🍱";
}
?>