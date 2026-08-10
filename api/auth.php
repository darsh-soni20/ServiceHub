<?php
require_once 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Helper function to set role session and generate unique token
function setRoleSession($role, $userId, $userName, $userEmail) {
    if (!isset($_SESSION['sessions'])) {
        $_SESSION['sessions'] = [];
    }
    if (!isset($_SESSION['tokens'])) {
        $_SESSION['tokens'] = [];
    }

    $token = $role . '_' . bin2hex(random_bytes(16));

    $sessionData = [
        'user_id'    => $userId,
        'role'       => $role,
        'user_name'  => $userName,
        'user_email' => $userEmail,
        'token'      => $token
    ];

    $_SESSION['sessions'][$role] = $sessionData;
    $_SESSION['tokens'][$token]  = $sessionData;

    // Save token to database for cross-session/iframe retrieval
    try {
        $db = getDB();
        ensureAuthTokensTable($db);
        $stmt = $db->prepare("INSERT INTO auth_tokens (token, user_id, role, user_name, user_email) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $token, $userId, $role, $userName, $userEmail);
        $stmt->execute();
        $stmt->close();
        $db->close();
    } catch (Throwable $e) {
        // Silently fail - session still works for same-origin
    }

    return $token;
}

// Handle logout first (no DB needed)
if ($action === 'logout') {
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? $_GET['token'] ?? null;
    if ($token) {
        if (isset($_SESSION['tokens'][$token])) unset($_SESSION['tokens'][$token]);
        
        // Remove from database too
        try {
            $db = getDB();
            ensureAuthTokensTable($db);
            $stmt = $db->prepare("DELETE FROM auth_tokens WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();
            $db->close();
        } catch (Throwable $e) {}
    }
    if ($role) {
        if (isset($_SESSION['sessions'][$role])) {
            unset($_SESSION['sessions'][$role]);
        }
    }
    sendJSON(['status' => 'success', 'message' => 'Logged out successfully']);
}

// Handle session check (no DB needed)
if ($action === 'check') {
    $requested_role = $_GET['role'] ?? null;
    $user = getAuthenticatedUser($requested_role);
    if ($user) {
        sendJSON([
            'status' => 'success',
            'logged_in' => true,
            'role' => $user['role'],
            'user_id' => $user['user_id'],
            'user_name' => $user['user_name'],
            'user_email' => $user['user_email'],
            'token' => $user['token'] ?? ''
        ]);
    } else {
        sendJSON(['status' => 'success', 'logged_in' => false]);
    }
}

// All remaining actions need the database
$db = getDB();

// ── LOGIN ──
if ($action === 'login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendJSON(['status' => 'error', 'message' => 'Invalid request data'], 400);
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role = trim($input['role'] ?? 'user');

    if (empty($email) || empty($password)) {
        sendJSON(['status' => 'error', 'message' => 'Email and password are required'], 400);
    }

    if ($role === 'user') {
        // Customer login guard: ONLY check users table
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($user) {
            if ($password === $user['password']) {
                $token = setRoleSession('user', $user['userid'], $user['name'], $user['email']);
                sendJSON(['status' => 'success', 'role' => 'user', 'token' => $token, 'redirect' => 'user_panel/ServiceHub/index.html']);
            } else {
                sendJSON(['status' => 'error', 'message' => 'Invalid email or password.'], 401);
            }
        } else {
            // Check if user exists in provider or admin table for specific error message
            $p_check = $db->prepare("SELECT providerid FROM providers WHERE email = ?");
            $p_check->bind_param("s", $email);
            $p_check->execute();
            if ($p_check->get_result()->num_rows > 0) {
                $p_check->close();
                sendJSON(['status' => 'error', 'message' => 'This account is registered as a Service Provider. Please select the Service Provider login tab.'], 403);
            }
            $p_check->close();

            $a_check = $db->prepare("SELECT adminid FROM admin WHERE username = ?");
            $a_check->bind_param("s", $email);
            $a_check->execute();
            if ($a_check->get_result()->num_rows > 0) {
                $a_check->close();
                sendJSON(['status' => 'error', 'message' => 'This account is registered as an Admin. Please select the Admin login tab.'], 403);
            }
            $a_check->close();

            sendJSON(['status' => 'error', 'message' => 'Invalid email or password.'], 401);
        }
    } elseif ($role === 'provider') {
        // Provider login guard: ONLY check providers table
        $stmt = $db->prepare("SELECT * FROM providers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($user) {
            if ($password === $user['password']) {
                if ($user['status'] === 'Inactive') {
                    sendJSON(['status' => 'error', 'message' => 'Your account is pending admin approval.'], 403);
                }
                $token = setRoleSession('provider', $user['providerid'], $user['name'], $user['email']);
                sendJSON(['status' => 'success', 'role' => 'provider', 'token' => $token, 'redirect' => 'provider_panel/provider-dashboard.html']);
            } else {
                sendJSON(['status' => 'error', 'message' => 'Invalid email or password.'], 401);
            }
        } else {
            // Check if email belongs to customer or admin
            $u_check = $db->prepare("SELECT userid FROM users WHERE email = ?");
            $u_check->bind_param("s", $email);
            $u_check->execute();
            if ($u_check->get_result()->num_rows > 0) {
                $u_check->close();
                sendJSON(['status' => 'error', 'message' => 'This account is registered as a Customer. Please select the Customer login tab.'], 403);
            }
            $u_check->close();

            $a_check = $db->prepare("SELECT adminid FROM admin WHERE username = ?");
            $a_check->bind_param("s", $email);
            $a_check->execute();
            if ($a_check->get_result()->num_rows > 0) {
                $a_check->close();
                sendJSON(['status' => 'error', 'message' => 'This account is registered as an Admin. Please select the Admin login tab.'], 403);
            }
            $a_check->close();

            sendJSON(['status' => 'error', 'message' => 'Invalid email or password.'], 401);
        }
    } elseif ($role === 'admin') {
        // Admin login guard: ONLY check admin table
        $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($user && $password === $user['password']) {
            $token = setRoleSession('admin', $user['adminid'], 'Administrator', $user['username']);
            sendJSON(['status' => 'success', 'role' => 'admin', 'token' => $token, 'redirect' => 'admin_panel/src/index.html']);
        } else {
            sendJSON(['status' => 'error', 'message' => 'Invalid admin credentials.'], 401);
        }
    } else {
        sendJSON(['status' => 'error', 'message' => 'Invalid role specified'], 400);
    }
}

// ── REGISTER ──
if ($action === 'register' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendJSON(['status' => 'error', 'message' => 'Invalid request data'], 400);
    }

    $email = trim($input['email'] ?? '');
    $role = $input['role'] ?? '';
    $password = $input['password'] ?? '';
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $city = trim($input['city'] ?? '');
    $pincode = trim($input['pincode'] ?? '');

    // Validate required fields
    if (empty($email) || empty($password) || empty($name)) {
        sendJSON(['status' => 'error', 'message' => 'Name, email and password are required'], 400);
    }

    // Check duplicate email
    if ($role === 'user') {
        $check = $db->prepare("SELECT email FROM users WHERE email = ?");
    } elseif ($role === 'provider') {
        $check = $db->prepare("SELECT email FROM providers WHERE email = ?");
    } else {
        sendJSON(['status' => 'error', 'message' => 'Invalid role for registration'], 400);
    }
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        sendJSON(['status' => 'error', 'message' => 'Email already registered. Please use a different email.'], 409);
    }
    $check->close();

    if ($role === 'user') {
        $max_id_query = $db->query("SELECT MAX(userid) AS maxid FROM users");
        $max_id = $max_id_query->fetch_assoc()['maxid'];
        $new_id = $max_id ? $max_id + 1 : 1;
        
        $stmt = $db->prepare("INSERT INTO users (userid, name, email, phone, address, city, pincode, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $new_id, $name, $email, $phone, $address, $city, $pincode, $password);
        if ($stmt->execute()) {
            $stmt->close();
            sendJSON(['status' => 'success', 'message' => 'Account created successfully! Please login.'], 201);
        } else {
            $error = $stmt->error;
            $stmt->close();
            sendJSON(['status' => 'error', 'message' => 'Registration failed: ' . $error], 500);
        }
    } elseif ($role === 'provider') {
        $category = trim($input['category'] ?? '');
        $experience = (int)($input['experience'] ?? 0);
        $document = 'pending_doc.pdf';
        $status = 'Inactive';
        
        if (empty($category)) {
            sendJSON(['status' => 'error', 'message' => 'Please select a service category'], 400);
        }

        $stmt = $db->prepare("INSERT INTO providers (name, email, phone, category, experience, address, city, pincode, document, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssissssss", $name, $email, $phone, $category, $experience, $address, $city, $pincode, $document, $status, $password);
        if ($stmt->execute()) {
            $stmt->close();
            sendJSON(['status' => 'success', 'message' => 'Partner application submitted! Pending admin approval.'], 201);
        } else {
            $error = $stmt->error;
            $stmt->close();
            sendJSON(['status' => 'error', 'message' => 'Registration failed: ' . $error], 500);
        }
    }
}

$db->close();
sendJSON(['status' => 'error', 'message' => 'Invalid action'], 400);
?>
