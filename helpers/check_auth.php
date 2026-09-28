<?php 
// helpers/check_auth.php - functions for checking if a user is logged in
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Not logged in"]);
        exit;
    }
}

/**
 * System-admin equivalent of requireAuth(). Uses its own session key
 * ($_SESSION['admin_id']) so a clinic-staff session and a system-admin
 * session never collide. Every admin/*.php endpoint except login.php and
 * logout.php should call this first. Returns the admin_id for convenience.
 */
function requireAdminAuth(): int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Not logged in"]);
        exit;
    }
    return (int) $_SESSION['admin_id'];
}