<?php
require_once 'config.php';
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Token-based session resolver
$authUser = getAuthenticatedUser('user');
$userid = $authUser ? (int)$authUser['user_id'] : 0;

// ── 1. ADD NEW REVIEW (USER) ──
if (($action === 'add_review' || $method === 'POST') && ($method === 'POST')) {
    if ($userid === 0) {
        sendJSON(['status' => 'error', 'message' => 'Please log in to submit a review.'], 401);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $bookingId  = (int)($input['booking_id'] ?? 0);
    $providerId = (int)($input['provider_id'] ?? 0);
    $rating     = (int)($input['rating'] ?? 5);
    $reviewText = trim($input['review_text'] ?? '');

    if ($bookingId <= 0 || $rating < 1 || $rating > 5) {
        sendJSON(['status' => 'error', 'message' => 'Invalid booking ID or rating (1-5).'], 400);
        exit;
    }

    // If providerId wasn't passed, look it up from booking
    if ($providerId === 0) {
        $b_stmt = $db->prepare("SELECT providerid FROM booking WHERE bookingid = ? AND userid = ?");
        $b_stmt->bind_param("ii", $bookingId, $userid);
        $b_stmt->execute();
        $b_res = $b_stmt->get_result();
        if ($b_row = $b_res->fetch_assoc()) {
            $providerId = (int)$b_row['providerid'];
        }
        $b_stmt->close();
    }

    if ($providerId <= 0) {
        sendJSON(['status' => 'error', 'message' => 'Service provider not found for this booking.'], 400);
        exit;
    }

    // Check if review already exists for this booking
    $check_stmt = $db->prepare("SELECT reviewid FROM reviews WHERE bookingid = ? AND userid = ?");
    $check_stmt->bind_param("ii", $bookingId, $userid);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    if ($check_res && $check_res->num_rows > 0) {
        $existing = $check_res->fetch_assoc();
        $check_stmt->close();
        // Update existing review
        $up = $db->prepare("UPDATE reviews SET rating = ?, reviews = ? WHERE reviewid = ?");
        $up->bind_param("isi", $rating, $reviewText, $existing['reviewid']);
        if ($up->execute()) {
            $up->close();
            sendJSON(['status' => 'success', 'message' => 'Your review has been updated!']);
        } else {
            $up->close();
            sendJSON(['status' => 'error', 'message' => 'Failed to update review.'], 500);
        }
        exit;
    }
    $check_stmt->close();

    // Insert new review
    $stmt = $db->prepare("INSERT INTO reviews (userid, providerid, bookingid, rating, reviews) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $userid, $providerId, $bookingId, $rating, $reviewText);
    if ($stmt->execute()) {
        $stmt->close();
        sendJSON(['status' => 'success', 'message' => 'Thank you! Your review has been submitted.']);
    } else {
        $error = $stmt->error;
        $stmt->close();
        sendJSON(['status' => 'error', 'message' => 'Failed to save review: ' . $error], 500);
    }
}

// ── 2. GET REVIEWS (BY PROVIDER / USER / ALL) ──
if ($method === 'GET') {
    $providerId = (int)($_GET['provider_id'] ?? 0);
    $bookingId  = (int)($_GET['booking_id'] ?? 0);

    if ($bookingId > 0) {
        $stmt = $db->prepare("SELECT r.*, u.name as customer_name FROM reviews r LEFT JOIN users u ON r.userid = u.userid WHERE r.bookingid = ?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $res = $stmt->get_result();
        $review = $res->fetch_assoc();
        $stmt->close();
        sendJSON(['status' => 'success', 'data' => $review]);
    } elseif ($providerId > 0) {
        $stmt = $db->prepare("SELECT r.*, u.name as customer_name FROM reviews r LEFT JOIN users u ON r.userid = u.userid WHERE r.providerid = ? ORDER BY r.reviewid DESC");
        $stmt->bind_param("i", $providerId);
        $stmt->execute();
        $res = $stmt->get_result();
        $reviewsList = [];
        while ($row = $res->fetch_assoc()) {
            $reviewsList[] = $row;
        }
        $stmt->close();
        sendJSON(['status' => 'success', 'data' => $reviewsList]);
    } else {
        $res = $db->query("SELECT r.*, u.name as customer_name, p.name as provider_name, p.category as category_name FROM reviews r LEFT JOIN users u ON r.userid=u.userid LEFT JOIN providers p ON r.providerid=p.providerid ORDER BY r.reviewid DESC");
        $reviewsList = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['review_text'] = $row['reviews'];
                $reviewsList[] = $row;
            }
        }
        sendJSON(['status' => 'success', 'data' => $reviewsList]);
    }
}

sendJSON(['status' => 'error', 'message' => 'Invalid request method'], 405);
