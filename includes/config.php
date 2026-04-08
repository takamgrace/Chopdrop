<?php
if (defined('CHOPDROP_DB_LOADED')) return;
define('CHOPDROP_DB_LOADED', true);

// ─── ERROR REPORTING (useful during development) ──────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── LOCAL MySQL CREDENTIALS ──────────────────────────────────────────────
// Edit these four lines to match your WAMP / XAMPP / LAMP setup
$host   = 'localhost';
$user   = 'root';
$pass   = '';             // WAMP default is empty, XAMPP default is empty
$dbname = 'chopdrop';
$port   = 3306;

// ─── SITE SETTINGS ────────────────────────────────────────────────────────
define('SITE_NAME', 'ChopDrop');
define('SITE_URL',  'http://localhost/chopdrop');
define('CURRENCY',  'XAF');

// ─── mysqli-style result wrapper ──────────────────────────────────────────
class DBResultCompat {
    private PDOStatement $stmt;
    public int $num_rows;

    public function __construct(PDOStatement $stmt) {
        $this->stmt     = $stmt;
        $this->num_rows = $stmt->rowCount();
    }

    public function fetch_assoc(): array|false {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Accepts optional MYSQLI_ASSOC constant — just ignores it
    public function fetch_all($mode = null): array {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ─── mysqli-style connection wrapper ─────────────────────────────────────
class DBCompat {
    public PDO     $pdo;
    public int     $insert_id     = 0;
    public int     $affected_rows = 0;
    public ?string $connect_error = null;

    public function __construct(string $dsn, string $user, string $pass) {
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function query(string $sql): DBResultCompat|false {
        try {
            $stmt = $this->pdo->query($sql);
            $this->affected_rows = $stmt->rowCount();
            if (preg_match('/^\s*(INSERT|REPLACE)/i', $sql)) {
                $this->insert_id = (int)$this->pdo->lastInsertId();
            }
            return new DBResultCompat($stmt);
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    public function prepare(string $sql): PDOStatement {
        return $this->pdo->prepare($sql);
    }

    public function real_escape_string(string $s): string {
        return substr($this->pdo->quote($s), 1, -1);
    }

    public function begin_transaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void            { $this->pdo->commit(); }
    public function rollback(): void          { $this->pdo->rollBack(); }
}

// ─── Connect to local MySQL ───────────────────────────────────────────────
try {
    $dsn  = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $conn = new DBCompat($dsn, $user, $pass);
} catch (PDOException $e) {
    die('
    <div style="font-family:sans-serif;padding:40px;background:#1a0a2e;color:#fff;min-height:100vh">
        <h2 style="color:#c084fc">&#x26A0; Database Connection Failed</h2>
        <p style="color:#f87171">' . htmlspecialchars($e->getMessage()) . '</p>
        <hr style="border:1px solid #444;margin:20px 0">
        <p><strong>How to fix:</strong></p>
        <ol style="line-height:2.2;font-family:sans-serif">
            <li>Make sure <b>WAMP / XAMPP / LAMP</b> is running</li>
            <li>Make sure MySQL is started (green icon in WAMP)</li>
            <li>Open <b>phpMyAdmin</b> and create a database called <code>chopdrop_db</code></li>
            <li>Import <code>chopdrop.sql</code> into that database</li>
            <li>If your MySQL password is not empty, edit <code>includes/config.php</code> line 14</li>
        </ol>
        <p style="color:#fbbf24">Host: <code>' . $host . '</code> &nbsp; DB: <code>' . $dbname . '</code> &nbsp; User: <code>' . $user . '</code></p>
    </div>');
}

// ─── Global db() helper ───────────────────────────────────────────────────
if (!function_exists('db')) {
    function db(): DBCompat {
        global $conn;
        return $conn;
    }
}

// ─── Auth helpers ─────────────────────────────────────────────────────────
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool    { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isVendor(): bool   { return isset($_SESSION['role']) && $_SESSION['role'] === 'vendor'; }
function isRider(): bool    { return isset($_SESSION['role']) && $_SESSION['role'] === 'rider'; }

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        flash('error', 'Please log in to continue.');
        $r = (str_contains($_SERVER['SCRIPT_NAME'], '/admin/')) ? '../'.$redirect : $redirect;
        header("Location: $r");
        exit;
    }
}

function requireAdmin(string $redirect = '../login.php'): void {
    if (!isAdmin()) {
        flash('error', 'Administrator access required.');
        header("Location: $redirect"); exit;
    }
}

function requireRider(string $redirect = '../login.php'): void {
    if (!isRider()) {
        flash('error', 'Rider access required.');
        header("Location: $redirect"); exit;
    }
}

function requireAdminOrVendor(string $redirect = '../login.php'): void {
    if (!isAdmin() && !isVendor()) {
        flash('error', 'Unauthorized access.');
        header("Location: $redirect"); exit;
    }
}

function getVendorRid(): int {
    return (int)($_SESSION['restaurant_id'] ?? 0);
}

// ─── Flash message helpers ────────────────────────────────────────────────
function flash(string $key, string $message = ''): ?string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if ($message !== '') {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

// ─── Utility helpers ──────────────────────────────────────────────────────
function e(string $s): string  { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function money(int $n): string { return number_format($n) . ' ' . CURRENCY; }

// ─── Cart helpers ─────────────────────────────────────────────────────────
function cartCount(): int {
    if (!isLoggedIn()) return 0;
    $uid = (int)$_SESSION['user_id'];
    $r   = db()->query("SELECT SUM(quantity) AS c FROM cart WHERE user_id = $uid");
    if (!$r) return 0;
    $row = $r->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

function cartItems(): array {
    if (!isLoggedIn()) return [];
    $uid = (int)$_SESSION['user_id'];
    $r   = db()->query("
        SELECT c.*, f.name, f.price, f.image,
               r.name         AS restaurant_name,
               r.id           AS restaurant_id,
               r.delivery_fee AS delivery_fee
        FROM   cart c
        JOIN   foods       f ON f.id = c.food_id
        JOIN   restaurants r ON r.id = f.restaurant_id
        WHERE  c.user_id = $uid
    ");
    return $r ? $r->fetch_all() : [];
}

function cartTotal(): int {
    $total = 0;
    foreach (cartItems() as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
