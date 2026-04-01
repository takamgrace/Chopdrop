<?php
require_once 'includes/config.php';
$db = db();

$creds = [
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

foreach ($creds as $email => $pass) {
    $em = $db->real_escape_string($email);
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $db->query("UPDATE users SET password='$hash' WHERE email='$em'");
    echo "Updated: $email\n";
}
echo "Done!\n";
?>
