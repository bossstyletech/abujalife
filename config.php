<?php
/**
 * Abuja Life - Global Configuration & Database Bootstrapper
 * Supports Railway MySQL, standard MySQL, with automatic SQLite zero-config fallback.
 */

// ----------------------------------------------------
// Persistent Session Configuration (1 Year Duration - Never Logged Out)
// ----------------------------------------------------
@ini_set('session.gc_maxlifetime', '31536000');
@ini_set('session.cookie_lifetime', '31536000');
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 31536000,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

// ----------------------------------------------------
// 1. Database Credentials (Configurable via ENV or Railway Defaults)
// ----------------------------------------------------
$rawHost = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '';
$rawPort = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
$rawName = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'abuja_life';
$rawUser = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
$rawPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');

// If Railway MYSQL_URL is provided, parse it
if ($mysqlUrl = getenv('MYSQL_URL')) {
    $parsed = parse_url($mysqlUrl);
    if (!empty($parsed['host'])) $rawHost = $parsed['host'];
    if (!empty($parsed['port'])) $rawPort = $parsed['port'];
    if (!empty($parsed['path'])) $rawName = ltrim($parsed['path'], '/');
    if (!empty($parsed['user'])) $rawUser = $parsed['user'];
    if (isset($parsed['pass'])) $rawPass = $parsed['pass'];
}

define('DB_HOST', $rawHost ?: '');
define('DB_PORT', $rawPort);
define('DB_NAME', $rawName);
define('DB_USER', $rawUser);
define('DB_PASS', $rawPass);
define('DB_CHARSET', 'utf8mb4');
define('HAS_MYSQL_CONFIG', !empty($rawHost));

define('APP_NAME', 'Abuja Life');
define('APP_VERSION', '1.0.0');
define('CURRENCY_SYMBOL', '₦');

// Districts in Abuja Federal Capital Territory
define('ABUJA_DISTRICTS', [
    'Kubwa' => ['tier' => 1, 'min_cred' => 0, 'travel_cost' => 500, 'desc' => 'Bustling satellite town with train terminal.'],
    'Lugbe' => ['tier' => 1, 'min_cred' => 0, 'travel_cost' => 600, 'desc' => 'High-energy hub along the Airport Expressway.'],
    'Gwarinpa' => ['tier' => 2, 'min_cred' => 15, 'travel_cost' => 1500, 'desc' => 'Massive residential estate packed with shawarma & cafes.'],
    'Garki' => ['tier' => 2, 'min_cred' => 20, 'travel_cost' => 2000, 'desc' => 'Historic administrative area with vibrant nightlife.'],
    'Central Area' => ['tier' => 3, 'min_cred' => 30, 'travel_cost' => 2500, 'desc' => 'Heart of ministries, embassies, and CBN headquarters.'],
    'Wuse 2' => ['tier' => 3, 'min_cred' => 40, 'travel_cost' => 3000, 'desc' => 'Abuja luxury nightlife, boutiques, and tech lounges.'],
    'Maitama' => ['tier' => 4, 'min_cred' => 60, 'travel_cost' => 5000, 'desc' => 'Diplomatic and billionaire enclave with serene tree-lined boulevards.'],
    'Asokoro' => ['tier' => 5, 'min_cred' => 75, 'travel_cost' => 7500, 'desc' => 'Power corridor near Presidential Villa and top dignitaries.']
]);

/**
 * Get PDO Database Connection
 * Uses MySQL if Railway/remote credentials exist; otherwise defaults straight to SQLite
 * with zero connection lag and zero Connection Refused errors!
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $isExplicitSqlite = (getenv('DB_CONNECTION') === 'sqlite');

    if (!$isExplicitSqlite && HAS_MYSQL_CONFIG && extension_loaded('pdo_mysql')) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 2,
        ];

        try {
            $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
            if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
            return $pdo;
        } catch (PDOException $e) {
            // If database 1049 (Unknown DB), try to create
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $rawPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
                    
                    $sqlFile = __DIR__ . '/db.sql';
                    if (file_exists($sqlFile)) {
                        $pdo->exec(file_get_contents($sqlFile));
                    }
                    if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
                    return $pdo;
                } catch (Exception $inner) {
                    // Fallback to SQLite
                }
            }
            // Fall through to SQLite fallback on any connection refusal
        }
    }

    // --- Automatic SQLite Fallback ---
    try {
        $sqliteFile = __DIR__ . '/database.sqlite';
        $needsInit = !file_exists($sqliteFile) || filesize($sqliteFile) === 0;

        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        if ($needsInit) {
            $schemaFile = __DIR__ . '/db_sqlite.sql';
            if (file_exists($schemaFile)) {
                $sqlContent = file_get_contents($schemaFile);
                $pdo->exec($sqlContent);
            }
        }

        if (!defined('DB_DRIVER')) define('DB_DRIVER', 'sqlite');
        return $pdo;
    } catch (Exception $sqlEx) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database initialization error: ' . $sqlEx->getMessage()
        ]);
        exit;
    }
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    return trim(htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8'));
}

function formatNaira($amount) {
    return '₦' . number_format((float)$amount, 2);
}

function getAuthUserId() {
    if (!empty($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }

    // Auto-restore session from persistent remember token cookie
    if (!empty($_COOKIE['abuja_remember_token'])) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE remember_token = ? LIMIT 1");
            $stmt->execute([$_COOKIE['abuja_remember_token']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                return (int)$user['id'];
            }
        } catch (Exception $e) {
            // Ignore error and fall through
        }
    }

    return null;
}

function issueRememberToken($userId) {
    try {
        $token = bin2hex(random_bytes(32));
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$token, $userId]);
        if (!headers_sent()) {
            setcookie('abuja_remember_token', $token, time() + 31536000, '/', '', false, true);
        }
        return $token;
    } catch (Exception $e) {
        return null;
    }
}

function requireAuth() {
    $userId = getAuthUserId();
    if (!$userId) {
        jsonResponse([
            'success' => false,
            'error' => 'Authentication required. Please log in.',
            'redirect' => 'index.php'
        ], 401);
    }
    return $userId;
}

function getUserCharacter($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT c.*, 
               j.title AS job_title, j.daily_salary AS job_salary, j.category AS job_category,
               v.name AS vehicle_name, v.brand AS vehicle_brand,
               p.name AS property_name, p.district AS property_district
        FROM characters c
        LEFT JOIN jobs j ON c.current_job_id = j.id
        LEFT JOIN vehicles v ON c.primary_vehicle_id = v.id
        LEFT JOIN properties p ON c.primary_property_id = p.id
        WHERE c.user_id = ? AND c.is_alive = 1
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function logActivity($characterId, $actionType, $message, $cashChange = 0, $energyChange = 0, $happinessChange = 0) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (character_id, action_type, message, cash_change, energy_change, happiness_change)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$characterId, $actionType, $message, $cashChange, $energyChange, $happinessChange]);
    } catch (Exception $e) {
        // Continue silently if log fails
    }
}
