<?php
session_start();
require_once 'includes/config.php';

$pageTitle = 'ChopDrop — Luxury Food Delivery';

$search    = trim($_GET['q'] ?? '');
$catFilter = $_GET['cat'] ?? '';
$s = db()->real_escape_string($search);
$c = db()->real_escape_string($catFilter);

$foodWhere = "1=1";
if ($s) $foodWhere .= " AND (f.name LIKE '%$s%' OR f.description LIKE '%$s%')";
if ($c) $foodWhere .= " AND f.category='$c'";

$restaurants = db()->query("SELECT * FROM restaurants WHERE is_open=1 AND (is_active IS NULL OR is_active=1) ORDER BY rating DESC LIMIT 8");
$restaurants = $restaurants ? $restaurants->fetch_all() : [];

$foods = db()->query("SELECT f.*, r.name AS rname, r.id AS restaurant_id FROM foods f JOIN restaurants r ON r.id=f.restaurant_id WHERE $foodWhere ORDER BY RANDOM() LIMIT 12");
$foods = $foods ? $foods->fetch_all() : [];

$categories = db()->query("SELECT DISTINCT category FROM foods ORDER BY category");
$categories = $categories ? $categories->fetch_all() : [];

require_once 'includes/header.php';
?>