<?php
if (defined('CHOPDROP_DB_LOADED')) return;
define('CHOPDROP_DB_LOADED', true);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── Load .env file manually (PHP has no built-in .env reader) ────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;       // skip comments
        if (strpos($line, '=') === false) continue;           // skip malformed
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!getenv($key)) {                                  // don't override system env
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

// ─── Read values (now available via getenv) ───────────────────────────────────
$host   = getenv('DB_HOST')  ?: '127.0.0.1';
$user   = getenv('DB_USER')  ?: 'root';
$pass   = getenv('DB_PASS')  ?: '';
$dbname = getenv('DB_NAME')  ?: 'chopdrop';
$port   = (int)(getenv('DB_PORT') ?: 3306);

define('SITE_NAME', getenv('APP_NAME')  ?: 'ChopDrop');
define('SITE_URL',  getenv('SITE_URL')  ?: 'http://localhost/chopdrop');
define('CURRENCY',  getenv('CURRENCY')  ?: 'XAF');

// ─── Connect to Database ──────────────────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die('<div style="font-family:sans-serif;padding:40px;background:#1a0a2e;color:#fff;min-height:100vh">
        <h2 style="color:#c084fc">Database Connection Error</h2>
        <p style="color:#f87171">' . $e->getMessage() . '</p>
        <p>Host: <code>' . e($host) . '</code> | Port: <code>' . $port . '</code> | DB: <code>' . e($dbname) . '</code></p>
        <p style="color:#fbbf24">If using Railway, make sure you are using the <b>public</b> host (not mysql.railway.internal) when connecting from outside Railway.</p>
    </div>');
}

if (!function_exists('db')) {
    function db() {
        global $conn;
        return $conn;
    }
}


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
