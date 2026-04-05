<?php
if (defined('CHOPDROP_DB_LOADED')) return;
define('CHOPDROP_DB_LOADED', true);

$host   = getenv('DB_HOST');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');
$dbname = getenv('DB_NAME');
$port   = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

if (!function_exists('db')) {
    function db() {
        global $conn;
        return $conn;
    }
}



define('CURRENCY','XAF');



function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function money(int $n): string { return number_format($n) . ' XAF'; }
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isVendor(): bool { return isset($_SESSION['role']) && $_SESSION['role'] === 'vendor'; }
function isRider(): bool { return isset($_SESSION['role']) && $_SESSION['role'] === 'rider'; }
function getVendorRid(): int { return (int)($_SESSION['restaurant_id'] ?? 0); }
function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: '.SITE_URL.'/login.php?redirect='.urlencode($_SERVER['REQUEST_URI'])); exit; }
}
function requireAdmin(): void {
    if (!isAdmin()) { header('Location: '.SITE_URL.'/index.php'); exit; }
}
function requireAdminOrVendor(): void {
    if (!isAdmin() && !isVendor()) { header('Location: '.SITE_URL.'/index.php'); exit; }
}
function requireVendor(): void {
    if (!isVendor()) { header('Location: '.SITE_URL.'/index.php'); exit; }
}
function requireRider(): void {
    if (!isRider()) { header('Location: '.SITE_URL.'/index.php'); exit; }
}
function flash(string $key, string $msg = ''): string {
    if ($msg) { $_SESSION['flash'][$key] = $msg; return ''; }
    $out = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $out;
}
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    $uid = (int)$_SESSION['user_id'];
    $r = db()->query("SELECT SUM(quantity) c FROM cart WHERE user_id=$uid");
    return (int)($r->fetch_assoc()['c'] ?? 0);
}
function cartItems(): array {
    if (!isLoggedIn()) return [];
    $uid = (int)$_SESSION['user_id'];
    $r = db()->query("SELECT c.*,f.name,f.price,f.image,r.name AS restaurant_name,r.id AS restaurant_id, r.delivery_fee
        FROM cart c JOIN foods f ON f.id=c.food_id JOIN restaurants r ON r.id=f.restaurant_id
        WHERE c.user_id=$uid");
    return $r->fetch_all(MYSQLI_ASSOC);
}
function cartTotal(): int {
    return array_sum(array_map(fn($i)=>$i['price']*$i['quantity'], cartItems()));
}
