<?php
/**
 * Session Bridge - Creates PHP session from JWT login
 * Called via AJAX after successful JS-based login
 */
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// CSRF validation — skip for initial login (no session yet)
if (!empty($_SESSION['csrf_token'])) {
    $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $csrfBody = $input['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfHeader) && !verify_csrf_token($csrfBody)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }
}

if (empty($input['access_token']) || empty($input['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing token or user data']);
    exit;
}

$_SESSION['access_token'] = $input['access_token'];
$_SESSION['refresh_token'] = $input['refresh_token'] ?? '';
$_SESSION['user'] = $input['user'];
$_SESSION['hrms_emp_id'] = $input['user']['hrms_employee_id'] ?? $input['user']['id'] ?? null;
$_SESSION['login_time'] = time();

echo json_encode(['status' => 'success', 'message' => 'Session created']);
