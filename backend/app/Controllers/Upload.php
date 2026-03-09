<?php

namespace App\Controllers;

use App\Models\User;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Upload extends Controller
{
    use ResponseTrait;

    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const ALLOWED_DOC_TYPES   = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    private const MAX_FILE_SIZE_MB    = 10;

    /**
     * Upload profile picture
     * POST /upload/profile-picture
     */
    public function uploadProfilePicture()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()) {
                return $this->failValidationError('Invalid or missing file');
            }

            if (!in_array($file->getMimeType(), self::ALLOWED_IMAGE_TYPES)) {
                return $this->failValidationError('Only JPEG, PNG, WebP, and GIF images are allowed');
            }

            if ($file->getSizeByUnit('mb') > self::MAX_FILE_SIZE_MB) {
                return $this->failValidationError('File size must not exceed ' . self::MAX_FILE_SIZE_MB . 'MB');
            }

            $uploadDir = WRITEPATH . 'uploads/profiles/';
            $fileName  = 'profile_' . time() . '_' . $file->getRandomName();

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $fileName);

            $url = base_url('uploads/profiles/' . $fileName);

            // Update user's profile_picture_url
            $user = auth()->user();
            if ($user) {
                (new User())->update($user->id, ['profile_picture_url' => $url]);
            }

            return $this->respond([
                'message'  => 'Profile picture uploaded successfully',
                'url'      => $url,
                'filename' => $fileName,
            ], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Upload::uploadProfilePicture - ' . $e->getMessage());
            return $this->failServerError('Error uploading profile picture');
        }
    }

    /**
     * Upload certificate
     * POST /upload/certificate
     */
    public function uploadCertificate()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()) {
                return $this->failValidationError('Invalid or missing file');
            }

            $allowed = array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_DOC_TYPES);
            if (!in_array($file->getMimeType(), $allowed)) {
                return $this->failValidationError('Only images and PDF/Word documents are allowed');
            }

            if ($file->getSizeByUnit('mb') > self::MAX_FILE_SIZE_MB) {
                return $this->failValidationError('File size must not exceed ' . self::MAX_FILE_SIZE_MB . 'MB');
            }

            $uploadDir = WRITEPATH . 'uploads/certificates/';
            $fileName  = 'cert_' . time() . '_' . $file->getRandomName();

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $fileName);

            return $this->respond([
                'message'  => 'Certificate uploaded successfully',
                'url'      => base_url('uploads/certificates/' . $fileName),
                'filename' => $fileName,
                'size_kb'  => round($file->getSize() / 1024, 1),
            ], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Upload::uploadCertificate - ' . $e->getMessage());
            return $this->failServerError('Error uploading certificate');
        }
    }

    /**
     * Upload compliance document
     * POST /upload/document
     */
    public function uploadDocument()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()) {
                return $this->failValidationError('Invalid or missing file');
            }

            $allowed = array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_DOC_TYPES);
            if (!in_array($file->getMimeType(), $allowed)) {
                return $this->failValidationError('Only images and PDF/Word documents are allowed');
            }

            if ($file->getSizeByUnit('mb') > self::MAX_FILE_SIZE_MB) {
                return $this->failValidationError('File size must not exceed ' . self::MAX_FILE_SIZE_MB . 'MB');
            }

            $uploadDir = WRITEPATH . 'uploads/documents/';
            $fileName  = 'doc_' . time() . '_' . $file->getRandomName();

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $fileName);

            return $this->respond([
                'message'  => 'Document uploaded successfully',
                'url'      => base_url('uploads/documents/' . $fileName),
                'filename' => $fileName,
                'size_kb'  => round($file->getSize() / 1024, 1),
            ], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Upload::uploadDocument - ' . $e->getMessage());
            return $this->failServerError('Error uploading document');
        }
    }

    /**
     * Bulk upload employees via CSV
     * POST /upload/bulk-employees
     * Required CSV columns: employee_id, first_name, last_name, email
     * Optional: phone, date_of_birth, nationality, status
     */
    public function bulkUploadEmployees()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file || !$file->isValid()) {
                return $this->failValidationError('Invalid or missing CSV file');
            }

            if (!in_array($file->getClientExtension(), ['csv', 'txt'])) {
                return $this->failValidationError('Only CSV files are accepted');
            }

            if ($file->getSizeByUnit('mb') > 5) {
                return $this->failValidationError('CSV file must not exceed 5MB');
            }

            $uploadDir = WRITEPATH . 'uploads/bulk/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'bulk_' . time() . '.csv';
            $file->move($uploadDir, $fileName);
            $filePath = $uploadDir . $fileName;

            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return $this->failServerError('Could not read uploaded CSV file');
            }

            // Read and normalise header row
            $rawHeaders = fgetcsv($handle);
            $headers    = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);

            $required = ['employee_id', 'first_name', 'last_name', 'email'];
            $missing  = array_diff($required, $headers);

            if (!empty($missing)) {
                fclose($handle);
                unlink($filePath);
                return $this->failValidationError('Missing required CSV columns: ' . implode(', ', $missing));
            }

            $headerMap     = array_flip($headers);
            $employeeModel = new User();

            $inserted  = 0;
            $updated   = 0;
            $failed    = 0;
            $errors    = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) < count($required)) {
                    $errors[] = "Row $rowNumber: insufficient columns";
                    $failed++;
                    continue;
                }

                // Map columns to data
                $data = [];
                foreach ($headerMap as $col => $index) {
                    $data[$col] = isset($row[$index]) ? trim($row[$index]) : '';
                }

                // Validate required fields
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row $rowNumber: invalid or missing email";
                    $failed++;
                    continue;
                }

                if (empty($data['first_name']) || empty($data['last_name'])) {
                    $errors[] = "Row $rowNumber: first_name and last_name are required";
                    $failed++;
                    continue;
                }

                try {
                    $existing = $employeeModel->where('email', $data['email'])->first();

                    if ($existing) {
                        // Update existing — don't touch email or employee_id
                        unset($data['email'], $data['employee_id']);
                        $employeeModel->update($existing['id'], $data);
                        $updated++;
                    } else {
                        $data['status'] = !empty($data['status']) ? $data['status'] : 'Active';
                        $employeeModel->insert($data);
                        $inserted++;
                    }
                } catch (\Throwable $rowError) {
                    $errors[] = "Row $rowNumber: " . $rowError->getMessage();
                    $failed++;
                }
            }

            fclose($handle);
            unlink($filePath);

            return $this->respond([
                'message'  => 'Bulk upload completed',
                'inserted' => $inserted,
                'updated'  => $updated,
                'failed'   => $failed,
                'errors'   => array_slice($errors, 0, 50),
            ], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Upload::bulkUploadEmployees - ' . $e->getMessage());
            return $this->failServerError('Error processing bulk employee upload');
        }
    }
}
