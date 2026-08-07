<?php
require_once __DIR__ . '/config.php';
$db = getDB();

$bookingid = isset($_GET['bookingid']) ? (int)$_GET['bookingid'] : 0;
if ($bookingid <= 0) {
    die("Invalid Invoice Request");
}

// Fetch booking details with user and provider information
$stmt = $db->prepare("
    SELECT b.*, 
           s.name as category_name,
           u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address as customer_address, u.city as customer_city, u.pincode as customer_pincode,
           p.name as provider_name, p.phone as provider_phone, p.email as provider_email, p.category as provider_category
    FROM booking b
    LEFT JOIN services s ON b.serviceid = s.serviceid
    LEFT JOIN users u ON b.userid = u.userid
    LEFT JOIN providers p ON b.providerid = p.providerid
    WHERE b.bookingid = ?
");
$stmt->bind_param("i", $bookingid);
$stmt->execute();
$res = $stmt->get_result();
$booking = $res->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found");
}

// Extract dynamic service package name if stored in description
$service_name = $booking['category_name'] ?? 'General Service';
if (!empty($booking['description']) && preg_match('/Package:\s*([^\n\r]+)/i', $booking['description'], $m)) {
    $service_name = trim($m[1]);
}

$invoice_ref = 'INV-BK-' . str_pad($booking['bookingid'], 5, '0', STR_PAD_LEFT);
$booking_ref = 'BK-' . $booking['bookingid'];
$amount = (float)$booking['amount'];
$formatted_amount = number_format($amount, 2);

// Customer full address
$full_address = $booking['customer_address'] ?? 'Customer Location';
if (!empty($booking['customer_city']) && strpos($full_address, $booking['customer_city']) === false) {
    $full_address .= ', ' . $booking['customer_city'];
}
if (!empty($booking['customer_pincode']) && strpos($full_address, $booking['customer_pincode']) === false) {
    $full_address .= ' - ' . $booking['customer_pincode'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= $invoice_ref ?> - ServiceHub</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; color: #333; padding: 30px 15px; }
        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 40px;
            border: 1px solid #e1e5eb;
            position: relative;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }
        .brand-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #111;
            letter-spacing: 1px;
        }
        .brand-logo span { color: #d19f68; }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 24px;
            color: #d19f68;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .invoice-meta {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 35px;
        }
        .info-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #edf2f7;
        }
        .info-box h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #d19f68;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .info-box p {
            font-size: 13px;
            color: #4a5568;
            line-height: 1.6;
        }
        .info-box strong { color: #1a202c; }
        .table-wrapper {
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #edf2f7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th {
            background: #1e2530;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 14px 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 16px 18px;
            font-size: 14px;
            color: #2d3748;
            border-bottom: 1px solid #edf2f7;
        }
        tr:last-child td { border-bottom: none; }
        .summary-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
        }
        .payment-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-paid { background: rgba(46,204,113,0.15); color: #27ae60; border: 1px solid rgba(46,204,113,0.3); }
        .status-pending { background: rgba(241,196,15,0.15); color: #d35400; border: 1px solid rgba(241,196,15,0.3); }
        .status-completed { background: rgba(52,152,219,0.15); color: #2980b9; border: 1px solid rgba(52,152,219,0.3); }

        .totals-table {
            width: 280px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: #4a5568;
        }
        .totals-row.grand-total {
            border-top: 2px solid #1e2530;
            padding-top: 12px;
            margin-top: 8px;
            font-size: 18px;
            font-weight: 700;
            color: #111;
        }
        .invoice-footer {
            border-top: 1px solid #edf2f7;
            padding-top: 25px;
            margin-top: 40px;
            text-align: center;
            color: #718096;
            font-size: 12px;
        }
        .actions-bar {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: linear-gradient(135deg, #d19f68, #b88652);
            color: #fff;
            padding: 10px 22px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(209,159,104,0.3);
            text-decoration: none;
            transition: all 0.25s;
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(209,159,104,0.4); }
        .btn-back {
            color: #4a5568;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .actions-bar { display: none !important; }
            .invoice-card { box-shadow: none; border: none; padding: 20px 0; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="javascript:history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> Back to My Bookings</a>
        <button class="btn-print" onclick="window.print()"><i class="fas fa-file-pdf"></i> Download / Print Invoice</button>
    </div>

    <div class="invoice-card">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div>
                <div class="brand-logo">Service<span>Hub</span></div>
                <div class="invoice-meta" style="margin-top: 5px;">
                    <div><strong>ServiceHub On-Demand Services</strong></div>
                    <div>Email: servicehub84@gmail.com | Phone: +91 8484848484</div>
                </div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-meta">
                    <div>Ref: <strong><?= $invoice_ref ?></strong></div>
                    <div>Booking: <strong><?= $booking_ref ?></strong></div>
                    <div>Date: <strong><?= date('F j, Y', strtotime($booking['date'])) ?></strong></div>
                </div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="details-grid">
            <div class="info-box">
                <h3>Billed To (Customer)</h3>
                <p><strong><?= htmlspecialchars($booking['customer_name'] ?? 'Customer') ?></strong></p>
                <p><i class="fas fa-envelope" style="font-size:11px; color:#d19f68;"></i> <?= htmlspecialchars($booking['customer_email'] ?? 'N/A') ?></p>
                <p><i class="fas fa-phone" style="font-size:11px; color:#d19f68;"></i> <?= htmlspecialchars($booking['customer_phone'] ?? 'N/A') ?></p>
                <p><i class="fas fa-map-marker-alt" style="font-size:11px; color:#d19f68;"></i> <?= htmlspecialchars($full_address) ?></p>
            </div>

            <div class="info-box">
                <h3>Service Provider</h3>
                <p><strong><?= htmlspecialchars($booking['provider_name'] ?? 'Assigned Technician') ?></strong></p>
                <p><i class="fas fa-tools" style="font-size:11px; color:#d19f68;"></i> Category: <?= htmlspecialchars($booking['provider_category'] ?? $booking['category_name'] ?? 'Service') ?></p>
                <p><i class="fas fa-phone" style="font-size:11px; color:#d19f68;"></i> Contact: <?= htmlspecialchars($booking['provider_phone'] ?? 'N/A') ?></p>
                <p><i class="fas fa-clock" style="font-size:11px; color:#d19f68;"></i> Scheduled Time: <?= htmlspecialchars($booking['time']) ?></p>
            </div>
        </div>

        <!-- Service Line Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%;">Service Description</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Rate</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($service_name) ?></strong>
                            <div style="font-size: 12px; color: #718096; margin-top: 3px;">
                                Service Category: <?= htmlspecialchars($booking['category_name'] ?? 'Home Services') ?>
                            </div>
                        </td>
                        <td style="text-align: center;">1</td>
                        <td style="text-align: right;">₹<?= $formatted_amount ?></td>
                        <td style="text-align: right;">₹<?= $formatted_amount ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals & Payment Info -->
        <div class="summary-section">
            <div>
                <div style="font-size: 12px; color: #718096; margin-bottom: 6px; font-weight: 600;">Payment Details:</div>
                <div style="font-size: 13px; color: #2d3748; margin-bottom: 8px;">
                    Mode: <strong><?= strtoupper($booking['paymentmode']) ?></strong>
                </div>
                <?php
                    $badgeClass = 'status-pending';
                    $statusText = strtoupper($booking['status']);
                    if ($booking['status'] === 'completed' || $booking['paymentmode'] === 'online') {
                        $badgeClass = 'status-paid';
                        $statusText = 'PAID (' . strtoupper($booking['paymentmode']) . ')';
                    } elseif ($booking['status'] === 'confirmed') {
                        $badgeClass = 'status-completed';
                    }
                ?>
                <span class="payment-status-badge <?= $badgeClass ?>">
                    <i class="fas fa-check-circle"></i> <?= $statusText ?>
                </span>
            </div>

            <div class="totals-table">
                <div class="totals-row">
                    <span>Subtotal:</span>
                    <span>₹<?= $formatted_amount ?></span>
                </div>
                <div class="totals-row">
                    <span>Taxes & Service Fee:</span>
                    <span>₹0.00 (Included)</span>
                </div>
                <div class="totals-row grand-total">
                    <span>Total Amount:</span>
                    <span style="color:#d19f68;">₹<?= $formatted_amount ?></span>
                </div>
            </div>
        </div>

        <!-- Invoice Footer -->
        <div class="invoice-footer">
            <p>Thank you for booking with <strong>ServiceHub</strong>! For any questions or support, contact support at <strong>servicehub84@gmail.com</strong>.</p>
        </div>
    </div>

<script>
    // Auto-trigger browser print/Save as PDF dialog on page load
    window.addEventListener('load', function() {
        setTimeout(function() { window.print(); }, 600);
    });
</script>
</body>
</html>
