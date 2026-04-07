<?php
$host = 'junction.proxy.rlwy.net';
$user = 'root';
$pass = 'WNhHqzmBSbSbNLjbMvQPYzyfeWHXDfnH';
$db = 'railway';
$port = 51445;

echo "Testing MySQL...\n";
try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli->connect_error) {
        echo "MySQLi Error: " . $mysqli->connect_error . "\n";
    } else {
        echo "MySQLi Connected successfully!\n";
    }
} catch (Exception $e) {
    echo "MySQLi Exception: " . $e->getMessage() . "\n";
}

echo "Testing Postgres...\n";
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "PDO PGSQL Connected successfully!\n";
} catch (Exception $e) {
    echo "PDO PGSQL Exception: " . $e->getMessage() . "\n";
}
