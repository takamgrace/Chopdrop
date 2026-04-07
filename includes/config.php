<?php
if (defined('CHOPDROP_DB_LOADED')) return;
define('CHOPDROP_DB_LOADED', true);

// ─── Load .env file (local development only) ──────────────────────────────
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\n\r\0\x0B\"'");
        if (!getenv($key)) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

// ─── Read credentials from environment ────────────────────────────────────
$host   = getenv('DB_HOST') ?: '127.0.0.1';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'neondb';
$port   = (int)(getenv('DB_PORT') ?: 5432);

define('SITE_NAME', getenv('APP_NAME') ?: 'ChopDrop');
define('SITE_URL',  getenv('SITE_URL') ?: 'http://localhost/chopdrop');
define('CURRENCY',  getenv('CURRENCY') ?: 'XAF');

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
    // Accept optional MYSQLI_ASSOC constant or nothing — both work
    public function fetch_all($mode = null): array {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ─── mysqli-style connection wrapper ──────────────────────────────────────
class DBCompat {
    public PDO    $pdo;
    public ?string $connect_error = null;
    public int     $insert_id     = 0;
    public int     $affected_rows = 0;

    public function __construct(string $dsn, string $user, string $pass) {
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function query(string $sql): DBResultCompat|false {
        // Convert MySQL backticks → PostgreSQL double-quotes
        $sql = str_replace('`', '"', $sql);
        // Convert MySQL RAND() → PostgreSQL RANDOM()
        $sql = preg_replace('/\bRAND\s*\(\s*\)/i', 'RANDOM()', $sql);
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
        $sql = str_replace('`', '"', $sql);
        $sql = preg_replace('/\bRAND\s*\(\s*\)/i', 'RANDOM()', $sql);
        return $this->pdo->prepare($sql);
    }

    public function real_escape_string(string $s): string {
        return substr($this->pdo->quote($s), 1, -1);
    }

    public function begin_transaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void            { $this->pdo->commit(); }
    public function rollback(): void          { $this->pdo->rollBack(); }
}

// ─── Connect to Neon PostgreSQL ───────────────────────────────────────────
try {
    // Extract Neon endpoint ID for SNI (required by Neon)
    // Host: ep-name-123456-pooler.us-east-1.aws.neon.tech
    // Endpoint ID: ep-name-123456
    preg_match('/^(ep-[a-z0-9]+-[a-z0-9]+)/i', $host, $matches);
    $endpoint_id = $matches[1] ?? '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require"
         . ($endpoint_id ? ";options=endpoint%3D{$endpoint_id}" : '');

    $conn = new DBCompat($dsn, $user, $pass);

} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die('
    <div style="font-family:sans-serif;padding:40px;background:#1a0a2e;color:#fff;min-height:100vh">
        <h2 style="color:#c084fc">&#x26A0; Database Connection Failed</h2>
        <p style="color:#f87171">' . htmlspecialchars($e->getMessage()) . '</p>
        <hr style="border:1px solid #444;margin:20px 0">
        <p><strong>Debug Info:</strong></p>
        <ul style="line-height:2;font-family:monospace">
            <li>Host: <code>' . htmlspecialchars($host)   . '</code></li>
            <li>User: <code>' . htmlspecialchars($user)   . '</code></li>
            <li>DB:   <code>' . htmlspecialchars($dbname) . '</code></li>
            <li>Port: <code>' . $port                     . '</code></li>
        </ul>
        <p style="color:#fbbf24">&#x2192; Go to Render &#x2192; Your Web Service &#x2192; Environment and set DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT</p>
    </div>');
}

// ─── Global db() helper ───────────────────────────────────────────────────
if (!function_exists('db')) {
    function db(): DBCompat {
        global $conn;
        return $conn;
    }
}

// ─── Auth helpers ──────────────────────────────────────────────────────────
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool    { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isVendor(): bool   { return isset($_SESSION['role']) && $_SESSION['role'] === 'vendor'; }
function isRider(): bool    { return isset($_SESSION['role']) && $_SESSION['role'] === 'rider'; }

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        flash('error', 'Please log in to continue.');
        header("Location: $redirect");
        exit;
    }
}

// ─── Flash message helpers ────────────────────────────────────────────────
function flash(string $key, string $message = ''): ?string {
    if ($message !== '') {
        // Set flash message
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    // Get and clear flash message
    if (session_status() === PHP_SESSION_NONE) session_start();
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

// ─── Utility helpers ──────────────────────────────────────────────────────
function e(string $s): string  { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function money(int $n): string { return number_format($n) . ' ' . CURRENCY; }

// ─── Cart helpers ──────────────────────────────────────────────────────────
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
               r.name        AS restaurant_name,
               r.id          AS restaurant_id,
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
