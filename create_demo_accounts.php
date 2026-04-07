<?php
require_once 'includes/config.php';
$db = db();

function createUser($name, $email, $role, $rid) {
    global $db;
    
    // Passwords from README.md
    $passwords = [
        'vendor_mama_africa_kitchen@chopdrop.cm' => 'KQzDerNZ',
        'rider1_mama_africa_kitchen@chopdrop.cm' => '0t8a25yN',
        'rider2_mama_africa_kitchen@chopdrop.cm' => '5pmCtuSd',
        'vendor_la_piazza_douala@chopdrop.cm' => 'XvVYMqgW',
        'rider1_la_piazza_douala@chopdrop.cm' => '4j2WUTLt',
        'rider2_la_piazza_douala@chopdrop.cm' => '2cDa4ds8',
        'vendor_food_burger@chopdrop.cm' => 'ArPtwh7I',
        'rider1_food_burger@chopdrop.cm' => '0gWFxQam',
        'rider2_food_burger@chopdrop.cm' => 'UlbtQSAN',
        'vendor_le_poulet_dore@chopdrop.cm' => 'iZqtu5Es',
        'rider1_le_poulet_dore@chopdrop.cm' => 'mYwNAW0r',
        'rider2_le_poulet_dore@chopdrop.cm' => 'uMZYya5h',
        'vendor_cmer_food@chopdrop.cm' => 'Z4ODRjwN',
        'rider1_cmer_food@chopdrop.cm' => 'dyLiq0gW',
        'rider2_cmer_food@chopdrop.cm' => 'GRct2Dh8',
        'vendor_knc@chopdrop.cm' => 'wijXxSNC',
        'rider1_knc@chopdrop.cm' => 'UetrwAf0',
        'rider2_knc@chopdrop.cm' => '0HICmi6k',
        'vendor_chicken_burger@chopdrop.cm' => 'YKHeGmUS',
        'rider1_chicken_burger@chopdrop.cm' => '1SQmGFwv',
        'rider2_chicken_burger@chopdrop.cm' => '1SHOm8vT',
        'vendor_kamer__dishes@chopdrop.cm' => 'edzTmFMy',
        'rider1_kamer__dishes@chopdrop.cm' => 'EK790PtD',
        'rider2_kamer__dishes@chopdrop.cm' => 'jIPZCqiz'
    ];
    
    $raw_pass = $passwords[$email] ?? 'password123';
    $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
    
    $n = $db->real_escape_string($name);
    $e = $db->real_escape_string($email);
    // Check if exists
    $check = $db->query("SELECT id FROM users WHERE email='$e'")->fetch_assoc();
    if ($check) {
        $db->query("UPDATE users SET password='$pass' WHERE email='$e'");
        return;
    }
    $db->query("INSERT INTO users (name, email, password, role, restaurant_id) VALUES ('$n', '$e', '$pass', '$role', $rid)");
}

$restaurants = $db->query("SELECT id, name FROM restaurants")->fetch_all();

foreach ($restaurants as $r) {
    $rid = $r['id'];
    $slug = strtolower(str_replace(' ', '_', $r['name']));
    
    // Create 1 Vendor
    createUser($r['name'] . " Vendor", "vendor_" . $slug . "@chopdrop.cm", 'vendor', $rid);
    
    // Create 2 Riders
    createUser($r['name'] . " Rider 1", "rider1_" . $slug . "@chopdrop.cm", 'rider', $rid);
    createUser($r['name'] . " Rider 2", "rider2_" . $slug . "@chopdrop.cm", 'rider', $rid);
}

echo "Created vendors and riders for " . count($restaurants) . " restaurants.\n";
