<?php
echo "<pre>";
echo "HOST: "   . getenv('DB_HOST') . "\n";
echo "USER: "   . getenv('DB_USER') . "\n";
echo "DBNAME: " . getenv('DB_NAME') . "\n";
echo "PORT: "   . getenv('DB_PORT') . "\n";
echo "PASS set: " . (getenv('DB_PASS') ? 'YES' : 'NO') . "\n";
echo "</pre>";
exit;
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "DEBUG: Starting session...<br>";
session_start();
echo "DEBUG: Session started.<br>";
require_once 'includes/config.php';
echo "DEBUG: Config loaded.<br>";

$db = db();
echo "DEBUG: DB connection obtained.<br>";

echo "DEBUG: Running restaurant query...<br>";
$restaurants = $db->query("SELECT * FROM restaurants LIMIT 1");
if (!$restaurants) echo "DEBUG: Restaurant query FAILED: " . $db->error . "<br>";
else echo "DEBUG: Restaurant query OK.<br>";

echo "DEBUG: Running food query...<br>";
$foods = $db->query("SELECT * FROM foods LIMIT 1");
if (!$foods) echo "DEBUG: Food query FAILED: " . $db->error . "<br>";
else echo "DEBUG: Food query OK.<br>";

echo "DEBUG: Including header...<br>";
require_once 'includes/header.php';
echo "DEBUG: Header included.<br>";
?>
<h1>DEBUG PAGE WORKS</h1>
