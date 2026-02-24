<?php

namespace App\Controllers;

use App\Models\Skill;
use App\Models\EmployeeSkill;
use App\Models\Competency;
use App\Models\EmployeeCompetency;
use App\Models\Certification;
use App\Models\IndividualDevelopmentPlan;
use App\Models\AwardRecognition;
use App\Models\Employee;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Talent extends Controller
{
    use ResponseTrait;

    protected $skill;
    protected $employeeSkill;
    protected $competency;
    protected $employeeCompetency;
    protected $certification;
    protected $idp;
    protected $award;
    protected $employee;

    public function __construct()
    {
        $this->skill = new Skill();
        $this->employeeSkill = new EmployeeSkill();
        $this->competency = new Competency();
        $this->employeeCompetency = new EmployeeCompetency();
        $this->certification = new Certification();
        $this->idp = new IndividualDevelopmentPlan();
        $this->award = new AwardRecognition();
        $this->employee = new Employee();
    }

    /**
     * Get all skills (catalog)
     * GET /talent/skills
     */
    public function getSkills()
    {
        try {
            $skills = $this->skill
                ->orderBy('name', 'ASC')
                ->findAll();

            return $this->respond(['data' => $skills], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching skills');
        }
    }

    /**
     * Get specific skill
     * GET /talent/skills/{id}
     */
    public function getSkillId($id)
    {
        try {
            $skill = $this->skill->find($id);

            if (!$skill) {
                return $this->failNotFound('Skill not found');
            }

            return $this->respond(['data' => $skill], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching skill');
        }
    }

    /**
     * Add skill to user's profile
     * POST /talent/skills
     */
    public function addSkill()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->employeeSkill->insert($data)) {
                return $this->respond(['message' => 'Skill added'], 201);
            }

            return $this->fail($this->employeeSkill->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding skill');
        }
    }

    /**
     * Update user's skill
     * PUT /talent/skills/{id}
     */
    public function updateSkill($id)
    {
        try {
            $userId = auth()->user()->id;
            $skill = $this->employeeSkill->find($id);

            if (!$skill || $skill['employee_id'] != $userId) {
                return $this->failForbidden('Skill not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->employeeSkill->update($id, $data)) {
                return $this->respond(['message' => 'Skill updated'], 200);
            }

            return $this->fail($this->employeeSkill->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating skill');
        }
    }

    /**
     * Get competencies (catalog)
     * GET /talent/competencies
     */
    public function getCompetencies()
    {
        try {
            $competencies = $this->competency
                ->orderBy('name', 'ASC')
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
     * Get user's certifications
     * GET /talent/certifications
     */
    public function getCertifications()
    {
        try {
            $userId = auth()->user()->id;

            $certifications = $this->certification
                ->where('employee_id', $userId)
                ->orderBy('issue_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $certifications], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching certifications');
        }
    }

    /**
     * Get specific certification
     * GET /talent/certifications/{id}
     */
    public function getCertificationId($id)
    {
        try {
            $userId = auth()->user()->id;
            $cert = $this->certification->find($id);

            if (!$cert || $cert['employee_id'] != $userId) {
                return $this->failForbidden('Certification not found or unauthorized');
            }

            return $this->respond(['data' => $cert], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching certification');
        }
    }

    /**
     * Add certification
     * POST /talent/certifications
     */
    public function addCertification()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;

            if ($this->certification->insert($data)) {
                return $this->respond(['message' => 'Certification added'], 201);
            }

            return $this->fail($this->certification->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error adding certification');
        }
    }

    /**
     * Update certification
     * PUT /talent/certifications/{id}
     */
    public function updateCertification($id)
    {
        try {
            $userId = auth()->user()->id;
            $cert = $this->certification->find($id);

            if (!$cert || $cert['employee_id'] != $userId) {
                return $this->failForbidden('Certification not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->certification->update($id, $data)) {
                return $this->respond(['message' => 'Certification updated'], 200);
            }

            return $this->fail($this->certification->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating certification');
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

    /**
     * Get awards for user
     * GET /talent/awards
     */
    public function getAwards()
    {
        try {
            $userId = auth()->user()->id;

            $awards = $this->award
                ->where('employee_id', $userId)
                ->orderBy('award_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $awards], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching awards');
        }
    }

    /**
     * Get specific award
     * GET /talent/awards/{id}
     */
    public function getAwardId($id)
    {
        try {
            $userId = auth()->user()->id;
            $award = $this->award->find($id);

            if (!$award || $award['employee_id'] != $userId) {
                return $this->failForbidden('Award not found or unauthorized');
            }

            return $this->respond(['data' => $award], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching award');
        }
    }
}
