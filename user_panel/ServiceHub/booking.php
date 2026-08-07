<?php
require_once __DIR__ . '/../../api/config.php';
$db = getDB();

$booking_success = false;
$success_data = [];
$error_msg = '';

$service_name_query = isset($_GET['service']) ? htmlspecialchars($_GET['service']) : 'General Service Consultation';
$service_price_query = isset($_GET['price']) ? htmlspecialchars($_GET['price']) : '₹49 onwards';

// Pre-fill user profile details automatically if logged in
$user_profile = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];

$user_sess_obj = getAuthenticatedUser('user');
$userid_sess = $user_sess_obj ? (int)$user_sess_obj['user_id'] : 0;

if ($userid_sess === 0) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Login Required</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body style="background:#12161f; color:#fff; font-family:sans-serif; text-align:center; padding:60px 20px;">';
    echo '<div style="max-width:400px; margin:0 auto; background:rgba(255,255,255,0.05); padding:35px; border-radius:16px; border:1px solid rgba(209,159,104,0.3);box-shadow:0 10px 30px rgba(0,0,0,0.5);">';
    echo '<i class="fas fa-user-lock" style="font-size:48px; color:#d19f68; margin-bottom:15px;"></i>';
    echo '<h2 style="font-size:22px; margin-bottom:10px; color:#fff;">Login Required</h2>';
    echo '<p style="color:#aaa; font-size:14px; margin-bottom:25px; line-height:1.5;">You must be logged in to book a service. Redirecting to login page...</p>';
    echo '<button onclick="window.top.location.href=\'/Project/login.php\'" style="background:linear-gradient(135deg, #d19f68, #b88652); color:#000; font-weight:bold; padding:12px 28px; border:none; border-radius:25px; cursor:pointer; font-size:14px; box-shadow:0 4px 15px rgba(209,159,104,0.3);"><i class="fas fa-sign-in-alt"></i> Go to Login Page</button>';
    echo '</div>';
    echo '<script>setTimeout(function(){ window.top.location.href=\'/Project/login.php\'; }, 1500);</script>';
    echo '</body></html>';
    exit();
}

if ($userid_sess > 0) {
    $u_stmt = $db->prepare("SELECT name, email, phone, address, city, pincode FROM users WHERE userid = ?");
    $u_stmt->bind_param("i", $userid_sess);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if ($u_row = $u_res->fetch_assoc()) {
        $user_profile['name'] = $u_row['name'];
        $user_profile['email'] = $u_row['email'];
        $user_profile['phone'] = $u_row['phone'];
        
        $addr = $u_row['address'];
        if (!empty($u_row['city']) && strpos($addr, $u_row['city']) === false) {
            $addr .= ($addr ? ', ' : '') . $u_row['city'];
        }
        if (!empty($u_row['pincode']) && strpos($addr, $u_row['pincode']) === false) {
            $addr .= ($addr ? ' - ' : '') . $u_row['pincode'];
        }
        $user_profile['address'] = $addr;
    }
    $u_stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name'] ?? '');
    $service_price = trim($_POST['service_price'] ?? '');
    $customer_name = trim($_POST['fullName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date = trim($_POST['bookingDate'] ?? '');
    $slotVal = trim($_POST['timeSlot'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    $timeValue = '10:00:00';
    if (strpos($slotVal, 'Morning') !== false) $timeValue = '10:00:00';
    elseif (strpos($slotVal, 'Afternoon') !== false) $timeValue = '13:00:00';
    elseif (strpos($slotVal, 'Evening') !== false) $timeValue = '16:00:00';

    $amount = 0;
    if (preg_match('/[\d,]+/', $service_price, $matches)) {
        $amount = (float)str_replace(',', '', $matches[0]);
    }
    $serviceid = 0;
    // 1. Try matching sub_services table
    $stmt = $db->prepare("SELECT serviceid FROM sub_services WHERE name LIKE ? LIMIT 1");
    $search = '%' . $service_name . '%';
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($row = $r->fetch_assoc()) {
        $serviceid = (int)$row['serviceid'];
    }
    $stmt->close();

    // 2. Try matching services table
    if ($serviceid === 0) {
        $stmt = $db->prepare("SELECT serviceid FROM services WHERE name LIKE ? OR ? LIKE CONCAT('%', name, '%') LIMIT 1");
        $stmt->bind_param("ss", $search, $service_name);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            $serviceid = (int)$row['serviceid'];
        }
        $stmt->close();
    }

    // 3. Fallback: match first word
    if ($serviceid === 0) {
        $first_word = explode(' ', trim($service_name))[0];
        if (!empty($first_word)) {
            $stmt = $db->prepare("SELECT serviceid FROM services WHERE name LIKE ? LIMIT 1");
            $wordSearch = '%' . $first_word . '%';
            $stmt->bind_param("s", $wordSearch);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($row = $r->fetch_assoc()) {
                $serviceid = (int)$row['serviceid'];
            }
            $stmt->close();
        }
    }
    
    // Fallback if still 0
    if ($serviceid === 0) {
        $r = $db->query("SELECT serviceid FROM services LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            $serviceid = (int)$row['serviceid'];
        }
    }

    // Build description with Package tag
    $description = ($instructions ? $instructions : '') . ($slotVal ? "\nSlot: " . $slotVal : '');
    $fullDescription = "Package: " . $service_name;
    if (!empty($description)) {
        $fullDescription .= "\n" . $description;
    }
    if (!empty($address)) {
        $fullDescription .= "\nAddress: " . $address;
    }


    $userid = 0;
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT userid FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) $userid = (int)$row['userid'];
        $stmt->close();
    }
    if ($userid === 0) {
        $u_sess = getAuthenticatedUser('user');
        if ($u_sess) $userid = (int)$u_sess['user_id'];
    }
    // If still 0, create a new user automatically
    if ($userid === 0 && !empty($email) && !empty($customer_name)) {
        $dummy_pass = password_hash('User@123', PASSWORD_BCRYPT); // default password
        
        // Get next userid (table doesn't have AUTO_INCREMENT)
        $next_id = 1;
        $r = $db->query("SELECT MAX(userid) as max_id FROM users");
        if ($r && $row = $r->fetch_assoc()) {
            $next_id = (int)$row['max_id'] + 1;
        }

        $stmt = $db->prepare("INSERT INTO users (userid, name, email, phone, address, city, pincode, password) VALUES (?, ?, ?, ?, ?, 'Pending', '000000', ?)");
        $stmt->bind_param("isssss", $next_id, $customer_name, $email, $phone, $address, $dummy_pass);
        if ($stmt->execute()) {
            $userid = $next_id;
        }
        $stmt->close();
    }

    $providerid = 0;
    if ($serviceid > 0) {
        $svcResult = $db->query("SELECT name FROM services WHERE serviceid = $serviceid");
        if ($svcRow = $svcResult->fetch_assoc()) {
            $svcName = $svcRow['name'];
            $stmt = $db->prepare("SELECT providerid FROM providers WHERE category LIKE ? AND status = 'Active' ORDER BY RAND() LIMIT 1");
            $catSearch = '%' . $svcName . '%';
            $stmt->bind_param("s", $catSearch);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($row = $r->fetch_assoc()) $providerid = (int)$row['providerid'];
            $stmt->close();
        }
    }
    if ($providerid === 0) {
        $r = $db->query("SELECT providerid FROM providers WHERE status = 'Active' ORDER BY RAND() LIMIT 1");
        if ($row = $r->fetch_assoc()) $providerid = (int)$row['providerid'];
    }

    $paymentmode = trim($_POST['paymentmode'] ?? 'cash');
    if ($paymentmode !== 'online') {
        $paymentmode = 'cash';
    }

    $generated_otp = sprintf("%04d", rand(1000, 9999));
    $stmt = $db->prepare("INSERT INTO booking (userid, providerid, serviceid, date, time, status, otp, description, paymentmode, amount) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
    $stmt->bind_param("iiisssssd", $userid, $providerid, $serviceid, $date, $timeValue, $generated_otp, $fullDescription, $paymentmode, $amount);
    
    if ($stmt->execute()) {
        $booking_id_num = $db->insert_id;
        $booking_success = true;

        $prov_name = 'Assigned Technician';
        $prov_phone = 'N/A';
        $prov_rating = '4.8';
        $prov_exp = '';

        if ($providerid > 0) {
            $p_stmt = $db->prepare("SELECT p.name, p.phone, p.experience, COALESCE(ROUND(AVG(r.rating), 1), 4.8) as rating FROM providers p LEFT JOIN reviews r ON p.providerid = r.providerid WHERE p.providerid = ? GROUP BY p.providerid");
            $p_stmt->bind_param("i", $providerid);
            $p_stmt->execute();
            $p_res = $p_stmt->get_result();
            if ($p_row = $p_res->fetch_assoc()) {
                $prov_name = $p_row['name'];
                $prov_phone = $p_row['phone'];
                $prov_rating = $p_row['rating'];
                $prov_exp = $p_row['experience'] . ' yrs exp';
            }
            $p_stmt->close();
        }

        $success_data = [
            'booking_id' => 'BK-' . $booking_id_num,
            'booking_id_raw' => $booking_id_num,
            'service' => htmlspecialchars($service_name),
            'date_time' => date('F j, Y', strtotime($date)) . " (" . htmlspecialchars($slotVal) . ")",
            'customer' => htmlspecialchars($customer_name),
            'phone' => htmlspecialchars($phone),
            'otp' => $generated_otp,
            'amount' => $amount,
            'formatted_amount' => '₹' . number_format($amount, 2),
            'paymentmode' => strtoupper($paymentmode),
            'provider_name' => htmlspecialchars($prov_name),
            'provider_phone' => htmlspecialchars($prov_phone),
            'provider_rating' => htmlspecialchars($prov_rating),
            'provider_exp' => htmlspecialchars($prov_exp)
        ];
    } else {
        $error_msg = "Booking failed: " . $stmt->error;
    }

}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ServiceHub - Book a Service</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="assets/css/fontawesome-all.min.css">
    
    <style>
        :root {
            --primary: #d19f68;
            --primary-hover: #b88651;
            --bg-dark: #0b0e14;
            --card-bg: #121620;
            --input-bg: #181e2b;
            --input-border: #252f44;
            --text-main: #ffffff;
            --text-muted: #a5b1c2;
            --success: #2ecc71;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top right, #1a2233, #0b0e14);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Booking Container */
        .booking-container {
            width: 100%;
            max-width: 550px;
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(209, 159, 104, 0.15);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(209, 159, 104, 0.05);
            overflow: hidden;
            position: relative;
            transition: all 0.4s ease;
        }

        /* Top Header Brand */
        .brand-header {
            background: linear-gradient(135deg, #1d2538 0%, #121620 100%);
            padding: 24px 30px;
            border-bottom: 1px solid rgba(209, 159, 104, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-logo span {
            color: var(--primary);
        }

        .brand-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
            display: none; /* Only show in iframe mode if triggered by parent, standard close button is in modal overlay */
        }

        .brand-close:hover {
            color: var(--primary);
        }

        /* Form Wrapper */
        .form-wrapper {
            padding: 30px;
        }

        .section-title {
            font-family: 'Oswald', sans-serif;
            font-size: 22px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 20px;
            color: var(--text-main);
            letter-spacing: 0.5px;
        }

        /* Selected Service Badge */
        .service-info-badge {
            background: rgba(209, 159, 104, 0.08);
            border: 1px dashed rgba(209, 159, 104, 0.4);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .service-info-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .service-info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: 1px;
            font-weight: 600;
        }

        .service-info-name {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .service-info-price {
            background: var(--primary);
            color: #000;
            font-family: 'Oswald', sans-serif;
            font-size: 14px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 30px;
            text-transform: uppercase;
        }

        /* Input Groups */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        @media (min-width: 480px) {
            .form-grid-2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-group label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .input-group label span {
            color: var(--primary);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: rgba(209, 159, 104, 0.5);
            font-size: 14px;
            pointer-events: none;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 12px 14px 12px 42px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(209, 159, 104, 0.15);
            background: rgba(24, 30, 43, 0.8);
        }

        .form-control:focus + i {
            color: var(--primary);
        }

        textarea.form-control {
            padding-left: 14px;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'><path d='M1 1L6 6L11 1' stroke='%23d19f68' stroke-width='2' stroke-linecap='round'/></svg>");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, var(--primary) 0%, #af804d 100%);
            color: #000;
            border: none;
            border-radius: 8px;
            padding: 14px 20px;
            font-family: 'Oswald', sans-serif;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(209, 159, 104, 0.25);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(209, 159, 104, 0.4);
            background: linear-gradient(135deg, #e4b27b 0%, var(--primary) 100%);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Loading Spinner */
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-top: 2px solid #000;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Success State */
        .success-wrapper {
            display: none;
            padding: 50px 30px;
            text-align: center;
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon-container {
            margin-bottom: 25px;
        }

        .checkmark-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(46, 204, 113, 0.1);
            border: 2px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
            animation: pulse-success 2s infinite;
        }

        .checkmark-circle i {
            color: var(--success);
            font-size: 38px;
            animation: scaleCheck 0.4s 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
        }

        @keyframes pulse-success {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }

        @keyframes scaleCheck {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

        .success-title {
            font-family: 'Oswald', sans-serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--success);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .success-message {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: var(--text-muted);
        }

        .summary-value {
            font-weight: 500;
            color: #fff;
        }

        .summary-value.highlight {
            color: var(--primary);
            font-weight: 600;
        }

        .close-btn-secondary {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--text-main);
            border-radius: 8px;
            padding: 12px 30px;
            font-family: 'Oswald', sans-serif;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Direct URL helper alert */
        .parent-info-alert {
            background: rgba(33, 150, 243, 0.08);
            border: 1px solid rgba(33, 150, 243, 0.2);
            color: #90caf9;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

    <div class="booking-container">
        
        <!-- Header -->
        <div class="brand-header">
            <div class="brand-logo">
                <i class="fas fa-tools" style="color: var(--primary);"></i> Service<span>Hub</span>
            </div>
            <button class="brand-close" id="iframeCloseBtn" onclick="closeModalInParent()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Booking Form State -->
        <?php if (!$booking_success): ?>
<div class="form-wrapper" id="bookingFormSection">
            <div class="mb-3">
                <h2 class="section-title" style="margin:0;">Schedule Booking</h2>
            </div>
            
            <?php if (!empty($user_profile['name'])): ?>
            <div style="display:flex; background:rgba(46,204,113,0.1); border:1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:8px 12px; border-radius:8px; font-size:12px; margin-bottom:15px; align-items:center; gap:8px;">
                <i class="fas fa-check-circle"></i> Profile details auto-filled! You only need to select date, slot & payment mode.
            </div>
            <?php endif; ?>

            <!-- Dynamic Service Details Badge -->
            <div class="service-info-badge">
                <div class="service-info-details">
                    <span class="service-info-label">Selected Service</span>
                    <span class="service-info-name" id="displayServiceName"><?= $service_name_query ?></span>
                </div>
                <span class="service-info-price" id="displayServicePrice"><?= $service_price_query ?></span>
            </div>

            <!-- Form -->
            <?php if ($error_msg): ?><div style='color:#e74c3c; margin-bottom:15px; font-size:14px; text-align:center;'><?= $error_msg ?></div><?php endif; ?>
<form id="bookingForm" method="POST" action="booking.php">
                <!-- Hidden inputs to submit actual service data -->
                <input type="hidden" id="serviceNameInput" name="service_name" value="<?= $service_name_query ?>">
                <input type="hidden" id="servicePriceInput" name="service_price" value="<?= $service_price_query ?>">

                <div class="form-grid">
                    <!-- Name -->
                    <div class="input-group">
                        <label for="fullName">Your Full Name <span>*</span></label>
                        <div class="input-wrapper">
                            <input type="text" id="fullName" name="fullName" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($user_profile['name']) ?>" required>
                            <i class="fas fa-user"></i>
                        </div>
                    </div>


                    <!-- Email & Phone Grid -->
                    <div class="form-grid-2">
                        <div class="input-group">
                            <label for="phone">Phone Number <span>*</span></label>
                            <div class="input-wrapper">
                                <input type="tel" id="phone" name="phone" class="form-control" placeholder="9876543210" pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number" value="<?= htmlspecialchars($user_profile['phone']) ?>" required>
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="email">Email Address <span>*</span></label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" value="<?= htmlspecialchars($user_profile['email']) ?>" required>
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Date & Time Grid -->
                    <div class="form-grid-2">
                        <div class="input-group">
                            <label for="bookingDate">Preferred Date <span>*</span></label>
                            <div class="input-wrapper">
                                <input type="date" id="bookingDate" name="bookingDate" class="form-control" required style="padding-left: 42px;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="timeSlot">Preferred Time Slot <span>*</span></label>
                            <div class="input-wrapper">
                                <select id="timeSlot" name="timeSlot" class="form-control" required>
                                    <option value="" disabled selected>Select a slot</option>
                                    <option value="Morning (9:00 AM - 12:00 PM)">Morning (9:00 AM - 12:00 PM)</option>
                                    <option value="Afternoon (12:00 PM - 3:00 PM)">Afternoon (12:00 PM - 3:00 PM)</option>
                                    <option value="Evening (3:00 PM - 6:00 PM)">Evening (3:00 PM - 6:00 PM)</option>
                                </select>
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="input-group">
                        <label for="address">Service Address <span>*</span></label>
                        <div class="input-wrapper">
                            <textarea id="address" name="address" class="form-control" rows="3" placeholder="Enter complete home/office address where service is required" required><?= htmlspecialchars($user_profile['address']) ?></textarea>
                        </div>
                    </div>

                                        <!-- Payment Mode -->
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label for="paymentmode">Payment Mode <span>*</span></label>
                        <div class="input-wrapper">
                            <select id="paymentmode" name="paymentmode" class="form-control" required>
                                <option value="cash" selected>Cash (Pay after service)</option>
                                <option value="online">Online (Pay now via Gateway)</option>
                            </select>
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="input-group">
                        <label for="instructions">Special Instructions (Optional)</label>
                        <div class="input-wrapper">
                            <textarea id="instructions" name="instructions" class="form-control" rows="2" placeholder="Any specific requirements or information for the technician"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn" id="submitBtn">
                    <span id="btnText">Confirm Booking</span>
                    <span class="spinner" id="btnSpinner"></span>
                </button>
            </form>
        </div>

                <!-- Payment Gateway Modal -->
        <div id="paymentModal" class="payment-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center;">
            <div class="payment-modal-box" style="background:var(--card-bg); width:100%; max-width:400px; padding:30px; border-radius:12px; text-align:center; border:1px solid var(--primary);">
                <i class="fas fa-mobile-alt" style="font-size:48px; color:var(--primary); margin-bottom:15px;"></i>
                <h3 style="color:#fff; font-family:'Oswald', sans-serif; margin-bottom:10px;">Pay via UPI</h3>
                <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Enter your UPI ID to securely process your payment.</p>
                <div style="margin-bottom:20px; text-align:left;">
                    <label style="display:block; color:var(--text-muted); font-size:12px; margin-bottom:5px;">Your UPI ID</label>
                    <input type="text" id="upiIdInput" class="form-control" placeholder="example@upi" style="width:100%; border:1px solid rgba(255,255,255,0.2); background:rgba(0,0,0,0.3); padding:10px; color:#fff; border-radius:6px;">
                    <div id="upiError" style="color:#e74c3c; font-size:12px; margin-top:5px; display:none;">Please enter a valid UPI ID (e.g., name@bank)</div>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="cancelPayment()" style="flex:1; padding:12px; background:transparent; border:1px solid rgba(255,255,255,0.2); color:#fff; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="button" onclick="confirmPayment()" style="flex:1; padding:12px; background:var(--primary); border:none; color:#000; font-weight:bold; border-radius:6px; cursor:pointer;">Pay Now</button>
                </div>
            </div>
        </div>

        <!-- Booking Success State -->
        <?php else: ?>
<div class="success-wrapper" id="successSection" style="display:block;">
            <div class="success-icon-container">
                <div class="checkmark-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            
            <h2 class="success-title">Booking Confirmed!</h2>
            <p class="success-message">
                Your service booking has been registered successfully. Our representative will contact you shortly to confirm technician allocation.
            </p>

            <div class="summary-card">
                <div class="summary-row">
                    <span class="summary-label">Booking ID</span>
                    <span class="summary-value highlight" id="successBookingId"><?= $success_data["booking_id"] ?? "" ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Service</span>
                    <span class="summary-value" id="successService"><?= $success_data["service"] ?? "" ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Date & Time</span>
                    <span class="summary-value" id="successDateTime"><?= $success_data["date_time"] ?? "" ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Customer</span>
                    <span class="summary-value" id="successCustomer"><?= $success_data["customer"] ?? "" ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Phone</span>
                    <span class="summary-value" id="successPhone"><?= $success_data["phone"] ?? "" ?></span>
                </div>
                <?php if (!empty($success_data["otp"])): ?>
                <div class="summary-row" style="background:rgba(46,204,113,0.12); padding:10px; border-radius:8px; margin:8px 0; border:1px solid rgba(46,204,113,0.3);">
                    <span class="summary-label" style="color:#2ecc71; font-weight:600;"><i class="fas fa-key"></i> Service Start OTP</span>
                    <span class="summary-value" style="color:#2ecc71; font-size:18px; font-weight:bold; letter-spacing:2px;"><?= $success_data["otp"] ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Service Provider Details Row -->
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid rgba(255,255,255,0.1);">
                    <div style="font-size:11px; text-transform:uppercase; color:var(--primary); font-weight:600; letter-spacing:1px; margin-bottom:8px;">Assigned Service Provider</div>
                    <div style="background:rgba(209,159,104,0.1); border:1px solid rgba(209,159,104,0.25); border-radius:8px; padding:12px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:600; color:#fff; font-size:15px;">
                                <?= $success_data["provider_name"] ?? "Technician" ?>
                                <span style="background:rgba(241,196,15,0.2); color:#f1c40f; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:bold; margin-left:6px;"><i class="fas fa-star" style="font-size:10px;"></i> <?= $success_data["provider_rating"] ?? "4.8" ?></span>
                            </div>
                            <div style="color:var(--text-muted); font-size:12px; margin-top:3px;">
                                Phone: <strong style="color:#fff;"><?= $success_data["provider_phone"] ?? "N/A" ?></strong> <?= !empty($success_data["provider_exp"]) ? "• " . $success_data["provider_exp"] : "" ?>
                            </div>
                        </div>
                        <?php if (!empty($success_data["provider_phone"]) && $success_data["provider_phone"] !== 'N/A'): ?>
                            <a href="tel:<?= $success_data["provider_phone"] ?>" style="background:var(--primary); color:#000; padding:6px 12px; border-radius:20px; font-weight:bold; font-size:12px; text-decoration:none;"><i class="fas fa-phone"></i> Call</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Grand Total & Payment Summary Row -->
                <div style="margin-top:15px; padding-top:15px; border-top:1px dashed rgba(255,255,255,0.15);">
                    <div style="font-size:11px; text-transform:uppercase; color:var(--primary); font-weight:600; letter-spacing:1px; margin-bottom:8px;">Payment & Bill Summary</div>
                    <div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:12px 16px;">
                        <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-muted); margin-bottom:6px;">
                            <span>Service Amount:</span>
                            <span style="color:#fff; font-weight:500;"><?= $success_data["formatted_amount"] ?? "₹0.00" ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-muted); margin-bottom:6px;">
                            <span>Taxes & Conveniences:</span>
                            <span style="color:#2ecc71; font-weight:500;">₹0.00 (Included)</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-muted); margin-bottom:8px;">
                            <span>Payment Method:</span>
                            <span style="color:#fff; font-weight:600; background:rgba(255,255,255,0.1); padding:2px 8px; border-radius:4px; font-size:11px;"><?= $success_data["paymentmode"] ?? "CASH" ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(255,255,255,0.1); padding-top:8px; margin-top:4px;">
                            <span style="font-size:15px; font-weight:bold; color:#fff;">Grand Total:</span>
                            <span style="font-size:20px; font-weight:bold; color:var(--primary); font-family:'Oswald', sans-serif;"><?= $success_data["formatted_amount"] ?? "₹0.00" ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <?php if (!empty($success_data["booking_id_raw"])): ?>
                <a href="/Project/api/invoice.php?bookingid=<?= $success_data['booking_id_raw'] ?>" target="_blank" class="close-btn-secondary" style="background:var(--primary); color:#000; font-weight:bold; border-color:var(--primary); text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
                    <i class="fas fa-file-invoice"></i> View Invoice
                </a>
                <?php endif; ?>
                <button class="close-btn-secondary" onclick="closeModalInParent()">Close Window</button>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Logic Script -->
        <script>
        const form = document.getElementById('bookingForm');
        if(form) {
            form.addEventListener('submit', function(e) {
                const paymentMode = document.getElementById('paymentmode').value;
                if (paymentMode === 'online') {
                    // Check if the form has been intercepted already
                    if (!form.dataset.paymentConfirmed) {
                        e.preventDefault(); // Stop submission
                        document.getElementById('paymentModal').style.display = 'flex';
                    }
                }
            });
        }

        function cancelPayment() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        function confirmPayment() {
            const upiInput = document.getElementById('upiIdInput').value.trim();
            if (!upiInput.includes('@') || upiInput === '') {
                document.getElementById('upiError').style.display = 'block';
                return;
            }
            document.getElementById('upiError').style.display = 'none';

            document.getElementById('paymentModal').style.display = 'none';
            // Show a processing state
            document.getElementById('btnText').textContent = 'Verifying Payment...';
            document.getElementById('btnSpinner').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            
            // Mark as confirmed and submit
            form.dataset.paymentConfirmed = "true";
            
            // Artificial delay to simulate gateway
            setTimeout(() => {
                form.submit(); // Submits the form data to the database
            }, 1500);
        }
        // Setup Date input constraints (minimum date is today)
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1; // Months start at 0
        let dd = today.getDate();

        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;

        const formattedToday = yyyy + '-' + mm + '-' + dd;
        document.getElementById('bookingDate').setAttribute('min', formattedToday);

        // Parse query params to set service details
        // Run query param parser on load
        window.addEventListener('DOMContentLoaded', () => {
            

            // Check if page is loaded inside iframe
            if (window.self !== window.top) {
                // Inside iframe: Show close button in the logo bar
                document.getElementById('iframeCloseBtn').style.display = 'block';
            }
        });

        // Close modal messaging to parent window
        function closeModalInParent() {
            if (window.self !== window.top) {
                // Post message to parent page to trigger modal close
                window.parent.postMessage({ action: 'close-booking-modal' }, '*');
            } else {
                // Direct access: Redirect back to services.html
                window.location.href = 'services.html';
            }
        }

        
        // Send message to parent that booking was created successfully (if PHP was successful)
        <?php if ($booking_success): ?>
        if (window.self !== window.top) {
            window.parent.postMessage({
                action: "booking-success",
                bookingId: "<?= $success_data['booking_id'] ?>",
                serviceName: "<?= $success_data['service'] ?>",
                customerName: "<?= $success_data['customer'] ?>"
            }, "*");
        }
        <?php endif; ?>
    </script>

    <script src="/Project/assets/js/auth-interceptor.js"></script>
    <script>
        const form = document.getElementById('bookingForm');
        if(form) {
            form.addEventListener('submit', function(e) {
                const paymentMode = document.getElementById('paymentmode').value;
                if (paymentMode === 'online') {
                    // Check if the form has been intercepted already
                    if (!form.dataset.paymentConfirmed) {
                        e.preventDefault(); // Stop submission
                        document.getElementById('paymentModal').style.display = 'flex';
                    }
                }
            });
        }

        function cancelPayment() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        function confirmPayment() {
            const upiInput = document.getElementById('upiIdInput').value.trim();
            if (!upiInput.includes('@') || upiInput === '') {
                document.getElementById('upiError').style.display = 'block';
                return;
            }
            document.getElementById('upiError').style.display = 'none';

            document.getElementById('paymentModal').style.display = 'none';
            // Show a processing state
            document.getElementById('btnText').textContent = 'Verifying Payment...';
            document.getElementById('btnSpinner').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
            
            // Mark as confirmed and submit
            form.dataset.paymentConfirmed = "true";
            
            // Artificial delay to simulate gateway
            setTimeout(() => {
                form.submit(); // Submits the form data to the database
            }, 1500);
        }
    // Profile details are now auto-filled server-side via PHP

    fetch('/Project/api/auth.php?action=check&role=user')
        .then(res => res.json())
        .then(data => {
            if (data.logged_in) {
                document.querySelectorAll('.auth-login').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.auth-user').forEach(el => el.style.display = 'block');
            } else {
                document.querySelectorAll('.auth-login').forEach(el => el.style.display = 'block');
                document.querySelectorAll('.auth-user').forEach(el => el.style.display = 'none');
            }
        });
</script>
</body>
</html>

