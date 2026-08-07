<?php
require_once 'config.php';

header('Content-Type: application/json');
$db = getDB();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Determine Provider ID using token resolver
$provUser = getAuthenticatedUser('provider');
$providerid = $provUser ? (int)$provUser['user_id'] : 0;

if ($providerid === 0) {
    sendJSON(['status' => 'error', 'message' => 'Unauthorized: Please log in as a service provider.'], 401);
}

// ── 1. GET DASHBOARD DATA ──
if ($action === 'get_dashboard' && $method === 'GET') {
    // Provider Profile
    $stmt = $db->prepare("SELECT providerid, name, email, phone, category, experience, address, city, pincode, document, status FROM providers WHERE providerid = ?");
    $stmt->bind_param("i", $providerid);
    $stmt->execute();
    $provider = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$provider) {
        sendJSON(['status' => 'error', 'message' => 'Provider not found.'], 404);
    }

    // Helper to format booking row
    $formatRow = function($row) {
        $row['booking_ref'] = 'BK-' . $row['bookingid'];
        if (!empty($row['description']) && preg_match('/Package:\s*([^\n\r]+)/i', $row['description'], $m)) {
            $row['service_name'] = trim($m[1]);
        } elseif (empty($row['service_name'])) {
            $row['service_name'] = 'General Service';
        }
        return $row;
    };

    // Pending Job Requests
    $stmt = $db->prepare("SELECT b.*, s.name as service_name, u.name as customer_name, u.phone as customer_phone, u.address as customer_address FROM booking b LEFT JOIN services s ON b.serviceid = s.serviceid LEFT JOIN users u ON b.userid = u.userid WHERE b.providerid = ? AND b.status = 'pending' ORDER BY b.bookingid DESC");
    $stmt->bind_param("i", $providerid);
    $stmt->execute();
    $res = $stmt->get_result();
    $pending_requests = [];
    while ($row = $res->fetch_assoc()) {
        $pending_requests[] = $formatRow($row);
    }
    $stmt->close();

    // Accepted / Scheduled Jobs
    $stmt = $db->prepare("SELECT b.*, s.name as service_name, u.name as customer_name, u.phone as customer_phone, u.address as customer_address FROM booking b LEFT JOIN services s ON b.serviceid = s.serviceid LEFT JOIN users u ON b.userid = u.userid WHERE b.providerid = ? AND b.status IN ('confirmed', 'in-progress', 'completed') ORDER BY b.bookingid DESC");
    $stmt->bind_param("i", $providerid);
    $stmt->execute();
    $res = $stmt->get_result();
    $scheduled_jobs = [];
    while ($row = $res->fetch_assoc()) {
        $scheduled_jobs[] = $formatRow($row);
    }
    $stmt->close();

    // Declined / Cancelled Jobs
    $stmt = $db->prepare("SELECT b.*, s.name as service_name, u.name as customer_name, u.phone as customer_phone, u.address as customer_address FROM booking b LEFT JOIN services s ON b.serviceid = s.serviceid LEFT JOIN users u ON b.userid = u.userid WHERE b.providerid = ? AND b.status IN ('declined', 'cancelled') ORDER BY b.bookingid DESC");
    $stmt->bind_param("i", $providerid);
    $stmt->execute();
    $res = $stmt->get_result();
    $declined_jobs = [];
    while ($row = $res->fetch_assoc()) {
        $declined_jobs[] = $formatRow($row);
    }
    $stmt->close();

    // Stats Calculation
    $todayDate = date('Y-m-d');
    $today_count = 0;
    $total_earnings = 0;
    $completed_jobs = 0;
    $confirmed_jobs = 0;

    foreach ($scheduled_jobs as $job) {
        if ($job['status'] === 'confirmed') {
            $confirmed_jobs++;
        }
        if ($job['status'] === 'completed' || $job['status'] === 'confirmed' || $job['status'] === 'in-progress') {
            $total_earnings += floatval($job['amount']);
        }
        if ($job['status'] === 'completed') {
            $completed_jobs++;
        }
        if ($job['date'] === $todayDate) {
            $today_count++;
        }
    }

    sendJSON([
        'status' => 'success',
        'provider' => $provider,
        'requests' => $pending_requests,
        'schedule' => $scheduled_jobs,
        'declined' => $declined_jobs,
        'stats' => [
            'new_requests_count' => count($pending_requests),
            'today_jobs_count' => $today_count,
            'completed_jobs' => $completed_jobs,
            'confirmed_jobs' => $confirmed_jobs,
            'declined_count' => count($declined_jobs),
            'total_earnings' => $total_earnings
        ]
    ]);
}

// ── 2. TOGGLE ONLINE / ACTIVE STATUS ──
if ($action === 'toggle_status' && ($method === 'POST' || $method === 'PUT')) {
    $input = json_decode(file_get_contents('php://input'), true);
    $newStatus = trim($input['status'] ?? '');

    if (!in_array($newStatus, ['Active', 'Inactive'])) {
        sendJSON(['status' => 'error', 'message' => 'Status must be Active or Inactive.'], 400);
    }

    $stmt = $db->prepare("UPDATE providers SET status = ? WHERE providerid = ?");
    $stmt->bind_param("si", $newStatus, $providerid);
    if ($stmt->execute()) {
        $stmt->close();
        sendJSON(['status' => 'success', 'message' => 'Status updated', 'new_status' => $newStatus]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        sendJSON(['status' => 'error', 'message' => 'Failed to update status: ' . $error], 500);
    }
}

// ── 3. UPDATE BOOKING STATUS (ACCEPT / DECLINE / COMPLETE) ──
if ($action === 'update_booking' && ($method === 'POST' || $method === 'PUT')) {
    $input = json_decode(file_get_contents('php://input'), true);
    $bookingIdRaw = (int)($input['booking_id'] ?? 0);
    $newStatus = trim($input['status'] ?? '');

    $allowed = ['confirmed', 'declined', 'cancelled', 'in-progress', 'completed'];
    if ($bookingIdRaw <= 0 || !in_array($newStatus, $allowed)) {
        sendJSON(['status' => 'error', 'message' => 'Invalid booking ID or status: ' . $newStatus], 400);
    }

    // If accepting booking (confirmed), ensure a 4-digit OTP is generated if missing
    if ($newStatus === 'confirmed') {
        $check_otp = $db->query("SELECT otp FROM booking WHERE bookingid = $bookingIdRaw");
        if ($check_otp && $o_row = $check_otp->fetch_assoc()) {
            if (empty($o_row['otp'])) {
                $new_otp = sprintf("%04d", rand(1000, 9999));
                $db->query("UPDATE booking SET otp = '$new_otp' WHERE bookingid = $bookingIdRaw");
            }
        }
    }

    $stmt = $db->prepare("UPDATE booking SET status = ? WHERE bookingid = ? AND providerid = ?");
    $stmt->bind_param("sii", $newStatus, $bookingIdRaw, $providerid);
    if ($stmt->execute()) {
        $stmt->close();
        sendJSON(['status' => 'success', 'message' => 'Booking status updated to ' . $newStatus]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        sendJSON(['status' => 'error', 'message' => 'Failed to update booking: ' . $error], 500);
    }
}

// ── 3B. VERIFY OTP TO START SERVICE ──
if ($action === 'verify_otp' && ($method === 'POST' || $method === 'PUT')) {
    $input = json_decode(file_get_contents('php://input'), true);
    $bookingId = (int)($input['booking_id'] ?? 0);
    $userOtp = trim($input['otp'] ?? '');

    if ($bookingId <= 0 || empty($userOtp)) {
        sendJSON(['status' => 'error', 'message' => 'Booking ID and OTP are required.'], 400);
    }

    $stmt = $db->prepare("SELECT otp, status FROM booking WHERE bookingid = ? AND providerid = ?");
    $stmt->bind_param("ii", $bookingId, $providerid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (trim($row['otp']) === $userOtp) {
            $up = $db->prepare("UPDATE booking SET status = 'in-progress' WHERE bookingid = ?");
            $up->bind_param("i", $bookingId);
            if ($up->execute()) {
                $up->close();
                sendJSON(['status' => 'success', 'message' => 'OTP verified successfully! Service is now in progress.']);
            } else {
                $up->close();
                sendJSON(['status' => 'error', 'message' => 'Failed to start service.'], 500);
            }
        } else {
            sendJSON(['status' => 'error', 'message' => 'Invalid OTP! Please ask customer for the correct 4-digit code.'], 400);
        }
    } else {
        sendJSON(['status' => 'error', 'message' => 'Booking not found or unauthorized.'], 404);
    }
    $stmt->close();
}

// ── 4. UPDATE PROVIDER PROFILE ──
if ($action === 'update_profile' && ($method === 'POST' || $method === 'PUT')) {
    $input = json_decode(file_get_contents('php://input'), true);

    $name       = trim($input['name'] ?? '');
    $email      = trim($input['email'] ?? '');
    $phone      = trim($input['phone'] ?? '');
    $category   = trim($input['category'] ?? '');
    $experience = (int)($input['experience'] ?? 0);
    $address    = trim($input['address'] ?? '');
    $city       = trim($input['city'] ?? '');
    $pincode    = trim($input['pincode'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($category)) {
        sendJSON(['status' => 'error', 'message' => 'Name, email, phone and service category are required.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJSON(['status' => 'error', 'message' => 'Please enter a valid email address.'], 400);
    }

    // Check if email is already taken by another provider
    $check_stmt = $db->prepare("SELECT providerid FROM providers WHERE email = ? AND providerid != ?");
    $check_stmt->bind_param("si", $email, $providerid);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    if ($check_res && $check_res->num_rows > 0) {
        $check_stmt->close();
        sendJSON(['status' => 'error', 'message' => 'This email address is already in use by another provider account.'], 400);
    }
    $check_stmt->close();

    $stmt = $db->prepare("UPDATE providers SET name = ?, email = ?, phone = ?, category = ?, experience = ?, address = ?, city = ?, pincode = ? WHERE providerid = ?");
    $stmt->bind_param("ssssisssi", $name, $email, $phone, $category, $experience, $address, $city, $pincode, $providerid);

    if ($stmt->execute()) {
        $stmt->close();
        // Update session namespace
        if (isset($_SESSION['sessions']['provider'])) {
            $_SESSION['sessions']['provider']['user_name'] = $name;
            $_SESSION['sessions']['provider']['user_email'] = $email;
        }
        // Update all token entries for this provider
        if (isset($_SESSION['tokens'])) {
            foreach ($_SESSION['tokens'] as $tk => &$tdata) {
                if ($tdata['role'] === 'provider' && (int)$tdata['user_id'] === $providerid) {
                    $tdata['user_name'] = $name;
                    $tdata['user_email'] = $email;
                }
            }
            unset($tdata);
        }
        sendJSON(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        $error = $stmt->error;
        $stmt->close();
        sendJSON(['status' => 'error', 'message' => 'Failed to update profile: ' . $error], 500);
    }
}

$db->close();
sendJSON(['status' => 'error', 'message' => 'Invalid action or request method.'], 400);
?>
