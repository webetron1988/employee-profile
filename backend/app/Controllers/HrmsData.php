<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\HrmsEmployee;
use App\Models\HrmsEducation;
use App\Models\HrmsWorkExperience;
use App\Models\HrmsSkill;
use App\Models\HrmsCertification;
use App\Models\HrmsAward;
use App\Models\HrmsProject;
use App\Models\EpEducation;
use App\Models\EpWorkExperience;
use App\Models\EpSkill;
use App\Models\EpCertification;
use App\Models\EpAward;
use App\Models\EpProject;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

/**
 * HrmsData Controller
 *
 * Provides read-only HRMS endpoints and dual-source CRUD endpoints.
 * HRMS DB is read-only. All writes go to EP DB (ep_* tables).
 * Each record is tagged with `_source: 'hrms'` or `_source: 'ep'`.
 */
class HrmsData extends Controller
{
    use ResponseTrait;

    protected $userModel;
    protected $hrmsEmployee;

    // HRMS read-only models
    protected $hrmsEducation;
    protected $hrmsWorkExperience;
    protected $hrmsSkill;
    protected $hrmsCertification;
    protected $hrmsAward;
    protected $hrmsProject;

    // EP writable models
    protected $epEducation;
    protected $epWorkExperience;
    protected $epSkill;
    protected $epCertification;
    protected $epAward;
    protected $epProject;

    /**
     * HRMS DB connection (read-only)
     */
    protected $hrmsDb;

    /**
     * Dual-source entity configuration.
     * Maps a slug to its HRMS model, EP model, HRMS primary key field name.
     */
    protected array $entityMap;

    public function __construct()
    {
        $this->userModel         = new User();
        $this->hrmsEmployee      = new HrmsEmployee();

        $this->hrmsEducation     = new HrmsEducation();
        $this->hrmsWorkExperience = new HrmsWorkExperience();
        $this->hrmsSkill         = new HrmsSkill();
        $this->hrmsCertification = new HrmsCertification();
        $this->hrmsAward         = new HrmsAward();
        $this->hrmsProject       = new HrmsProject();

        $this->epEducation       = new EpEducation();
        $this->epWorkExperience  = new EpWorkExperience();
        $this->epSkill           = new EpSkill();
        $this->epCertification   = new EpCertification();
        $this->epAward           = new EpAward();
        $this->epProject         = new EpProject();

        $this->hrmsDb = \Config\Database::connect('hrms');

        $this->entityMap = [
            'education' => [
                'hrmsModel'  => $this->hrmsEducation,
                'epModel'    => $this->epEducation,
                'hrmsPk'     => 'education_id',
                'label'      => 'Education',
            ],
            'work-experience' => [
                'hrmsModel'  => $this->hrmsWorkExperience,
                'epModel'    => $this->epWorkExperience,
                'hrmsPk'     => 'experience_id',
                'label'      => 'Work Experience',
            ],
            'skills' => [
                'hrmsModel'  => $this->hrmsSkill,
                'epModel'    => $this->epSkill,
                'hrmsPk'     => 'emp_skill_id',
                'label'      => 'Skill',
            ],
            'certifications' => [
                'hrmsModel'  => $this->hrmsCertification,
                'epModel'    => $this->epCertification,
                'hrmsPk'     => 'certification_id',
                'label'      => 'Certification',
            ],
            'awards' => [
                'hrmsModel'  => $this->hrmsAward,
                'epModel'    => $this->epAward,
                'hrmsPk'     => 'award_id',
                'label'      => 'Award',
            ],
            'projects' => [
                'hrmsModel'  => $this->hrmsProject,
                'epModel'    => $this->epProject,
                'hrmsPk'     => 'project_id',
                'label'      => 'Project',
            ],
        ];
    }

    // =====================================================================
    //  Auth helpers
    // =====================================================================

    /**
     * Get the authenticated EP user ID (users.id).
     */
    private function getUserId(): int
    {
        return auth()->user()->id;
    }

    /**
     * Get the HRMS employee ID for the current user.
     * Reads from the users table `hrms_employee_id` column.
     */
    private function getHrmsEmpId(): ?int
    {
        // Try header first (set by PermissionMiddleware from JWT claims)
        $headerVal = $this->request->getHeaderLine('X-Auth-Hrms-Emp-Id');
        if (!empty($headerVal) && $headerVal !== '0') {
            return (int) $headerVal;
        }
        // Fallback: look up from users table
        $userId = $this->getUserId();
        $user = $this->userModel->find($userId);
        if (!$user || empty($user['hrms_employee_id'])) {
            return null;
        }
        return (int) $user['hrms_employee_id'];
    }

    // =====================================================================
    //  READ-ONLY HRMS ENDPOINTS
    // =====================================================================

    /**
     * GET /hrms-data/full-profile
     * Full employee profile from HRMS (employee + positions + departments + managers etc.)
     */
    public function fullProfile()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            $row = $this->hrmsDb->table('employee')
                ->select('
                    employee.empID, employee.uid, employee.salutation,
                    employee.name, employee.middle_name, employee.last_name,
                    employee.contact, employee.email, employee.alternate_contact,
                    employee.gender, employee.dob, employee.marital_status,
                    employee.blood_group, employee.religion,
                    employee.nationality, employee.overall_experience,
                    employee.address, employee.temporary_address, employee.pin_code,
                    employee.passport_no, employee.national_identity_no,
                    employee.date_of_joining, employee.notice_period,
                    employee.working_status, employee.profile, employee.profile_storage, employee.client_id, employee.notes,
                    CONCAT(COALESCE(employee.name,"")," ",COALESCE(employee.middle_name,"")," ",COALESCE(employee.last_name,"")) AS full_name,
                    positions.positionTitle, positions.positionCode,
                    positions.work_type, positions.contract_type,
                    ctm.ctrt_contract_title AS contract_type_name,
                    ctm.ctrt_contract_code AS contract_type_code,
                    ctm.ctrt_attribute_2 AS contract_hours,
                    positions.probation_period, positions.probation_end_date,
                    positions.noticePeriod, positions.noticePeriodType, positions.grade_id,
                    grades.grade_name,
                    org.orgm_legal_reg_name AS organization_name,
                    div.divn_division_name AS division_name,
                    departments_master.dept_department_name,
                    unit_master.unit_unit_name AS unit_name,
                    section_master.secn_section_name AS section_name,
                    roles_master.role_role_name AS role_name,
                    employment_types.empTypeName,
                    CONCAT(COALESCE(reporting.name,"")," ",COALESCE(reporting.middle_name,"")," ",COALESCE(reporting.last_name,"")) AS reporting_to,
                    reporting.email AS reporting_manager_email,
                    reporting.profile AS reporting_manager_profile,
                    reporting.empID AS reporting_manager_id,
                    reporting_pos.positionTitle AS reporting_manager_designation,
                    CONCAT(COALESCE(matrix.name,"")," ",COALESCE(matrix.middle_name,"")," ",COALESCE(matrix.last_name,"")) AS matrix_manager,
                    matrix.email AS matrix_manager_email,
                    matrix.empID AS matrix_manager_id,
                    matrix_pos.positionTitle AS matrix_manager_designation,
                    CONCAT(COALESCE(reviewer.name,"")," ",COALESCE(reviewer.middle_name,"")," ",COALESCE(reviewer.last_name,"")) AS reviewer_manager,
                    reviewer.email AS reviewer_manager_email,
                    reviewer.empID AS reviewer_manager_id,
                    reviewer_pos.positionTitle AS reviewer_manager_designation,
                    countries_master.cnty_country_name AS country_name,
                    states_master.stat_state_name AS state_name,
                    cities_master.city_city_name AS city_name,
                    cnt_nat.cnty_nationality AS nationality_name
                ')
                ->join('positions', 'positions.positionID = employee.position_id', 'left')
                ->join('organization_master as org', 'org.orgm_id = positions.organization_id', 'left')
                ->join('divisions_master as div', 'div.divn_division_id = positions.division_id', 'left')
                ->join('departments_master', 'departments_master.dept_department_id = positions.department_id', 'left')
                ->join('unit_master', 'unit_master.unit_unit_id = positions.unit_id', 'left')
                ->join('section_master', 'section_master.secn_section_id = positions.section_id', 'left')
                ->join('roles_master', 'roles_master.role_role_id = employee.role_id', 'left')
                ->join('grades', 'grades.grade_id = positions.grade_id', 'left')
                ->join('employment_types', 'employment_types.empTypeID = employee.employment_type_id', 'left')
                ->join('employee as reporting', 'reporting.empID = positions.reporting_manager_id', 'left')
                ->join('positions as reporting_pos', 'reporting_pos.positionID = reporting.position_id', 'left')
                ->join('employee as matrix', 'matrix.empID = positions.metrix_manager_id', 'left')
                ->join('positions as matrix_pos', 'matrix_pos.positionID = matrix.position_id', 'left')
                ->join('employee as reviewer', 'reviewer.empID = positions.reviewer_manager_id', 'left')
                ->join('positions as reviewer_pos', 'reviewer_pos.positionID = reviewer.position_id', 'left')
                ->join('contract_types_master as ctm', 'ctm.ctrt_contract_id = SUBSTRING_INDEX(positions.contract_type, ",", 1)', 'left')
                ->join('countries_master', 'countries_master.cnty_country_id = employee.country_id', 'left')
                ->join('states_master', 'states_master.stat_state_id = employee.state_id', 'left')
                ->join('cities_master', 'cities_master.city_city_id = employee.city_id', 'left')
                ->join('countries_master as cnt_nat', 'cnt_nat.cnty_country_code = employee.nationality', 'left')
                ->where('employee.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();

            if (!$row) {
                return $this->failNotFound('Employee not found in HRMS');
            }

            return $this->respond(['data' => $row], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::fullProfile error: ' . $e->getMessage());
            return $this->failServerError('Error fetching full profile from HRMS');
        }
    }

    /**
     * GET /hrms-data/job-family
     * Job family hierarchy chain for the employee.
     */
    public function jobFamily()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            // Step 1: Get the leaf job family ID from employee's position
            $row = $this->hrmsDb->table('employee e')
                ->select('jd.jobd_job_family')
                ->join('positions p', 'p.positionID = e.position_id', 'left')
                ->join('job_details jd', 'jd.jobd_job_id = p.jobID', 'left')
                ->where('e.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();

            if (empty($row['jobd_job_family'])) {
                return $this->respond(['data' => ['chain' => [], 'breadcrumb' => '']], 200);
            }

            // Step 2: Walk up the parent chain from the leaf
            $chain = [];
            $currentID = (int) $row['jobd_job_family'];
            $maxDepth = 10;

            while ($currentID > 0 && $maxDepth-- > 0) {
                $family = $this->hrmsDb->table('jobfamily')
                    ->select('jobfamilyID, jobfamilyName, code, parentID')
                    ->where('jobfamilyID', $currentID)
                    ->where('status', 'active')
                    ->get()
                    ->getRowArray();

                if (!$family) break;

                $chain[] = [
                    'id'   => (int) $family['jobfamilyID'],
                    'name' => $family['jobfamilyName'],
                    'code' => $family['code'],
                ];
                $currentID = (int) $family['parentID'];
            }

            // Step 3: Reverse so root is first, leaf is last
            $chain = array_reverse($chain);
            $names = array_column($chain, 'name');
            $breadcrumb = implode(' > ', $names);

            return $this->respond([
                'data' => [
                    'chain'      => $chain,
                    'breadcrumb' => $breadcrumb,
                ],
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::jobFamily error: ' . $e->getMessage());
            return $this->failServerError('Error fetching job family from HRMS');
        }
    }

    /**
     * GET /hrms-data/direct-reports
     * Employees reporting to the current user.
     */
    public function directReports()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            $reports = $this->hrmsDb->table('employee e')
                ->select('e.empID, CONCAT(COALESCE(e.name,"")," ",COALESCE(e.last_name,"")) as name, e.email, e.profile, p.positionTitle')
                ->join('positions p', 'p.positionID = e.position_id', 'left')
                ->where('p.reporting_manager_id', $hrmsEmpId)
                ->get()
                ->getResultArray();

            return $this->respond([
                'data' => [
                    'direct_reports' => $reports,
                    'count'          => count($reports),
                ],
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::directReports error: ' . $e->getMessage());
            return $this->failServerError('Error fetching direct reports from HRMS');
        }
    }

    /**
     * GET /hrms-data/je-score
     * Job Evaluation score data for the employee.
     */
    public function jeScore()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            $row = $this->hrmsDb->table('employee e')
                ->select('
                    g.grade_id, g.grade_name,
                    jd.jobd_je_methodology_id, jd.jobd_je_total_points,
                    jd.jobd_je_min, jd.jobd_je_mid, jd.jobd_je_max,
                    jd.jobd_je_band, jd.jobd_je_profile, jd.jobd_je_ref_level,
                    jd.jobd_jeline_factors,
                    jem.je_methodology_name
                ')
                ->join('positions p', 'p.positionID = e.position_id', 'left')
                ->join('grades g', 'g.grade_id = p.grade_id', 'left')
                ->join('job_details jd', 'jd.jobd_job_id = p.jobID', 'left')
                ->join('je_methodology jem', 'jem.je_methodology_id = jd.jobd_je_methodology_id', 'left')
                ->where('e.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();

            if (!$row) {
                return $this->failNotFound('JE score data not found');
            }

            // Parse JE line factors JSON
            $factors = [];
            if (!empty($row['jobd_jeline_factors'])) {
                $raw = json_decode($row['jobd_jeline_factors'], true);
                if (is_array($raw)) {
                    foreach ($raw as $factorName => $factorData) {
                        $totalPts = isset($factorData['total_points']) ? (int) $factorData['total_points'] : 0;
                        $subFactors = [];
                        if (!empty($factorData['subfactor']) && !empty($factorData['je_line'])) {
                            foreach ($factorData['subfactor'] as $i => $sf) {
                                $subFactors[] = [
                                    'name'   => $sf,
                                    'points' => isset($factorData['je_line'][$i]) ? (int) $factorData['je_line'][$i] : 0,
                                ];
                            }
                        }
                        $factors[] = [
                            'name'         => $factorName,
                            'total_points' => $totalPts,
                            'sub_factors'  => $subFactors,
                        ];
                    }
                }
            }

            // Look up grade band name from je_score_mapping
            $gradeBand = $row['jobd_je_band'] ?: '';
            if (!empty($row['jobd_je_methodology_id']) && !empty($row['jobd_je_total_points'])) {
                $mapping = $this->hrmsDb->table('je_score_mapping_with_grade_ban')
                    ->select('je_mapping_band, je_mapping_grade, je_mapping_profile, je_mapping_reference_level')
                    ->where('je_mapping_methodology', $row['jobd_je_methodology_id'])
                    ->where('je_mapping_score_min <=', (int) $row['jobd_je_total_points'])
                    ->where('je_mapping_max >=', (int) $row['jobd_je_total_points'])
                    ->where('je_mapping_is_deleted', 0)
                    ->get()
                    ->getRowArray();
                if ($mapping) {
                    $gradeBand = 'Band ' . $mapping['je_mapping_band'];
                }
            }

            return $this->respond([
                'data' => [
                    'grade_name'      => $row['grade_name'] ?: '',
                    'grade_id'        => $row['grade_id'] ?: null,
                    'je_methodology'  => $row['je_methodology_name'] ?: '',
                    'je_total_points' => (int) ($row['jobd_je_total_points'] ?: 0),
                    'je_min'          => (int) ($row['jobd_je_min'] ?: 0),
                    'je_mid'          => (int) ($row['jobd_je_mid'] ?: 0),
                    'je_max'          => (int) ($row['jobd_je_max'] ?: 0),
                    'je_band'         => $gradeBand,
                    'je_profile'      => $row['jobd_je_profile'] ?: '',
                    'je_ref_level'    => $row['jobd_je_ref_level'] ?: '',
                    'je_factors'      => $factors,
                ],
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::jeScore error: ' . $e->getMessage());
            return $this->failServerError('Error fetching JE score from HRMS');
        }
    }

    /**
     * GET /hrms-data/work-classification
     * Work type & classification data for the employee.
     */
    public function workClassification()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            $row = $this->hrmsDb->table('employee')
                ->select('
                    jd.jobd_job_desc_id, jd.jobd_workforce_type, jd.jobd_workforce_cluster,
                    jd.jobd_job_classification1, jd.jobd_job_classification2, jd.jobd_job_classification3,
                    jd.jobd_travel_frequency, jd.jobd_compensation, jd.jobd_working_pattern,
                    jd.jobd_work_contract_arrangement, jd.jobd_work_type, jd.jobd_working_location,
                    jd.jobd_od_classification1, jd.jobd_od_classification2, jd.jobd_od_classification3,
                    jd.jobd_od_classification4, jd.jobd_od_classification5,
                    roles_master.role_role_name
                ')
                ->join('positions', 'positions.positionID = employee.position_id', 'left')
                ->join('job_details as jd', 'jd.jobd_job_id = positions.jobID', 'left')
                ->join('roles_master', 'roles_master.role_role_id = employee.role_id', 'left')
                ->where('employee.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();

            if (!$row) {
                return $this->failNotFound('Work classification data not found');
            }

            // Resolve workforce type (comma-separated IDs -> contract_types_master names)
            $workforceTypeNames = [];
            if (!empty($row['jobd_workforce_type'])) {
                $ids = explode(',', $row['jobd_workforce_type']);
                foreach ($ids as $id) {
                    $ct = $this->hrmsDb->table('contract_types_master')
                        ->select('ctrt_contract_title')
                        ->where('ctrt_contract_id', trim($id))
                        ->get()
                        ->getRowArray();
                    if ($ct) $workforceTypeNames[] = $ct['ctrt_contract_title'];
                }
            }
            $row['workforce_type_names'] = $workforceTypeNames;

            // Resolve contract arrangement names
            $arrangementNames = [];
            if (!empty($row['jobd_work_contract_arrangement'])) {
                $ids = explode(',', $row['jobd_work_contract_arrangement']);
                foreach ($ids as $id) {
                    $wca = $this->hrmsDb->table('job_work_contract_arrangements')
                        ->select('wca_name')
                        ->where('wca_id', trim($id))
                        ->get()
                        ->getRowArray();
                    if ($wca) $arrangementNames[] = $wca['wca_name'];
                }
            }
            $row['contract_arrangements'] = $arrangementNames;

            // Resolve workforce cluster label
            $clusterMap = [
                'entry' => 'Entry-Level', 'associate' => 'Associate',
                'mid' => 'Mid-Level Management', 'senior' => 'Senior Management',
                'executive' => 'Executive Leadership', 'top' => 'Top Leadership',
                'board' => 'Board/Advisory Level',
            ];
            $row['workforce_cluster_label'] = $clusterMap[$row['jobd_workforce_cluster'] ?? ''] ?? ($row['jobd_workforce_cluster'] ?: '–');

            // Classification labels
            $class1Map = ['core' => 'Core', 'non_core' => 'Non Core'];
            $class2Map = ['front_office' => 'Front Office', 'mid_office' => 'Mid Office', 'back_office' => 'Back Office', 'customer_facing' => 'Customer Facing'];
            $class3Map = ['revenue' => 'Revenue Generating', 'non_revenue' => 'Non Revenue Generating'];
            $row['classification1_label'] = $class1Map[$row['jobd_job_classification1'] ?? ''] ?? ($row['jobd_job_classification1'] ?: '–');
            $row['classification2_label'] = $class2Map[$row['jobd_job_classification2'] ?? ''] ?? ($row['jobd_job_classification2'] ?: '–');
            $row['classification3_label'] = $class3Map[$row['jobd_job_classification3'] ?? ''] ?? ($row['jobd_job_classification3'] ?: '–');

            // Travel frequency
            $travelMap = ['low' => '0-10%', 'medium' => '10-20%', 'high' => '20-50%'];
            $row['travel_display'] = $travelMap[$row['jobd_travel_frequency'] ?? ''] ?? ($row['jobd_travel_frequency'] ?: '–');
            $row['travel_label'] = $row['jobd_travel_frequency'] ? ucfirst($row['jobd_travel_frequency']) : '–';

            // Compensation
            $compMap = ['hourly_rated' => 'Hourly Rated', 'daily_rated' => 'Daily Rated', 'monthly_rated' => 'Monthly Rated'];
            $row['compensation_display'] = $compMap[$row['jobd_compensation'] ?? ''] ?? ($row['jobd_compensation'] ?: '–');

            // Working pattern
            $patternMap = ['day' => 'Day Shift', 'night' => 'Night Shift', 'rotating' => 'Rotating Shift'];
            $row['working_pattern_display'] = $patternMap[$row['jobd_working_pattern'] ?? ''] ?? ($row['jobd_working_pattern'] ?: '–');

            // Currency symbol from employee's organization
            $currSymbol = '$';
            $orgRow = $this->hrmsDb->table('employee e2')
                ->select('om.orgm_currency_code, cm.curr_currency_symbol')
                ->join('positions p2', 'p2.positionID = e2.position_id', 'left')
                ->join('organization_master om', 'om.orgm_id = p2.organization_id', 'left')
                ->join('currencies_master cm', 'cm.curr_currency_code = om.orgm_currency_code', 'left')
                ->where('e2.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();
            if ($orgRow && !empty($orgRow['curr_currency_symbol'])) {
                $currSymbol = $orgRow['curr_currency_symbol'];
            }
            $row['currency_symbol'] = $currSymbol;

            return $this->respond(['data' => $row], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::workClassification error: ' . $e->getMessage());
            return $this->failServerError('Error fetching work classification from HRMS');
        }
    }

    /**
     * GET /hrms-data/org-structure
     * Organization structure for the employee.
     */
    public function orgStructure()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            $row = $this->hrmsDb->table('employee e')
                ->select('
                    om.orgm_short_name AS org_name,
                    om.orgm_full_name AS org_full_name,
                    om.orgm_legal_reg_name AS legal_entity,
                    dm.divn_division_name AS division_name,
                    dept.dept_department_name AS department_name,
                    um.unit_unit_name AS unit_name,
                    sm.secn_section_name AS section_name,
                    jd.jobd_cost_code,
                    jd.jobd_business_unit1, jd.jobd_business_unit2, jd.jobd_business_unit3
                ')
                ->join('positions p', 'p.positionID = e.position_id', 'left')
                ->join('job_details jd', 'jd.jobd_job_id = p.jobID', 'left')
                ->join('organization_master om', 'om.orgm_id = p.organization_id', 'left')
                ->join('divisions_master dm', 'dm.divn_division_id = p.division_id', 'left')
                ->join('departments_master dept', 'dept.dept_department_id = p.department_id', 'left')
                ->join('unit_master um', 'um.unit_unit_id = p.unit_id', 'left')
                ->join('section_master sm', 'sm.secn_section_id = p.section_id', 'left')
                ->where('e.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();

            if (!$row) {
                return $this->failNotFound('Organization structure data not found');
            }

            // Business unit fallback
            $row['business_unit'] = $row['jobd_business_unit1'] ?: ($row['org_name'] ?: '–');
            // Team: prefer unit, fallback to section
            $row['team'] = $row['unit_name'] ?: ($row['section_name'] ?: '–');

            return $this->respond(['data' => $row], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::orgStructure error: ' . $e->getMessage());
            return $this->failServerError('Error fetching org structure from HRMS');
        }
    }

    /**
     * GET /hrms-data/competencies
     * Competencies from CPF linked to the employee's job.
     */
    public function competencies()
    {
        try {
            $hrmsEmpId = $this->getHrmsEmpId();
            if (!$hrmsEmpId) {
                return $this->failNotFound('HRMS employee ID not found for current user');
            }

            // Step 1: Get employee's position -> job_details
            $job = $this->hrmsDb->table('employee e')
                ->select('jd.jobd_job_id, jd.jobd_competencies, jd.jobd_comp_type, p.positionTitle as job_title')
                ->join('positions p', 'p.positionID = e.position_id', 'left')
                ->join('job_details jd', 'jd.jobd_job_id = p.jobID', 'left')
                ->where('e.empID', $hrmsEmpId)
                ->get()
                ->getRowArray();

            if (empty($job) || empty($job['jobd_competencies'])) {
                return $this->respond([
                    'data' => [
                        'clusters'           => [],
                        'proficiency_levels'  => [],
                        'job_title'           => $job['job_title'] ?? '',
                    ],
                ], 200);
            }

            $planIds = array_filter(array_map('intval', explode(',', $job['jobd_competencies'])));
            if (empty($planIds)) {
                return $this->respond([
                    'data' => [
                        'clusters'           => [],
                        'proficiency_levels'  => [],
                        'job_title'           => $job['job_title'] ?? '',
                    ],
                ], 200);
            }

            // Step 2: Get individual competencies from active plans
            $competencies = $this->hrmsDb->table('capability_and_performance_factor_content cpfc')
                ->select('
                    cpfc.cpf_content_id as comp_id,
                    cpfc.cpf_level_title as comp_name,
                    cpfc.cpf_id as plan_id,
                    cpfm.cpf_plan_name as plan_name,
                    cpfm.cpf_framework_template_id as framework_id,
                    cpfc.cpf_tab_category_id as cluster_id,
                    mwtc.tab_title as cluster_name,
                    mwtc.tab_position as cluster_position,
                    jcd.prof_id as selected_prof_id,
                    plc.prof_level_label as prof_label,
                    plc.prof_lvl_color as prof_color,
                    jcd.is_have_skill as is_must_have
                ')
                ->join('capability_and_performance_factor_master cpfm', 'cpfm.cpf_id = cpfc.cpf_id', 'inner')
                ->join('module_wise_tab_category mwtc', 'mwtc.tab_id = cpfc.cpf_tab_category_id', 'left')
                ->join('job_comp_data jcd', 'jcd.ind_com_id = cpfc.cpf_content_id AND jcd.jobd_job_id = ' . (int) $job['jobd_job_id'], 'left')
                ->join('prof_level_config plc', 'plc.prof_lvl_id = jcd.prof_id', 'left')
                ->whereIn('cpfc.cpf_id', $planIds)
                ->where('cpfc.cpf_framework_level_name', 'Individual Competency')
                ->where("(cpfm.cpf_delete_status IS NULL OR cpfm.cpf_delete_status != 'T')", null, false)
                ->orderBy('mwtc.tab_position', 'ASC')
                ->orderBy('cpfc.cpf_level_title', 'ASC')
                ->get()
                ->getResultArray();

            // Step 3: Get proficiency levels for the frameworks used
            $frameworkIds = array_unique(array_filter(array_column($competencies, 'framework_id')));
            $profLevels = [];
            if (!empty($frameworkIds)) {
                $levels = $this->hrmsDb->table('prof_level_config')
                    ->select('prof_lvl_id, prof_lvl_framework_id, prof_level_label, prof_lvl_color')
                    ->whereIn('prof_lvl_framework_id', $frameworkIds)
                    ->where('prof_lvl_status', 1)
                    ->orderBy('prof_lvl_id', 'ASC')
                    ->get()
                    ->getResultArray();
                foreach ($levels as $lv) {
                    $profLevels[$lv['prof_lvl_framework_id']][] = $lv;
                }
            }

            // Step 4: Group by cluster
            $clusters = [];
            foreach ($competencies as $c) {
                $clusterName = $c['cluster_name'] ? trim(preg_replace('/[^\x20-\x7E]/', '', $c['cluster_name'])) : 'Other';
                if (!isset($clusters[$clusterName])) {
                    $clusters[$clusterName] = [
                        'cluster_id'       => $c['cluster_id'],
                        'cluster_name'     => $clusterName,
                        'cluster_position' => $c['cluster_position'],
                        'competencies'     => [],
                    ];
                }
                $clusters[$clusterName]['competencies'][] = [
                    'comp_id'          => $c['comp_id'],
                    'comp_name'        => $c['comp_name'],
                    'plan_id'          => $c['plan_id'],
                    'plan_name'        => $c['plan_name'],
                    'framework_id'     => $c['framework_id'],
                    'selected_prof_id' => $c['selected_prof_id'],
                    'prof_label'       => $c['prof_label'],
                    'prof_color'       => $c['prof_color'],
                    'is_must_have'     => $c['is_must_have'] ? true : false,
                ];
            }

            return $this->respond([
                'data' => [
                    'job_title'          => $job['job_title'] ?? '',
                    'comp_type'          => $job['jobd_comp_type'],
                    'clusters'           => array_values($clusters),
                    'proficiency_levels' => $profLevels,
                ],
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::competencies error: ' . $e->getMessage());
            return $this->failServerError('Error fetching competencies from HRMS');
        }
    }

    // =====================================================================
    //  DUAL-SOURCE CRUD ENDPOINTS
    // =====================================================================

    /**
     * Generic GET handler — merges HRMS + EP data for a given entity type.
     *
     * 1. Get hrms_employee_id from auth
     * 2. Fetch from HRMS read-only model (getByEmpId)
     * 3. Fetch from EP model (getByEmployeeId)
     * 4. Get overridden and deleted HRMS IDs from EP model
     * 5. Filter out overridden/deleted HRMS records
     * 6. Tag each record with _source and _id
     * 7. Merge and return
     */
    private function getMergedData(string $entitySlug): array
    {
        $cfg = $this->entityMap[$entitySlug];
        $hrmsModel = $cfg['hrmsModel'];
        $epModel   = $cfg['epModel'];
        $hrmsPk    = $cfg['hrmsPk'];

        $userId    = $this->getUserId();
        $hrmsEmpId = $this->getHrmsEmpId();

        // Fetch HRMS records (may be empty if no HRMS employee link)
        $hrmsRecords = $hrmsEmpId ? $hrmsModel->getByEmpId($hrmsEmpId) : [];

        // Fetch EP records
        $epRecords = $epModel->getByEmployeeId($userId);

        // Get overridden / deleted HRMS IDs
        $overriddenIds = $epModel->getOverriddenHrmsIds($userId);
        $deletedIds    = $epModel->getDeletedHrmsIds($userId);
        $excludeIds    = array_merge($overriddenIds, $deletedIds);

        // Filter out HRMS records that have been overridden or deleted in EP
        $merged = [];

        foreach ($hrmsRecords as $rec) {
            $hrmsId = (string) ($rec[$hrmsPk] ?? '');
            if (in_array($hrmsId, $excludeIds, true)) {
                continue;
            }
            $rec['_source'] = 'hrms';
            $rec['_id']     = 'hrms_' . $hrmsId;
            $merged[] = $rec;
        }

        // Add EP records (tag with source)
        foreach ($epRecords as $rec) {
            $rec['_source'] = 'ep';
            $rec['_id']     = 'ep_' . $rec['id'];
            $merged[] = $rec;
        }

        return $merged;
    }

    // ── Education ────────────────────────────────────────────────────────

    /**
     * GET /hrms-data/education
     */
    public function getEducation()
    {
        try {
            $data = $this->getMergedData('education');
            return $this->respond(['data' => $data], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::getEducation error: ' . $e->getMessage());
            return $this->failServerError('Error fetching education data');
        }
    }

    /**
     * POST /hrms-data/education
     */
    public function createEducation()
    {
        return $this->createEpRecord('education');
    }

    /**
     * PUT /hrms-data/education/{id}
     */
    public function updateEducation($id)
    {
        return $this->updateEpRecord('education', $id);
    }

    /**
     * DELETE /hrms-data/education/{id}
     */
    public function deleteEducation($id)
    {
        return $this->deleteEpRecord('education', $id);
    }

    // ── Work Experience ──────────────────────────────────────────────────

    /**
     * GET /hrms-data/work-experience
     */
    public function getWorkExperience()
    {
        try {
            $data = $this->getMergedData('work-experience');
            return $this->respond(['data' => $data], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::getWorkExperience error: ' . $e->getMessage());
            return $this->failServerError('Error fetching work experience data');
        }
    }

    /**
     * POST /hrms-data/work-experience
     */
    public function createWorkExperience()
    {
        return $this->createEpRecord('work-experience');
    }

    /**
     * PUT /hrms-data/work-experience/{id}
     */
    public function updateWorkExperience($id)
    {
        return $this->updateEpRecord('work-experience', $id);
    }

    /**
     * DELETE /hrms-data/work-experience/{id}
     */
    public function deleteWorkExperience($id)
    {
        return $this->deleteEpRecord('work-experience', $id);
    }

    // ── Skills ───────────────────────────────────────────────────────────

    /**
     * GET /hrms-data/skills
     */
    public function getSkills()
    {
        try {
            $data = $this->getMergedData('skills');
            return $this->respond(['data' => $data], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::getSkills error: ' . $e->getMessage());
            return $this->failServerError('Error fetching skills data');
        }
    }

    /**
     * POST /hrms-data/skills
     */
    public function createSkill()
    {
        return $this->createEpRecord('skills');
    }

    /**
     * PUT /hrms-data/skills/{id}
     */
    public function updateSkill($id)
    {
        return $this->updateEpRecord('skills', $id);
    }

    /**
     * DELETE /hrms-data/skills/{id}
     */
    public function deleteSkill($id)
    {
        return $this->deleteEpRecord('skills', $id);
    }

    // ── Certifications ───────────────────────────────────────────────────

    /**
     * GET /hrms-data/certifications
     */
    public function getCertifications()
    {
        try {
            $data = $this->getMergedData('certifications');
            return $this->respond(['data' => $data], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::getCertifications error: ' . $e->getMessage());
            return $this->failServerError('Error fetching certifications data');
        }
    }

    /**
     * POST /hrms-data/certifications
     */
    public function createCertification()
    {
        return $this->createEpRecord('certifications');
    }

    /**
     * PUT /hrms-data/certifications/{id}
     */
    public function updateCertification($id)
    {
        return $this->updateEpRecord('certifications', $id);
    }

    /**
     * DELETE /hrms-data/certifications/{id}
     */
    public function deleteCertification($id)
    {
        return $this->deleteEpRecord('certifications', $id);
    }

    // ── Awards ───────────────────────────────────────────────────────────

    /**
     * GET /hrms-data/awards
     */
    public function getAwards()
    {
        try {
            $data = $this->getMergedData('awards');
            return $this->respond(['data' => $data], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::getAwards error: ' . $e->getMessage());
            return $this->failServerError('Error fetching awards data');
        }
    }

    /**
     * POST /hrms-data/awards
     */
    public function createAward()
    {
        return $this->createEpRecord('awards');
    }

    /**
     * PUT /hrms-data/awards/{id}
     */
    public function updateAward($id)
    {
        return $this->updateEpRecord('awards', $id);
    }

    /**
     * DELETE /hrms-data/awards/{id}
     */
    public function deleteAward($id)
    {
        return $this->deleteEpRecord('awards', $id);
    }

    // ── Projects ─────────────────────────────────────────────────────────

    /**
     * GET /hrms-data/projects
     */
    public function getProjects()
    {
        try {
            $data = $this->getMergedData('projects');
            return $this->respond(['data' => $data], 200);
        } catch (\Throwable $e) {
            log_message('error', 'HrmsData::getProjects error: ' . $e->getMessage());
            return $this->failServerError('Error fetching projects data');
        }
    }

    /**
     * POST /hrms-data/projects
     */
    public function createProject()
    {
        return $this->createEpRecord('projects');
    }

    /**
     * PUT /hrms-data/projects/{id}
     */
    public function updateProject($id)
    {
        return $this->updateEpRecord('projects', $id);
    }

    /**
     * DELETE /hrms-data/projects/{id}
     */
    public function deleteProject($id)
    {
        return $this->deleteEpRecord('projects', $id);
    }

    // =====================================================================
    //  GENERIC EP WRITE HELPERS
    // =====================================================================

    /**
     * Create a new record in the EP table.
     * POST body is JSON.
     */
    private function createEpRecord(string $entitySlug)
    {
        try {
            $cfg     = $this->entityMap[$entitySlug];
            $epModel = $cfg['epModel'];
            $label   = $cfg['label'];
            $userId  = $this->getUserId();

            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;
            $data['source']      = 'ep';

            // If this is an override of an HRMS record, the caller can pass hrms_original_id
            // to link back to the HRMS record being overridden.

            $id = $epModel->insert($data);

            if ($id) {
                $created = $epModel->find($id);
                return $this->respond([
                    'status'  => 'success',
                    'message' => "{$label} created successfully",
                    'data'    => $created,
                ], 201);
            }

            return $this->fail($epModel->errors(), 422);
        } catch (\Throwable $e) {
            log_message('error', "HrmsData::createEpRecord({$entitySlug}) error: " . $e->getMessage());
            return $this->failServerError("Error creating {$entitySlug} record");
        }
    }

    /**
     * Update an EP record, or create an override of an HRMS record.
     *
     * If the {id} refers to an existing EP record (numeric), update it directly.
     * If the request body contains `hrms_original_id`, this creates an EP override
     * of the specified HRMS record.
     */
    private function updateEpRecord(string $entitySlug, $id)
    {
        try {
            $cfg     = $this->entityMap[$entitySlug];
            $epModel = $cfg['epModel'];
            $label   = $cfg['label'];
            $userId  = $this->getUserId();

            $data = $this->request->getJSON(true);

            // Check if this is an override of an HRMS record (id might be a hrms_original_id)
            $existing = $epModel->find($id);

            if (!$existing) {
                // ID not found in EP — check if caller wants to create an override
                // The frontend sends hrms_original_id in the body when overriding
                if (!empty($data['hrms_original_id'])) {
                    $data['employee_id'] = $userId;
                    $data['source']      = 'ep';
                    $newId = $epModel->insert($data);
                    if ($newId) {
                        $created = $epModel->find($newId);
                        return $this->respond([
                            'status'  => 'success',
                            'message' => "{$label} override created successfully",
                            'data'    => $created,
                        ], 201);
                    }
                    return $this->fail($epModel->errors(), 422);
                }

                return $this->failNotFound("{$label} record not found");
            }

            // Verify ownership
            if ($existing['employee_id'] != $userId) {
                return $this->failForbidden("{$label} record not found or unauthorized");
            }

            // Don't allow changing employee_id or source
            unset($data['employee_id'], $data['source'], $data['id']);

            if ($epModel->update($id, $data)) {
                $updated = $epModel->find($id);
                return $this->respond([
                    'status'  => 'success',
                    'message' => "{$label} updated successfully",
                    'data'    => $updated,
                ], 200);
            }

            return $this->fail($epModel->errors(), 422);
        } catch (\Throwable $e) {
            log_message('error', "HrmsData::updateEpRecord({$entitySlug}, {$id}) error: " . $e->getMessage());
            return $this->failServerError("Error updating {$entitySlug} record");
        }
    }

    /**
     * Soft-delete an EP record, or create a tombstone for an HRMS record.
     *
     * If the {id} is an EP record, soft-delete it.
     * If the request body contains `hrms_original_id`, create a tombstone record
     * in EP so the HRMS record is excluded from the merged view.
     */
    private function deleteEpRecord(string $entitySlug, $id)
    {
        try {
            $cfg     = $this->entityMap[$entitySlug];
            $epModel = $cfg['epModel'];
            $label   = $cfg['label'];
            $userId  = $this->getUserId();

            $existing = $epModel->find($id);

            if ($existing) {
                // Verify ownership
                if ($existing['employee_id'] != $userId) {
                    return $this->failForbidden("{$label} record not found or unauthorized");
                }

                // Soft delete
                if ($epModel->delete($id)) {
                    return $this->respond([
                        'status'  => 'success',
                        'message' => "{$label} deleted successfully",
                    ], 200);
                }

                return $this->failServerError("Error deleting {$label} record");
            }

            // Not found in EP — check if this is a request to tombstone an HRMS record
            $body = $this->request->getJSON(true) ?? [];
            $hrmsOriginalId = $body['hrms_original_id'] ?? null;

            if ($hrmsOriginalId) {
                // Create a tombstone record in EP (soft-deleted immediately)
                $tombstone = [
                    'employee_id'     => $userId,
                    'hrms_original_id' => $hrmsOriginalId,
                    'source'          => 'hrms_override',
                ];

                $newId = $epModel->insert($tombstone, true);
                if ($newId) {
                    // Immediately soft-delete the tombstone
                    $epModel->delete($newId);
                    return $this->respond([
                        'status'  => 'success',
                        'message' => "{$label} hidden successfully",
                    ], 200);
                }

                return $this->failServerError("Error creating tombstone for {$label} record");
            }

            return $this->failNotFound("{$label} record not found");
        } catch (\Throwable $e) {
            log_message('error', "HrmsData::deleteEpRecord({$entitySlug}, {$id}) error: " . $e->getMessage());
            return $this->failServerError("Error deleting {$entitySlug} record");
        }
    }
}
