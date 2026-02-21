<?php
// ============================================================
// SNACS SHOP - App Configuration
// ============================================================

define('APP_NAME', 'कन्हैया स्नॅक्स');
define('APP_TAGLINE', 'कन्हैया स्नॅक्स');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/snacks-management-system');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('TAX_PERCENT', 0); // GST 5%
define('CURRENCY', '₹');
define('DEFAULT_MENU_IMAGE', BASE_URL . '/assets/images/default-food.png');

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Include database
require_once __DIR__ . '/database.php';

// Auth helpers
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function adminInfo() {
    return $_SESSION['admin_info'] ?? [];
}

// Response helpers
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonSuccess($data = [], $message = 'Success') {
    jsonResponse(['success' => true, 'message' => $message, 'data' => $data]);
}

function jsonError($message = 'Error', $code = 400) {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

// Format helpers
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function generateBillNumber() {
    return 'BILL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
