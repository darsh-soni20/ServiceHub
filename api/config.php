<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'servicemaster');

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
        $dbConnection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (Throwable $e) {
        try {
            $dbConnection = new mysqli('127.0.0.1', DB_USER, DB_PASS, DB_NAME);
        } catch (Throwable $e2) {
            try {
                $dbConnection = new mysqli('localhost', DB_USER, DB_PASS, DB_NAME, null, '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock');
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

function getAuthenticatedUser($requiredRole = null) {
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] 
          ?? $_GET['token'] 
          ?? $_POST['token'];
    
    // 1. Token-based lookup
    if (!empty($token) && isset($_SESSION['tokens'][$token])) {
        $user = $_SESSION['tokens'][$token];
        if ($requiredRole === null || $user['role'] === $requiredRole) {
            return $user;
        }
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
