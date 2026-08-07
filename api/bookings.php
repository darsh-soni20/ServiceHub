<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// ── POST: Create a new booking ──
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendJSON(['status' => 'error', 'message' => 'Invalid request data'], 400);
    }

    $service_name = trim($input['service_name'] ?? '');
    $date         = trim($input['date'] ?? '');
    $time         = trim($input['time'] ?? '');
    $description  = trim($input['description'] ?? '');
    $address      = trim($input['address'] ?? '');
    $customer_name = trim($input['customer_name'] ?? '');
    $phone        = trim($input['phone'] ?? '');
    $email        = trim($input['email'] ?? '');
    $paymentmode  = trim($input['paymentmode'] ?? 'cash');
    $amount       = floatval($input['amount'] ?? 0);

    // Validate required fields
    if (empty($service_name) || empty($date) || empty($customer_name) || empty($phone)) {
        sendJSON(['status' => 'error', 'message' => 'Service, date, name and phone are required'], 400);
    }

    // Resolve service name to serviceid
    $serviceid = 0;
    $stmt = $db->prepare("SELECT serviceid FROM services WHERE name LIKE ?");
    $search = '%' . $service_name . '%';
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($row = $r->fetch_assoc()) {
        $serviceid = (int)$row['serviceid'];
    }
    $stmt->close();

    // Resolve user by email (if logged in or email provided)
    $userid = 0;
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            $userid = (int)$row['userid'];
        }
        $stmt->close();
    }
    // Fallback: check session (multi-role support)
    if ($userid === 0) {
        $u = getAuthenticatedUser('user');
        if ($u) $userid = (int)$u['user_id'];
    }

    // Auto-assign a provider from the matching service category
    $providerid = 0;
    if ($serviceid > 0) {
        // Get the service name to match provider category
        $svcResult = $db->query("SELECT name FROM services WHERE serviceid = $serviceid");
        if ($svcRow = $svcResult->fetch_assoc()) {
            $svcName = $svcRow['name'];
            $stmt = $db->prepare("SELECT providerid FROM providers WHERE category LIKE ? AND status = 'Active' ORDER BY RAND() LIMIT 1");
            $catSearch = '%' . $svcName . '%';
            $stmt->bind_param("s", $catSearch);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($row = $r->fetch_assoc()) {
                $providerid = (int)$row['providerid'];
            }
            $stmt->close();
        }
    }
    // If no matching provider, pick any active provider
    if ($providerid === 0) {
        $r = $db->query("SELECT providerid FROM providers WHERE status = 'Active' ORDER BY RAND() LIMIT 1");
        if ($row = $r->fetch_assoc()) {
            $providerid = (int)$row['providerid'];
        }
    }

    // Build full description
    $fullDescription = $description;
    if (!empty($address)) {
        $fullDescription .= ($fullDescription ? "\n" : '') . "Address: " . $address;
    }

    $status = 'pending';
    $generated_otp = sprintf("%04d", rand(1000, 9999));

    $stmt = $db->prepare("INSERT INTO booking (userid, providerid, serviceid, date, time, status, otp, description, paymentmode, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissssssd", $userid, $providerid, $serviceid, $date, $time, $status, $generated_otp, $fullDescription, $paymentmode, $amount);

    if ($stmt->execute()) {
        $bookingId = $db->insert_id;
        $stmt->close();
        sendJSON([
            'status' => 'success',
            'message' => 'Booking created successfully',
            'booking_id' => 'BK-' . $bookingId,
            'booking_id_raw' => $bookingId,
            'otp' => $generated_otp
        ], 201);
    } else {
        $error = $stmt->error;
        $stmt->close();
        sendJSON(['status' => 'error', 'message' => 'Booking failed: ' . $error], 500);
    }
}

// ── GET: Fetch bookings for logged-in user ──
if ($method === 'GET') {
    // Multi-role session support for GET
    $u = getAuthenticatedUser();
    $userId = $u ? (int)$u['user_id'] : 0;
    $role = $u ? $u['role'] : '';

    if ($userId === 0) {
        sendJSON(['status' => 'error', 'message' => 'Not logged in'], 401);
    }

    if ($role === 'user') {
        $stmt = $db->prepare("SELECT b.*, s.name as service_name, p.name as provider_name FROM booking b LEFT JOIN services s ON b.serviceid=s.serviceid LEFT JOIN providers p ON b.providerid=p.providerid WHERE b.userid=? ORDER BY b.bookingid DESC");
        $stmt->bind_param("i", $userId);
    } elseif ($role === 'provider') {
        $stmt = $db->prepare("SELECT b.*, s.name as service_name, u.name as customer_name FROM booking b LEFT JOIN services s ON b.serviceid=s.serviceid LEFT JOIN users u ON b.userid=u.userid WHERE b.providerid=? ORDER BY b.bookingid DESC");
        $stmt->bind_param("i", $userId);
    } else {
        sendJSON(['status' => 'error', 'message' => 'Invalid role'], 403);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $row['booking_ref'] = 'BK-' . $row['bookingid'];
        if (!empty($row['description']) && preg_match('/Package:\s*([^\n\r]+)/i', $row['description'], $m)) {
            $row['service_name'] = trim($m[1]);
        } elseif (empty($row['service_name'])) {
            $row['service_name'] = 'General Service';
        }
        $bookings[] = $row;
    }
    $stmt->close();
    sendJSON(['status' => 'success', 'data' => $bookings]);
}

$db->close();
sendJSON(['status' => 'error', 'message' => 'Invalid request method'], 405);
?>
