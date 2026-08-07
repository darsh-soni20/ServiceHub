<?php
require_once __DIR__ . '/config.php';

function enforceRole($required_role) {
    $user = getAuthenticatedUser($required_role);
    if ($user) {
        return;
    }
    header('Location: /Project/login.php');
    exit();
}

if (isset($required_role)) {
    enforceRole($required_role);
}

$current_user = [];
if (isset($required_role)) {
    $resolved = getAuthenticatedUser($required_role);
    if ($resolved) {
        $current_user = $resolved;
    }
}
?>
