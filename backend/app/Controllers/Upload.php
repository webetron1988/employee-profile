<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Upload extends Controller
{
    use ResponseTrait;

    /**
     * Upload profile picture
     * POST /upload/profile-picture
     */
    public function uploadProfilePicture()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file->isValid()) {
                return $this->failValidationError('Invalid file');
            }

            $fileName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/profiles');

            return $this->respond([
                'message' => 'Profile picture uploaded',
                'url' => 'uploads/profiles/' . $fileName
            ], 201);
        } catch (\Throwable $e) {
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

            if (!$file->isValid()) {
                return $this->failValidationError('Invalid file');
            }

            $fileName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/certificates');

            return $this->respond([
                'message' => 'Certificate uploaded',
                'url' => 'uploads/certificates/' . $fileName
            ], 201);
        } catch (\Throwable $e) {
            return $this->failServerError('Error uploading certificate');
        }
    }

    /**
     * Upload document
     * POST /upload/document
     */
    public function uploadDocument()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file->isValid()) {
                return $this->failValidationError('Invalid file');
            }

            $fileName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/documents');

            return $this->respond([
                'message' => 'Document uploaded',
                'url' => 'uploads/documents/' . $fileName
            ], 201);
        } catch (\Throwable $e) {
            return $this->failServerError('Error uploading document');
        }
    }

    /**
     * Bulk upload employees
     * POST /upload/bulk-employees
     */
    public function bulkUploadEmployees()
    {
        try {
            $file = $this->request->getFile('file');

            if (!$file->isValid()) {
                return $this->failValidationError('Invalid file');
            }

            return $this->respond([
                'message' => 'Bulk upload endpoint - not yet implemented'
            ], 201);
        } catch (\Throwable $e) {
            return $this->failServerError('Error uploading bulk employees');
        }
    }
}
