<?php
require_once __DIR__ . '/includes/config.php';
$db = db();
$f = [];
$q = $db->query('SELECT f.name, f.price, r.name as rname FROM foods f JOIN restaurants r ON r.id=f.restaurant_id LIMIT 50');
while($row = $q->fetch_assoc()) {
    $f[] = "$row[name] ($row[price] XAF) at $row[rname]";
}
echo implode("\n", $f);
