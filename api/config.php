<?php
define('DB_HOST', getenv('MYSQLHOST') ?: 'sql101.infinityfree.com');
define('DB_USER', getenv('MYSQLUSER') ?: 'if0_42615564');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'DarshanUni2026');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'if0_42615564_servicehub');
// Port is often needed by Railway, usually added to host if not default
$port = getenv('MYSQLPORT') ?: '3306';
if (getenv('MYSQLPORT')) {
    define('DB_PORT', $port); // some apps might use it if defined
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function getDB() {
    try {
        $port = defined('DB_PORT') ? DB_PORT : 3306;
        $dbConnection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);
    } catch (Throwable $e) {
        try {
            $dbConnection = new mysqli('127.0.0.1', DB_USER, DB_PASS, DB_NAME, 3306);
        } catch (Throwable $e2) {
            try {
                $dbConnection = new mysqli('localhost', DB_USER, DB_PASS, DB_NAME, 3306, '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
            } catch (Throwable $e3) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please ensure MySQL is running in XAMPP.']);
                exit();
            }
        }
    }
    $dbConnection->set_charset("utf8");
    return $dbConnection;
}

// Auto-create the auth_tokens table if it doesn't exist
function ensureAuthTokensTable($db) {
    $db->query("CREATE TABLE IF NOT EXISTS `auth_tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `token` VARCHAR(255) NOT NULL UNIQUE,
        `user_id` INT NOT NULL,
        `role` VARCHAR(20) NOT NULL,
        `user_name` VARCHAR(255) NOT NULL DEFAULT '',
        `user_email` VARCHAR(255) NOT NULL DEFAULT '',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_token` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function getAuthenticatedUser($requiredRole = null) {
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] 
          ?? $_GET['token'] 
          ?? ($_POST['token'] ?? null);
    
    if (empty($token)) return null;

    // 1. Try session first (fast, same-origin requests)
    if (isset($_SESSION['tokens'][$token])) {
        $user = $_SESSION['tokens'][$token];
        if ($requiredRole === null || $user['role'] === $requiredRole) return $user;
    }

    // 2. Lookup in database (works across iframes, different sessions)
    try {
        $db = getDB();
        ensureAuthTokensTable($db);
        $stmt = $db->prepare("SELECT user_id, role, user_name, user_email, token FROM auth_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user = [
                'user_id'    => $row['user_id'],
                'role'       => $row['role'],
                'user_name'  => $row['user_name'],
                'user_email' => $row['user_email'],
                'token'      => $row['token']
            ];
            $stmt->close();
            $db->close();
            if ($requiredRole === null || $user['role'] === $requiredRole) return $user;
        }
        $stmt->close();
        $db->close();
    } catch (Throwable $e) {
        // Silently fail DB lookup
    }

    return null;
}

function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>
