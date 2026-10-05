<?php
/**
 * Abuja Life - Global Configuration & Database Bootstrapper
 * Built for standard PHP 7.4+ / 8.x environments (XAMPP, Laragon, LAMP, Docker)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------
// 1. Database Credentials (Configurable via ENV or defaults)
// ----------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'abuja_life');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

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
 * Auto-creates the database and imports db.sql if database does not exist.
 */
function getDbConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // Try connecting directly to the targeted database
        $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database unknown (1049), attempt auto-creation
        if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
            try {
                $rawPdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo = new PDO($dsn . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
                
                // Auto seed schema from db.sql if exists
                $sqlFile = __DIR__ . '/db.sql';
                if (file_exists($sqlFile)) {
                    $sqlContent = file_get_contents($sqlFile);
                    $pdo->exec($sqlContent);
                }
            } catch (Exception $innerEx) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Database connection and initialization error: ' . $innerEx->getMessage()
                ]);
                exit;
            }
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    return $pdo;
}

/**
 * Standardized JSON API Response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Sanitize user input strings
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    return trim(htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Format number into Nigerian Naira string
 */
function formatNaira($amount) {
    return '₦' . number_format((float)$amount, 2);
}

/**
 * Check if a session has an authenticated user ID
 */
function getAuthUserId() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Require user login or return JSON error
 */
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

/**
 * Fetch active character for user
 */
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

/**
 * Log activity to character audit trail
 */
function logActivity($characterId, $actionType, $message, $cashChange = 0, $energyChange = 0, $happinessChange = 0) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (character_id, action_type, message, cash_change, energy_change, happiness_change)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$characterId, $actionType, $message, $cashChange, $energyChange, $happinessChange]);
    } catch (Exception $e) {
        // Silently continue if log fails to avoid blocking core gameplay
    }
}
