<?php
/**
 * HRMS SSO Redirect
 * Sets HRMS session variables and redirects user to HRMS pages seamlessly.
 * Both EP frontend and HRMS run on same Apache (localhost), sharing PHP sessions.
 */
require_once __DIR__ . '/config.php';
require_login();

$targetUrl = $_GET['to'] ?? '';
if (empty($targetUrl)) {
    header('Location: ' . HRMS_BASE);
    exit;
}

// Decode target URL
$targetUrl = urldecode($targetUrl);

// Security: only allow redirects to HRMS_BASE domain
if (strpos($targetUrl, HRMS_BASE) !== 0 && strpos($targetUrl, '/') !== 0) {
    header('Location: ' . HRMS_BASE);
    exit;
}

// If target is a relative path, prepend HRMS_BASE
if (strpos($targetUrl, 'http') !== 0) {
    $targetUrl = HRMS_BASE . '/' . ltrim($targetUrl, '/');
}

// Check if HRMS session is already active
if (!empty($_SESSION['empID'])) {
    header('Location: ' . $targetUrl);
    exit;
}

// Get the logged-in user's HRMS employee ID
$empId = get_hrms_emp_id();
if (empty($empId)) {
    header('Location: login.php');
    exit;
}

// Fetch employee data from HRMS DB and set HRMS session
try {
    $hrmsDb = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=hrms-extension-v2',
        'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $hrmsDb->prepare("
        SELECT e.*, r.role_role_name, r.role_role_code, p.positionTitle
        FROM employee e
        LEFT JOIN roles_master r ON r.role_role_id = e.role_id
        LEFT JOIN positions p ON p.positionID = e.position_id
        WHERE e.empID = ? AND e.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$empId]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($emp)) {
        // Employee not found in HRMS - redirect to HRMS login
        header('Location: ' . HRMS_BASE);
        exit;
    }

    // Fetch global settings
    $gsStmt = $hrmsDb->prepare("SELECT timezone, lms_auth_code, default_page FROM global_settings WHERE client_id = ? LIMIT 1");
    $gsStmt->execute([$empId]);
    $global = $gsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Set HRMS session variables (same as Login_model::_handleSuccessfulLogin)
    $_SESSION['un']             = $emp['name'];
    $_SESSION['uid']            = $emp['uid'];
    $_SESSION['empID']          = $emp['empID'];
    $_SESSION['email']          = $emp['email'];
    $_SESSION['client_id']      = $emp['client_id'];
    $_SESSION['role_id']        = $emp['role_id'];
    $_SESSION['role_code']      = $emp['role_role_code'] ?? '';
    $_SESSION['role_name']      = $emp['role_role_name'] ?? '';
    $_SESSION['position_title'] = $emp['positionTitle'] ?? '';
    $_SESSION['ip_address']     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SESSION['user_type']      = 'inside-user';
    $_SESSION['timezone_id']    = $global['timezone'] ?? null;

    // Redirect to target HRMS page
    header('Location: ' . $targetUrl);
    exit;

} catch (PDOException $ex) {
    // DB error - fall back to direct HRMS redirect (user will see HRMS login)
    header('Location: ' . $targetUrl);
    exit;
}
