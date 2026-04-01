<?php
require_once __DIR__ . '/includes/config.php';
try {
    $db = db();
    $db->query("ALTER TABLE restaurants ADD COLUMN IF NOT EXISTS logo VARCHAR(500) DEFAULT ''");
    echo "SUCCESS: logo column created\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "SUCCESS: column already exists\n";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
