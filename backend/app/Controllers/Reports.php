<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Reports extends Controller
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Employee summary report
     * GET /reports/employee-summary
     * Query params: department, status, page, per_page
     */
    public function employeeSummary()
    {
        try {
            $department = $this->request->getVar('department');
            $status     = $this->request->getVar('status') ?? 'Active';
            $page       = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage    = min(200, max(1, (int) ($this->request->getVar('per_page') ?? 50)));
            $offset     = ($page - 1) * $perPage;

            $query = $this->db->table('employees e')
                ->select([
                    'e.employee_id',
                    'e.first_name',
                    'e.last_name',
                    'e.email',
                    'e.status',
                    'e.phone',
                    'ji.designation',
                    'ji.department',
                    'ji.business_unit',
                    'ji.employment_type',
                    'ji.joined_date',
                    'ji.location',
                    'ji.grade',
                ])
                ->join('job_information ji', 'ji.employee_id = e.id', 'left')
                ->where('e.deleted_at IS NULL')
                ->where('ji.deleted_at IS NULL');

            if ($status) {
                $query->where('e.status', $status);
            }
            if ($department) {
                $query->where('ji.department', $department);
            }

            $total   = $query->countAllResults(false);
            $records = $query->orderBy('e.last_name', 'ASC')->orderBy('e.first_name', 'ASC')->limit($perPage, $offset)->get()->getResultArray();

            return $this->respond([
                'data'         => $records,
                'total'        => $total,
                'page'         => $page,
                'per_page'     => $perPage,
                'pages'        => (int) ceil($total / $perPage),
                'generated_at' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::employeeSummary - ' . $e->getMessage());
            return $this->failServerError('Error generating employee summary');
        }
    }

    /**
     * Organizational structure report
     * GET /reports/org-structure
     * Query params: department
     */
    public function orgStructure()
    {
        try {
            $department = $this->request->getVar('department');

            $query = $this->db->table('org_hierarchy oh')
                ->select([
                    'e.employee_id',
                    'e.first_name',
                    'e.last_name',
                    'oh.department',
                    'oh.division',
                    'oh.section',
                    'oh.team',
                    'oh.org_level',
                    'oh.is_manager',
                    'oh.team_size',
                    'oh.hierarchy_path',
                    'ji.designation',
                    'ji.location',
                ])
                ->join('employees e', 'e.id = oh.employee_id')
                ->join('job_information ji', 'ji.employee_id = oh.employee_id', 'left')
                ->where('e.deleted_at IS NULL')
                ->where('e.status', 'Active');

            if ($department) {
                $query->where('oh.department', $department);
            }

            $records = $query->orderBy('oh.org_level', 'ASC')->orderBy('oh.department', 'ASC')->get()->getResultArray();

            // Group by department for readability
            $grouped = [];
            foreach ($records as $row) {
                $dept = $row['department'] ?? 'Unassigned';
                $grouped[$dept][] = $row;
            }

            return $this->respond([
                'data'         => $grouped,
                'total'        => count($records),
                'generated_at' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::orgStructure - ' . $e->getMessage());
            return $this->failServerError('Error generating org structure report');
        }
    }

    /**
     * Performance report
     * GET /reports/performance
     * Query params: period, department, page, per_page
     */
    public function performance()
    {
        try {
            $period     = $this->request->getVar('period');
            $department = $this->request->getVar('department');
            $page       = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage    = min(200, max(1, (int) ($this->request->getVar('per_page') ?? 50)));
            $offset     = ($page - 1) * $perPage;

            $query = $this->db->table('performance_reviews pr')
                ->select([
                    'e.employee_id',
                    'e.first_name',
                    'e.last_name',
                    'ji.designation',
                    'ji.department',
                    'pr.review_period',
                    'pr.review_date',
                    'pr.overall_rating',
                    'pr.performance_status',
                    'pr.approval_status',
                    'pr.goals_met',
                    'pr.strengths',
                    'pr.areas_for_improvement',
                ])
                ->join('employees e', 'e.id = pr.employee_id')
                ->join('job_information ji', 'ji.employee_id = pr.employee_id', 'left')
                ->where('e.deleted_at IS NULL');

            if ($period) {
                $query->where('pr.review_period', $period);
            }
            if ($department) {
                $query->where('ji.department', $department);
            }

            $total   = $query->countAllResults(false);
            $records = $query->orderBy('pr.review_date', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

            $ratings  = array_column($records, 'overall_rating');
            $avgRating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;

            return $this->respond([
                'data'         => $records,
                'total'        => $total,
                'page'         => $page,
                'per_page'     => $perPage,
                'pages'        => (int) ceil($total / $perPage),
                'summary'      => ['avg_rating' => $avgRating],
                'generated_at' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::performance - ' . $e->getMessage());
            return $this->failServerError('Error generating performance report');
        }
    }

    /**
     * Training report
     * GET /reports/training
     * Query params: department, status (completion_status), page, per_page
     */
    public function training()
    {
        try {
            $department = $this->request->getVar('department');
            $status     = $this->request->getVar('status');
            $page       = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage    = min(200, max(1, (int) ($this->request->getVar('per_page') ?? 50)));
            $offset     = ($page - 1) * $perPage;

            $query = $this->db->table('course_enrollments ce')
                ->select([
                    'e.employee_id',
                    'e.first_name',
                    'e.last_name',
                    'ji.department',
                    'c.course_name',
                    'c.course_code',
                    'c.course_type',
                    'c.provider',
                    'c.duration_hours',
                    'ce.enrollment_date',
                    'ce.completion_status',
                    'ce.completion_percentage',
                    'ce.score',
                    'ce.passing_score',
                    'ce.passed',
                    'ce.actual_end_date',
                ])
                ->join('employees e', 'e.id = ce.employee_id')
                ->join('courses c', 'c.id = ce.course_id')
                ->join('job_information ji', 'ji.employee_id = ce.employee_id', 'left')
                ->where('e.deleted_at IS NULL');

            if ($department) {
                $query->where('ji.department', $department);
            }
            if ($status) {
                $query->where('ce.completion_status', $status);
            }

            $total   = $query->countAllResults(false);
            $records = $query->orderBy('ce.enrollment_date', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

            $totalHours = array_sum(array_column($records, 'duration_hours'));
            $completed  = count(array_filter($records, fn($r) => $r['completion_status'] === 'Completed'));

            return $this->respond([
                'data'         => $records,
                'total'        => $total,
                'page'         => $page,
                'per_page'     => $perPage,
                'pages'        => (int) ceil($total / $perPage),
                'summary'      => [
                    'total_training_hours' => $totalHours,
                    'completed_count'      => $completed,
                ],
                'generated_at' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::training - ' . $e->getMessage());
            return $this->failServerError('Error generating training report');
        }
    }

    /**
     * Compliance report
     * GET /reports/compliance
     * Query params: document_type, status, page, per_page
     */
    public function compliance()
    {
        try {
            $docType = $this->request->getVar('document_type');
            $status  = $this->request->getVar('status');
            $page    = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage = min(200, max(1, (int) ($this->request->getVar('per_page') ?? 50)));
            $offset  = ($page - 1) * $perPage;

            $query = $this->db->table('compliance_documents cd')
                ->select([
                    'e.employee_id',
                    'e.first_name',
                    'e.last_name',
                    'ji.department',
                    'cd.document_type',
                    'cd.document_name',
                    'cd.issue_date',
                    'cd.expiry_date',
                    'cd.status',
                    'cd.signed_date',
                    'cd.comments',
                ])
                ->join('employees e', 'e.id = cd.employee_id')
                ->join('job_information ji', 'ji.employee_id = cd.employee_id', 'left')
                ->where('e.deleted_at IS NULL');

            if ($docType) {
                $query->where('cd.document_type', $docType);
            }
            if ($status) {
                $query->where('cd.status', $status);
            }

            $total   = $query->countAllResults(false);
            $records = $query->orderBy('cd.expiry_date', 'ASC')->limit($perPage, $offset)->get()->getResultArray();

            $expiringCount = count(array_filter($records, function ($r) {
                return $r['expiry_date'] && strtotime($r['expiry_date']) <= strtotime('+30 days') && strtotime($r['expiry_date']) >= strtotime('today');
            }));

            return $this->respond([
                'data'                => $records,
                'total'               => $total,
                'page'                => $page,
                'per_page'            => $perPage,
                'pages'               => (int) ceil($total / $perPage),
                'expiring_in_30_days' => $expiringCount,
                'generated_at'        => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::compliance - ' . $e->getMessage());
            return $this->failServerError('Error generating compliance report');
        }
    }

    /**
     * Headcount report
     * GET /reports/headcount
     * Query params: business_unit, location
     */
    public function headcount()
    {
        try {
            $businessUnit = $this->request->getVar('business_unit');
            $location     = $this->request->getVar('location');

            $query = $this->db->table('job_information ji')
                ->select([
                    'ji.department',
                    'ji.business_unit',
                    'ji.location',
                    'COUNT(*) as headcount',
                    'COUNT(CASE WHEN ji.employment_type = "Full-Time" THEN 1 END) as full_time',
                    'COUNT(CASE WHEN ji.employment_type = "Part-Time" THEN 1 END) as part_time',
                    'COUNT(CASE WHEN ji.employment_type = "Contract" THEN 1 END) as contract',
                    'COUNT(CASE WHEN ji.employment_type = "Intern" THEN 1 END) as intern',
                    'COUNT(CASE WHEN ji.employment_type = "Temporary" THEN 1 END) as temporary',
                ])
                ->join('employees e', 'e.id = ji.employee_id')
                ->where('ji.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->where('e.deleted_at IS NULL')
                ->groupBy('ji.department, ji.business_unit, ji.location')
                ->orderBy('headcount', 'DESC');

            if ($businessUnit) {
                $query->where('ji.business_unit', $businessUnit);
            }
            if ($location) {
                $query->where('ji.location', $location);
            }

            $records = $query->get()->getResultArray();

            $summary = [
                'total'     => array_sum(array_column($records, 'headcount')),
                'full_time' => array_sum(array_column($records, 'full_time')),
                'part_time' => array_sum(array_column($records, 'part_time')),
                'contract'  => array_sum(array_column($records, 'contract')),
                'intern'    => array_sum(array_column($records, 'intern')),
                'temporary' => array_sum(array_column($records, 'temporary')),
            ];

            return $this->respond([
                'data'         => $records,
                'summary'      => $summary,
                'generated_at' => date('Y-m-d H:i:s'),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Reports::headcount - ' . $e->getMessage());
            return $this->failServerError('Error generating headcount report');
        }
    }
}
