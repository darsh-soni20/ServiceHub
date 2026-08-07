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
    $bookings = [];
    $stmt = $db->prepare("
        SELECT b.bookingid, b.date, b.time, b.status, b.amount, b.paymentmode, b.description, b.otp,
               s.name as service_name, 
               p.providerid, p.name as provider_name, p.phone as provider_phone, 
               p.email as provider_email, p.experience as provider_experience,
               COALESCE(ROUND(AVG(r.rating), 1), 4.8) as provider_rating,
               rev.rating as user_rating, rev.reviews as user_review
        FROM booking b 
        LEFT JOIN services s ON b.serviceid = s.serviceid 
        LEFT JOIN providers p ON b.providerid = p.providerid 
        LEFT JOIN reviews r ON p.providerid = r.providerid
        LEFT JOIN reviews rev ON (b.bookingid = rev.bookingid AND rev.userid = b.userid)
        WHERE b.userid = ? 
        GROUP BY b.bookingid
        ORDER BY b.bookingid DESC
    ");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $row['booking_ref'] = 'BK-' . $row['bookingid'];
        if (!empty($row['description']) && preg_match('/Package:\s*([^\n\r]+)/i', $row['description'], $m)) {
            $row['service_name'] = trim($m[1]);
        } elseif (empty($row['service_name'])) {
            $row['service_name'] = 'General Service';
        }
        $bookings[] = $row;
    }
    
    sendJSON(['status' => 'success', 'data' => $bookings]);
    $stmt->close();

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['bookingid']) || !isset($data['action']) || $data['action'] !== 'cancel') {
        sendJSON(['status' => 'error', 'message' => 'Invalid request'], 400);
        exit;
    }

    $bookingid = (int)$data['bookingid'];

    // Ensure it belongs to user and is pending
    $stmt = $db->prepare("SELECT status FROM booking WHERE bookingid = ? AND userid = ?");
    $stmt->bind_param("ii", $bookingid, $userid);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if ($row['status'] === 'pending') {
            $update = $db->prepare("UPDATE booking SET status = 'canceled' WHERE bookingid = ?");
            $update->bind_param("i", $bookingid);
            if ($update->execute()) {
                sendJSON(['status' => 'success', 'message' => 'Booking canceled successfully']);
            } else {
                sendJSON(['status' => 'error', 'message' => 'Failed to cancel booking'], 500);
            }
            $update->close();
        } else {
            sendJSON(['status' => 'error', 'message' => 'Only pending bookings can be canceled'], 400);
        }
    } else {
        sendJSON(['status' => 'error', 'message' => 'Booking not found or unauthorized'], 404);
    }
    $stmt->close();
} else {
    sendJSON(['status' => 'error', 'message' => 'Method not allowed'], 405);
}
?>