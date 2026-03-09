<?php

namespace App\Controllers;

use App\Models\ComplianceDocument;
use App\Models\User;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Compliance extends Controller
{
    use ResponseTrait;

    protected $complianceDocument;
    protected $employee;

    public function __construct()
    {
        $this->complianceDocument = new ComplianceDocument();
        $this->employee = new User();
    }

    /**
     * Get compliance documents for user
     * GET /compliance/documents
     */
    public function getDocuments()
    {
        try {
            $userId = auth()->user()->id;

            $documents = $this->complianceDocument
                ->where('employee_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            return $this->respond(['data' => $documents], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching compliance documents');
        }
    }

    /**
     * Get specific compliance document
     * GET /compliance/documents/{id}
     */
    public function getDocumentId($id)
    {
        try {
            $userId = auth()->user()->id;
            $document = $this->complianceDocument->find($id);

            if (!$document || $document['employee_id'] != $userId) {
                return $this->failForbidden('Document not found or unauthorized');
            }

            return $this->respond(['data' => $document], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching compliance document');
        }
    }

    /**
     * Upload compliance document (HR/Admin)
     * POST /compliance/documents
     */
    public function uploadDocument()
    {
        try {
            $file = $this->request->getFile('document');
            $employeeId = $this->request->getVar('employee_id');
            $documentType = $this->request->getVar('document_type');

            if (!$file->isValid()) {
                return $this->failValidationError('Invalid file');
            }

            // Save file
            $fileName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/compliance');

            // Create document record
            $data = [
                'employee_id' => $employeeId,
                'document_type' => $documentType,
                'document_url' => 'uploads/compliance/' . $fileName,
                'status' => 'Pending',
                'uploaded_date' => date('Y-m-d H:i:s')
            ];

            if ($this->complianceDocument->insert($data)) {
                return $this->respond(['message' => 'Document uploaded'], 201);
            }

            return $this->fail($this->complianceDocument->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error uploading document');
        }
    }

    /**
     * Update compliance document
     * PUT /compliance/documents/{id}
     */
    public function updateDocument($id)
    {
        try {
            $document = $this->complianceDocument->find($id);

            if (!$document) {
                return $this->failNotFound('Document not found');
            }

            $data = $this->request->getJSON(true);

            if ($this->complianceDocument->update($id, $data)) {
                return $this->respond(['message' => 'Document updated'], 200);
            }

            return $this->fail($this->complianceDocument->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating document');
        }
    }

    /**
     * Get document status for all employees (HR only)
     * GET /compliance/document-status
     */
    public function getDocumentStatus()
    {
        try {
            $documentType = $this->request->getVar('type');
            $status = $this->request->getVar('status');

            $query = $this->complianceDocument;

            if ($documentType) {
                $query = $query->where('document_type', $documentType);
            }

            if ($status) {
                $query = $query->where('status', $status);
            }

            $documents = $query->findAll();

            // Group by status
            $result = [
                'Pending' => [],
                'Signed' => [],
                'Expired' => [],
                'Renewed' => []
            ];

            foreach ($documents as $doc) {
                $employee = $this->employee->find($doc['employee_id']);
                if (!isset($result[$doc['status']])) {
                    $result[$doc['status']] = [];
                }

                $result[$doc['status']][] = [
                    'document_id' => $doc['id'],
                    'employee_id' => $doc['employee_id'],
                    'employee_name' => $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Unknown',
                    'document_type' => $doc['document_type'],
                    'uploaded_date' => $doc['uploaded_date'],
                    'status' => $doc['status']
                ];
            }

            return $this->respond(['data' => $result], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching document status');
        }
    }

    /**
     * Sign compliance document
     * PUT /compliance/documents/{id}/sign
     */
    public function signDocument($id)
    {
        try {
            $userId = auth()->user()->id;
            $document = $this->complianceDocument->find($id);

            if (!$document) {
                return $this->failNotFound('Document not found');
            }

            $data = [
                'status' => 'Signed',
                'signed_date' => date('Y-m-d H:i:s'),
                'signed_by_id' => $userId
            ];

            if ($this->complianceDocument->update($id, $data)) {
                return $this->respond(['message' => 'Document signed successfully'], 200);
            }

            return $this->failServerError('Error signing document');
        } catch (\Throwable $e) {
            return $this->failServerError('Error signing document');
        }
    }
}
