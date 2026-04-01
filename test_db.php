<?php
require_once 'includes/config.php';
try {
    $db = db();
    echo "DB Connection Successful";
} catch (Exception $e) {
    echo "DB Connection Failed: " . $e->getMessage();
}
?>
