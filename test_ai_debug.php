<?php
require_once 'includes/ai_helper.php';
$res = askChopDropAI("Hello", "Be a friendly helper.");
echo "RESPONSE FROM Gemini: " . $res . "\n";

// Extra debugging
$apiKey = "AIzaSyD4k9Sw8RgjZ3YKLFp_k8i7VciMYWZ9gIc";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;
$data = ["contents" => [["parts" => [["text" => "Hello"]]]]];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

echo "RAW RESPONSE: " . $response . "\n";
echo "CURL ERROR: " . $err . "\n";
