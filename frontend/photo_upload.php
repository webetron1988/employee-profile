<?php
/**
 * Photo Upload Handler
 * Uses the same upload path + DB convention as HRMS FileStorage library:
 *   Local:  uploads/{client_id}/{empID}/employees/profile/{filename}
 *   DB:     employee.profile = filename, employee.profile_storage = JSON
 *   View:   files/viewFile/{b64 params} (HRMS Files controller serves the file)
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST only']);
    exit;
}

require_login();

// CSRF validation
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf_token($csrfHeader)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate file
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = 'No file uploaded';
    if (!empty($_FILES['file']['error'])) {
        $codes = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        ];
        $errMsg = $codes[$_FILES['file']['error']] ?? 'Upload error';
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $errMsg]);
    exit;
}

$file = $_FILES['file'];

// Validate MIME type (server-side check using finfo, not trusting client)
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Only JPEG, PNG, WebP, and GIF images are allowed']);
    exit;
}

// Validate size (max 2MB — same as HRMS config employees.profile.max_size = 2048 KB)
if ($file['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File size must not exceed 2MB']);
    exit;
}

// Get employee ID
$empId = get_hrms_emp_id();
if (empty($empId)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

// Get employee's client_id from HRMS DB
try {
    $hrmsDb = get_hrms_db();

    $empStmt = $hrmsDb->prepare("SELECT client_id, profile, profile_storage FROM employee WHERE empID = ? LIMIT 1");
    $empStmt->execute([$empId]);
    $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);

    if (!$empRow) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
        exit;
    }

    $clientId   = $empRow['client_id'];
    $oldProfile = $empRow['profile'];
    $oldStorage = $empRow['profile_storage'];

} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit;
}

// ── HRMS FileStorage upload path convention ──
// Local: HRMS_ROOT/uploads/{client_id}/{empID}/employees/profile/{filename}
$hrmsRoot  = realpath(__DIR__ . '/../../hrms_extension_v2/hrms_extension_v2');
$subFolder = 'employees/profile';
$uploadDir = $hrmsRoot . '/uploads/' . $clientId . '/' . $empId . '/' . $subFolder . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename (same pattern as HRMS FileStorage: uniqid + original name)
$fileName = uniqid('', true) . '_' . basename($file['name']);

// Move uploaded file to HRMS upload directory
if (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
    exit;
}

// Storage metadata (same structure as HRMS FileStorage returns)
$storageJson = json_encode([
    'provider' => 'local',
    'mode'     => 'local',
]);

// Delete old profile photo if it exists (local storage)
if (!empty($oldProfile) && !empty($oldStorage)) {
    $oldStorageArr = json_decode($oldStorage, true);
    if (!empty($oldStorageArr) && ($oldStorageArr['provider'] ?? '') === 'local') {
        $oldPath = $hrmsRoot . '/uploads/' . $clientId . '/' . $empId . '/' . $subFolder . '/' . $oldProfile;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }
}

// Update HRMS employee table (profile + profile_storage)
try {
    $stmt = $hrmsDb->prepare("UPDATE employee SET profile = ?, profile_storage = ? WHERE empID = ?");
    $stmt->execute([$fileName, $storageJson, $empId]);
} catch (PDOException $ex) {
    // DB failed — clean up uploaded file
    @unlink($uploadDir . $fileName);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
    exit;
}

// ── Build URL for display ──
$storageArr = json_decode($storageJson, true);
if ($storageArr['provider'] === 'local') {
    // Local: direct path (no HRMS auth needed)
    $url = HRMS_BASE . '/uploads/' . $clientId . '/' . $empId . '/' . $subFolder . '/' . $fileName;
} else {
    // AWS/cloud: viewFile URL (HRMS controller decrypts)
    $url = HRMS_BASE . '/files/viewFile/'
        . base64_encode($clientId) . '/'
        . base64_encode($empId) . '/'
        . base64_encode(rtrim($subFolder, '/') . '/') . '/'
        . base64_encode($fileName) . '/'
        . base64_encode(json_encode($storageArr));
}

echo json_encode([
    'status'   => 'success',
    'message'  => 'Profile photo updated',
    'url'      => $url,
    'filename' => $fileName,
]);
