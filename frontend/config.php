<?php
/**
 * Employee Profile System - PHP Configuration
 * Central config, session management, and HRMS API helper
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Constants (UPDATE FOR PRODUCTION) ─────────────────────────────────────
// These URLs and keys MUST be updated per environment before deployment.
define('HRMS_BASE', 'http://localhost/hrms_extension_v2/hrms_extension_v2');
define('API_BASE', 'http://localhost:8080');
define('SITE_URL', 'http://localhost/employee-profile/frontend');
define('SITE_TITLE', 'Employee Profile System');
define('HRMS_API_KEY', 'ep_sk_a7f3c9d2e1b4068597fa3de8c12b4a5e'); // Change per environment

// ── HRMS Database (UPDATE FOR PRODUCTION) ────────────────────────────────
define('HRMS_DB_HOST', '127.0.0.1');
define('HRMS_DB_PORT', '3306');
define('HRMS_DB_NAME', 'hrms-extension-v2');
define('HRMS_DB_USER', 'root');
define('HRMS_DB_PASS', '');

// ── HRMS Database Connection ──────────────────────────────────────────────
function get_hrms_db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . HRMS_DB_HOST . ';port=' . HRMS_DB_PORT . ';dbname=' . HRMS_DB_NAME,
            HRMS_DB_USER,
            HRMS_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

// ── HRMS API Helper (server-side cURL) ─────────────────────────────────────
function hrms_api_call($endpoint, $data = []) {
    $url = HRMS_BASE . $endpoint;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-Api-Key: ' . HRMS_API_KEY],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return null;
    }

    $decoded = json_decode($response, true);
    return ($decoded && isset($decoded['data'])) ? $decoded['data'] : null;
}

// ── Session Helpers ────────────────────────────────────────────────────────
function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['access_token']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function get_session_user() {
    return $_SESSION['user'] ?? null;
}

function get_hrms_emp_id() {
    return $_SESSION['hrms_emp_id'] ?? ($_SESSION['user']['hrms_employee_id'] ?? null);
}

// ── CSRF Protection ───────────────────────────────────────────────────────
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Display Helpers ────────────────────────────────────────────────────────
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function display($val, $default = '–') {
    return e(!empty($val) ? $val : $default);
}

// ── HRMS File URL Helper (mirrors generateFileURL from HRMS common_helper) ─
function hrms_file_url($fileName, $storageJson, $clientId, $empId, $subFolder, $id = null) {
    if (empty($fileName) || empty($storageJson)) return '';
    $storage = is_array($storageJson) ? $storageJson : json_decode($storageJson, true);
    if (empty($storage) || empty($storage['provider'])) return '';
    $b64Storage = base64_encode(json_encode($storage));
    $url = HRMS_BASE . '/files/viewFile/'
        . base64_encode($clientId) . '/'
        . base64_encode($empId) . '/'
        . base64_encode(rtrim($subFolder, '/') . '/') . '/'
        . base64_encode($fileName) . '/'
        . $b64Storage;
    if (!empty($id)) $url .= '/' . base64_encode($id);
    return $url;
}

// Build profile picture URL from employee record
function profile_pic_url($profile) {
    if (empty($profile['profile'])) return '';
    $clientId = $profile['client_id'] ?? '1';
    $empId    = $profile['empID'] ?? '';
    // New FileStorage convention: profile_storage JSON exists
    if (!empty($profile['profile_storage'])) {
        $storage = is_array($profile['profile_storage'])
            ? $profile['profile_storage']
            : json_decode($profile['profile_storage'], true);
        // Local storage: serve file directly (no HRMS auth needed)
        if (!empty($storage['provider']) && $storage['provider'] === 'local') {
            return HRMS_BASE . '/uploads/' . $clientId . '/' . $empId . '/employees/profile/' . $profile['profile'];
        }
        // AWS/cloud: use viewFile URL (goes through HRMS controller for decryption)
        return hrms_file_url($profile['profile'], $storage, $clientId, $empId, 'employees/profile');
    }
    // Legacy: file in uploads/img/admin_users/
    return HRMS_BASE . '/uploads/img/admin_users/' . $profile['profile'];
}
