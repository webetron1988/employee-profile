<?php
/**
 * Token Refresh — Gets a fresh CI4 JWT.
 * Strategy: 1) Try refresh token rotation  2) Fall back to service-login with API key
 * Called by JS when ci4Api gets a 401 or token is missing/expired.
 */
require_once 'config.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Helper to return success and update session
function returnSuccess($data) {
    $_SESSION['access_token']  = $data['access_token'];
    $_SESSION['refresh_token'] = $data['refresh_token'] ?? '';

    echo json_encode([
        'status'        => 'success',
        'access_token'  => $data['access_token'],
        'refresh_token' => $data['refresh_token'] ?? '',
        'user'          => $data['user'] ?? $_SESSION['user'],
    ]);
    exit;
}

// ── Strategy 1: Refresh token rotation ──
$refreshToken = $_SESSION['refresh_token'] ?? '';
if ($refreshToken) {
    $ch = curl_init(API_BASE . '/auth/refresh');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['refresh_token' => $refreshToken]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (!empty($data['data']['access_token'])) {
            returnSuccess($data['data']);
        }
    }
}

// ── Strategy 2: Service login (API key + user identity from session) ──
$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? null;
$hrmsEmpId = $_SESSION['hrms_emp_id'] ?? ($user['hrms_employee_id'] ?? null);

if ($userId || $hrmsEmpId) {
    $payload = [];
    if ($userId) $payload['user_id'] = $userId;
    if ($hrmsEmpId) $payload['hrms_employee_id'] = $hrmsEmpId;

    $ch = curl_init(API_BASE . '/auth/service-login');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Api-Key: ' . HRMS_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (!empty($data['data']['access_token'])) {
            returnSuccess($data['data']);
        }
    }
}

http_response_code(401);
echo json_encode(['status' => 'error', 'message' => 'Token refresh failed']);
