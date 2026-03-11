<?php

namespace App\Controllers;

use App\Models\Competency;
use App\Models\EmployeeCompetency;
use App\Models\IndividualDevelopmentPlan;
use App\Models\SuccessionPlan;
use App\Models\CareerPath;
use App\Models\User;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Talent extends Controller
{
    use ResponseTrait;

    protected $competency;
    protected $employeeCompetency;
    protected $idp;
    protected $successionPlan;
    protected $careerPath;
    protected $employee;

    public function __construct()
    {
        $this->competency = new Competency();
        $this->employeeCompetency = new EmployeeCompetency();
        $this->idp = new IndividualDevelopmentPlan();
        $this->successionPlan = new SuccessionPlan();
        $this->careerPath = new CareerPath();
        $this->employee = new User();
    }

    /**
     * Get competencies (catalog)
     * GET /talent/competencies
     */
    public function getCompetencies()
    {
        try {
            $competencies = $this->competency
                ->orderBy('competency_name', 'ASC')
                ->findAll();

            return $this->respond(['data' => $competencies], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching competencies');
        }
    }

    /**
     * Get specific competency
     * GET /talent/competencies/{id}
     */
    public function getCompetencyId($id)
    {
        try {
            $competency = $this->competency->find($id);

            if (!$competency) {
                return $this->failNotFound('Competency not found');
            }

            return $this->respond(['data' => $competency], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching competency');
        }
    }

    /**
     * Get user's competencies
     * GET /talent/my-competencies
     */
    public function getMyCompetencies()
    {
        try {
            $userId = auth()->user()->id;

            $competencies = $this->employeeCompetency
                ->where('employee_id', $userId)
                ->findAll();

            // Enhance with competency details
            foreach ($competencies as &$comp) {
                $compDetails = $this->competency->find($comp['competency_id']);
                $comp['competency'] = $compDetails;
            }

            return $this->respond(['data' => $competencies], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching competencies');
        }
    }

    /**
     * Update user's competency
     * PUT /talent/my-competencies/{id}
     */
    public function updateMyCompetency($id)
    {
        try {
            $userId = auth()->user()->id;
            $comp = $this->employeeCompetency->find($id);

            if (!$comp || $comp['employee_id'] != $userId) {
                return $this->failForbidden('Competency not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->employeeCompetency->update($id, $data)) {
                return $this->respond(['message' => 'Competency updated'], 200);
            }

            return $this->fail($this->employeeCompetency->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating competency');
        }
    }

    /**
     * Get all Individual Development Plans (multi-year)
     * GET /talent/idp/all
     */
    public function getIdpAll()
    {
        try {
            $userId = auth()->user()->id;

            $idps = $this->idp
                ->where('employee_id', $userId)
                ->orderBy('plan_year', 'DESC')
                ->findAll();

            // Add mentor details for each IDP
            foreach ($idps as &$idp) {
                if (!empty($idp['mentor_assigned_id'])) {
                    $mentor = $this->employee->find($idp['mentor_assigned_id']);
                    $idp['mentor'] = $mentor ? [
                        'id' => $mentor['id'],
                        'name' => $mentor['first_name'] . ' ' . $mentor['last_name']
                    ] : null;
                }
            }

            return $this->respond(['data' => $idps], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching IDPs');
        }
    }

    /**
     * Get Individual Development Plan
     * GET /talent/idp
     */
    public function getIdp()
    {
        try {
            $userId = auth()->user()->id;
            $currentYear = date('Y');

            $idp = $this->idp
                ->where('employee_id', $userId)
                ->where('plan_year', $currentYear)
                ->first();

            if (!$idp) {
                return $this->failNotFound('IDP not found for current year');
            }

            // Add mentor details
            if ($idp['mentor_assigned_id']) {
                $mentor = $this->employee->find($idp['mentor_assigned_id']);
                $idp['mentor'] = $mentor ? [
                    'id' => $mentor['id'],
                    'name' => $mentor['first_name'] . ' ' . $mentor['last_name']
                ] : null;
            }

            return $this->respond(['data' => $idp], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching IDP');
        }
    }

    /**
     * Create Individual Development Plan
     * POST /talent/idp
     */
    public function createIdp()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;
            $data['plan_year'] = $data['plan_year'] ?? date('Y');

            if ($this->idp->insert($data)) {
                return $this->respond(['message' => 'IDP created'], 201);
            }

            return $this->fail($this->idp->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating IDP');
        }
    }

    /**
     * Update Individual Development Plan
     * PUT /talent/idp/{id}
     */
    public function updateIdp($id)
    {
        try {
            $userId = auth()->user()->id;
            $idp = $this->idp->find($id);

            if (!$idp || $idp['employee_id'] != $userId) {
                return $this->failForbidden('IDP not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->idp->update($id, $data)) {
                return $this->respond(['message' => 'IDP updated'], 200);
            }

            return $this->fail($this->idp->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating IDP');
        }
    }

    // ════════════════════════════════════════════
    // Succession Plans CRUD
    // ════════════════════════════════════════════

    public function getSuccessionPlans()
    {
        try {
            $userId = auth()->user()->id;
            $plans = $this->successionPlan
                ->where('employee_id', $userId)
                ->orderBy('plan_type', 'ASC')
                ->orderBy('readiness_percentage', 'DESC')
                ->findAll();

            return $this->respond(['data' => $plans], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching succession plans');
        }
    }

    public function getSuccessionPlan($id)
    {
        try {
            $plan = $this->successionPlan->find($id);
            if (!$plan) {
                return $this->failNotFound('Succession plan not found');
            }
            return $this->respond(['data' => $plan], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching succession plan');
        }
    }

    public function createSuccessionPlan()
    {
        try {
            $data = $this->request->getJSON(true);
            $userId = auth()->user()->id;
            if (empty($data['employee_id'])) {
                $data['employee_id'] = $userId;
            }

            if ($this->successionPlan->insert($data)) {
                return $this->respond(['message' => 'Succession plan created', 'id' => $this->successionPlan->getInsertID()], 201);
            }

            return $this->respond(['status' => 422, 'messages' => ['error' => implode(', ', $this->successionPlan->errors())]], 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating succession plan: ' . $e->getMessage());
        }
    }

    public function updateSuccessionPlan($id)
    {
        try {
            $plan = $this->successionPlan->find($id);
            if (!$plan) {
                return $this->failNotFound('Succession plan not found');
            }

            $data = $this->request->getJSON(true);

            if ($this->successionPlan->update($id, $data)) {
                return $this->respond(['message' => 'Succession plan updated'], 200);
            }

            return $this->respond(['status' => 422, 'messages' => ['error' => implode(', ', $this->successionPlan->errors())]], 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating succession plan');
        }
    }

    public function deleteSuccessionPlan($id)
    {
        try {
            $plan = $this->successionPlan->find($id);
            if (!$plan) {
                return $this->failNotFound('Succession plan not found');
            }

            if ($this->successionPlan->delete($id)) {
                return $this->respond(['message' => 'Succession plan deleted'], 200);
            }

            return $this->failServerError('Error deleting succession plan');
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting succession plan');
        }
    }

    // ════════════════════════════════════════════
    // Career Paths CRUD
    // ════════════════════════════════════════════

    public function getCareerPaths()
    {
        try {
            $userId = auth()->user()->id;
            $paths = $this->careerPath
                ->where('employee_id', $userId)
                ->orderBy('sort_order', 'ASC')
                ->findAll();

            return $this->respond(['data' => $paths], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching career paths');
        }
    }

    public function createCareerPath()
    {
        try {
            $data = $this->request->getJSON(true);
            $userId = auth()->user()->id;
            $data['employee_id'] = $userId;

            if ($this->careerPath->insert($data)) {
                return $this->respond(['message' => 'Career path added', 'id' => $this->careerPath->getInsertID()], 201);
            }

            return $this->respond(['status' => 422, 'messages' => ['error' => implode(', ', $this->careerPath->errors())]], 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating career path: ' . $e->getMessage());
        }
    }

    public function updateCareerPath($id)
    {
        try {
            $userId = auth()->user()->id;
            $path = $this->careerPath->find($id);
            if (!$path || (int)$path['employee_id'] !== (int)$userId) {
                return $this->failForbidden('Career path not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->careerPath->update($id, $data)) {
                return $this->respond(['message' => 'Career path updated'], 200);
            }

            return $this->respond(['status' => 422, 'messages' => ['error' => implode(', ', $this->careerPath->errors())]], 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating career path');
        }
    }

    public function deleteCareerPath($id)
    {
        try {
            $userId = auth()->user()->id;
            $path = $this->careerPath->find($id);
            if (!$path || (int)$path['employee_id'] !== (int)$userId) {
                return $this->failForbidden('Career path not found or unauthorized');
            }

            if ($this->careerPath->delete($id)) {
                return $this->respond(['message' => 'Career path deleted'], 200);
            }

            return $this->failServerError('Error deleting career path');
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting career path');
        }
    }
}
