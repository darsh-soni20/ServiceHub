<?php
require_once 'config.php';

$db = getDB();


// Token-based session resolver
$authUser = getAuthenticatedUser('user');
$userid = $authUser ? (int)$authUser['user_id'] : 0;

if ($userid === 0) {
    sendJSON(['status' => 'error', 'message' => 'Unauthorized'], 401);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT name, email, phone, address, city, pincode FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        sendJSON(['status' => 'success', 'data' => $row]);
    } else {
        sendJSON(['status' => 'error', 'message' => 'User not found'], 404);
    }
    $stmt->close();
}
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        sendJSON(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        exit;
    }

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $city = trim($data['city'] ?? '');
    $pincode = trim($data['pincode'] ?? '');

    if (empty($name)) {
        sendJSON(['status' => 'error', 'message' => 'Name is required'], 400);
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, pincode = ? WHERE userid = ?");
    $stmt->bind_param("sssssi", $name, $phone, $address, $city, $pincode, $userid);
    
    if ($stmt->execute()) {
        // Update session namespace
        if (isset($_SESSION['sessions']['user'])) {
            $_SESSION['sessions']['user']['user_name'] = $name;
        }
        // Update all token entries for this user
        if (isset($_SESSION['tokens'])) {
            foreach ($_SESSION['tokens'] as $tk => &$tdata) {
                if ($tdata['role'] === 'user' && (int)$tdata['user_id'] === $userid) {
                    $tdata['user_name'] = $name;
                }
            }
            unset($tdata);
        }
        sendJSON(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        sendJSON(['status' => 'error', 'message' => 'Failed to update profile'], 500);
    }
    $stmt->close();
} else {
    sendJSON(['status' => 'error', 'message' => 'Method not allowed'], 405);
}
?>
