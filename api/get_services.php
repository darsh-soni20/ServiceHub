<?php
require_once 'config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['status' => 'error', 'message' => 'Method not allowed'], 405);
}

$categories = [];
$res = $db->query("SELECT * FROM services ORDER BY serviceid ASC");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sid = (int)$row['serviceid'];
        
        $subs = [];
        $stmt = $db->prepare("SELECT sub_serviceid, name, description, price, badge FROM sub_services WHERE serviceid = ? ORDER BY sub_serviceid ASC");
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $sub_res = $stmt->get_result();
        while ($sub_row = $sub_res->fetch_assoc()) {
            $sub_row['price_formatted'] = '₹' . number_format($sub_row['price'], 0);
            $subs[] = $sub_row;
        }
        $stmt->close();

        $row['sub_services'] = $subs;
        $row['total_subservices'] = count($subs);
        $categories[] = $row;
    }
}

sendJSON(['status' => 'success', 'data' => $categories]);
?>
