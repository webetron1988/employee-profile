<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\JobInformation;
use App\Models\EmploymentHistory;
use App\Models\OrgHierarchy;
use App\Models\Promotion;
use App\Models\Transfer;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Job extends Controller
{
    use ResponseTrait;

    protected $employee;
    protected $jobInformation;
    protected $employmentHistory;
    protected $orgHierarchy;
    protected $promotion;
    protected $transfer;

    public function __construct()
    {
        $this->employee = new Employee();
        $this->jobInformation = new JobInformation();
        $this->employmentHistory = new EmploymentHistory();
        $this->orgHierarchy = new OrgHierarchy();
        $this->promotion = new Promotion();
        $this->transfer = new Transfer();
    }

    /**
     * Get current user's job information
     * GET /job/information
     */
    public function getJobInformation()
    {
        try {
            $userId = auth()->user()->id;
            $jobInfo = $this->jobInformation->where('employee_id', $userId)->first();

            if (!$jobInfo) {
                return $this->failNotFound('Job information not found');
            }

            // Add manager details
            if ($jobInfo['reporting_manager_id']) {
                $manager = $this->employee->find($jobInfo['reporting_manager_id']);
                $jobInfo['reporting_manager'] = $manager ? [
                    'id' => $manager['id'],
                    'name' => $manager['first_name'] . ' ' . $manager['last_name'],
                    'email' => $manager['email']
                ] : null;
            }

            return $this->respond(['data' => $jobInfo], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching job information');
        }
    }

    /**
     * Get job information for specific employee
     * GET /job/information/{id}
     */
    public function getJobInformationById($id)
    {
        try {
            $jobInfo = $this->jobInformation->where('employee_id', $id)->first();

            if (!$jobInfo) {
                return $this->failNotFound('Job information not found');
            }

            return $this->respond(['data' => $jobInfo], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching job information');
        }
    }

    /**
     * Get employment history
     * GET /job/history
     */
    public function getEmploymentHistory()
    {
        try {
            $userId = auth()->user()->id;
            $history = $this->employmentHistory
                ->where('employee_id', $userId)
                ->orderBy('end_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $history], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching employment history');
        }
    }

    /**
     * Get employment history entry by ID
     * GET /job/history/{id}
     */
    public function getEmploymentHistoryId($id)
    {
        try {
            $userId = auth()->user()->id;
            $history = $this->employmentHistory->find($id);

            if (!$history || $history['employee_id'] != $userId) {
                return $this->failForbidden('Employment history not found or unauthorized');
            }

            return $this->respond(['data' => $history], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching employment history');
        }
    }

    /**
     * Add employment history entry
     * POST /job/history
     */
    public function addEmploymentHistory()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->employmentHistory->insert($data)) {
                return $this->respond(['message' => 'Employment history added'], 201);
            }

            return $this->fail($this->employmentHistory->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding employment history');
        }
    }

    /**
     * Get organizational hierarchy
     * GET /job/org-hierarchy
     */
    public function getOrgHierarchy()
    {
        try {
            // Get all org hierarchy levels
            $hierarchy = $this->orgHierarchy
                ->orderBy('level', 'ASC')
                ->findAll();

            return $this->respond(['data' => $hierarchy], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching organizational hierarchy');
        }
    }

    /**
     * Get organizational hierarchy by ID
     * GET /job/org-hierarchy/{id}
     */
    public function getOrgHierarchyId($id)
    {
        try {
            $hierarchy = $this->orgHierarchy->find($id);

            if (!$hierarchy) {
                return $this->failNotFound('Organizational hierarchy not found');
            }

            // Add parent information if exists
            if ($hierarchy['parent_id']) {
                $parent = $this->orgHierarchy->find($hierarchy['parent_id']);
                $hierarchy['parent'] = $parent;
            }

            // Add children
            $children = $this->orgHierarchy->where('parent_id', $id)->findAll();
            $hierarchy['children'] = $children;

            return $this->respond(['data' => $hierarchy], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching organizational hierarchy');
        }
    }

    /**
     * Get team members for current user
     * GET /job/team-members
     */
    public function getTeamMembers()
    {
        try {
            $userId = auth()->user()->id;

            // Get user's job info to find team
            $userJob = $this->jobInformation->where('employee_id', $userId)->first();

            if (!$userJob) {
                return $this->failNotFound('Job information not found');
            }

            // Get all employees reporting to this user
            $teamMembers = $this->jobInformation
                ->where('reporting_manager_id', $userId)
                ->findAll();

            // Enhance with employee details
            $enhanced = [];
            foreach ($teamMembers as $member) {
                $employee = $this->employee->find($member['employee_id']);
                $enhanced[] = array_merge($employee, $member);
            }

            return $this->respond(['data' => $enhanced], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching team members');
        }
    }

    /**
     * Get reporting structure
     * GET /job/reporting-structure
     */
    public function getReportingStructure()
    {
        try {
            $userId = auth()->user()->id;
            $userJob = $this->jobInformation->where('employee_id', $userId)->first();

            if (!$userJob) {
                return $this->failNotFound('Job information not found');
            }

            $structure = [];

            // Current user
            $currentUser = $this->employee->find($userId);
            $structure['current_user'] = [
                'id' => $currentUser['id'],
                'name' => $currentUser['first_name'] . ' ' . $currentUser['last_name'],
                'designation' => $userJob['designation']
            ];

            // Direct manager
            if ($userJob['reporting_manager_id']) {
                $manager = $this->employee->find($userJob['reporting_manager_id']);
                $managerJob = $this->jobInformation->where('employee_id', $userJob['reporting_manager_id'])->first();
                $structure['reporting_manager'] = [
                    'id' => $manager['id'],
                    'name' => $manager['first_name'] . ' ' . $manager['last_name'],
                    'designation' => $managerJob['designation'] ?? null
                ];
            }

            // Direct reports
            $directReports = $this->jobInformation->where('reporting_manager_id', $userId)->findAll();
            $structure['direct_reports'] = [];

            foreach ($directReports as $report) {
                $reportEmployee = $this->employee->find($report['employee_id']);
                $structure['direct_reports'][] = [
                    'id' => $reportEmployee['id'],
                    'name' => $reportEmployee['first_name'] . ' ' . $reportEmployee['last_name'],
                    'designation' => $report['designation'] ?? null
                ];
            }

            return $this->respond(['data' => $structure], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching reporting structure');
        }
    }

    /**
     * Get promotions for current user
     * GET /job/promotions
     */
    public function getPromotions()
    {
        try {
            $userId = auth()->user()->id;
            $promotions = $this->promotion
                ->where('employee_id', $userId)
                ->orderBy('promotion_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $promotions], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching promotions');
        }
    }

    /**
     * Create promotion record (HR only)
     * POST /job/promotions
     */
    public function createPromotion()
    {
        try {
            $data = $this->request->getJSON(true);

            if ($this->promotion->insert($data)) {
                return $this->respond(['message' => 'Promotion created'], 201);
            }

            return $this->fail($this->promotion->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating promotion');
        }
    }

    /**
     * Get transfers for current user
     * GET /job/transfers
     */
    public function getTransfers()
    {
        try {
            $userId = auth()->user()->id;
            $transfers = $this->transfer
                ->where('employee_id', $userId)
                ->orderBy('transfer_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $transfers], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching transfers');
        }
    }

    /**
     * Create transfer record (HR only)
     * POST /job/transfers
     */
    public function createTransfer()
    {
        try {
            $data = $this->request->getJSON(true);

            if ($this->transfer->insert($data)) {
                return $this->respond(['message' => 'Transfer created'], 201);
            }

            return $this->fail($this->transfer->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating transfer');
        }
    }
}
