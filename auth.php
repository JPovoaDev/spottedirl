<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getRole() {
    return $_SESSION['role'] ?? 'guest';
}

function requireRole($minRole) {
    $hierarchy = ['guest' => 0, 'user' => 1, 'simpatizante' => 2, 'admin' => 3];
    $current = $hierarchy[getRole()] ?? 0;
    $required = $hierarchy[$minRole] ?? 0;
    if ($current < $required) {
        header('Location: index.php');
        exit;
    }
}
?>