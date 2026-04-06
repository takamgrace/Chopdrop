<?php
require_once 'includes/config.php';
require_once 'includes/ai_helper.php';

function debugAskAI($query) {
    $apiKey = "AIzaSyD4k9Sw8RgjZ3YKLFp_k8i7VciMYWZ9gIc";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;
    $data = ["contents" => [["parts" => [["text" => $query]]]]];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ["code" => $code, "error" => $err, "response" => $response];
}

echo "DEBUGGING AI CONNECTION DETAILS:\n";
$res = debugAskAI("Hello");
echo "HTTP CODE: " . $res['code'] . "\n";
echo "CURL ERROR: " . $res['error'] . "\n";
echo "RAW RESPONSE: " . $res['response'] . "\n";
