<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Export extends Controller
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Export employee profile as structured JSON
     * GET /export/employee-profile/{id}
     */
    public function employeeProfile($id)
    {
        try {
            $employee = $this->db->table('employees e')
                ->select([
                    'e.employee_id', 'e.first_name', 'e.last_name', 'e.email',
                    'e.phone', 'e.date_of_birth', 'e.nationality',
                    'e.profile_picture_url', 'e.status',
                ])
                ->where('e.id', $id)
                ->where('e.deleted_at IS NULL')
                ->get()->getRowArray();

            if (!$employee) {
                return $this->failNotFound('Employee not found');
            }

            $job = $this->db->table('job_information')
                ->where('employee_id', $id)
                ->where('deleted_at IS NULL')
                ->get()->getRowArray();

            $history = $this->db->table('employment_history')
                ->where('employee_id', $id)
                ->orderBy('start_date', 'DESC')
                ->get()->getResultArray();

            $skills = $this->db->table('employee_skills es')
                ->select('s.skill_name, s.skill_category, es.proficiency_level, es.years_of_experience')
                ->join('skills s', 's.id = es.skill_id')
                ->where('es.employee_id', $id)
                ->orderBy('es.proficiency_level', 'DESC')
                ->get()->getResultArray();

            $certifications = $this->db->table('certifications')
                ->select('certification_name, issuing_body, issue_date, expiry_date, status')
                ->where('employee_id', $id)
                ->orderBy('issue_date', 'DESC')
                ->get()->getResultArray();

            $reviews = $this->db->table('performance_reviews')
                ->select('review_period, review_date, overall_rating, performance_status, approval_status')
                ->where('employee_id', $id)
                ->orderBy('review_date', 'DESC')
                ->limit(3)
                ->get()->getResultArray();

            $awards = $this->db->table('awards_recognition')
                ->select('award_name, award_date, description')
                ->where('employee_id', $id)
                ->orderBy('award_date', 'DESC')
                ->get()->getResultArray();

            $this->response->setHeader('Content-Disposition', 'attachment; filename="employee_profile_' . $id . '.json"');

            return $this->respond([
                'data' => [
                    'personal'           => $employee,
                    'job'                => $job,
                    'employment_history' => $history,
                    'skills'             => $skills,
                    'certifications'     => $certifications,
                    'performance'        => $reviews,
                    'awards'             => $awards,
                ],
                'exported_at' => date('Y-m-d H:i:s'),
                'format'      => 'json',
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Export::employeeProfile - ' . $e->getMessage());
            return $this->failServerError('Error exporting employee profile');
        }
    }

    /**
     * Export organizational chart data
     * GET /export/org-chart
     * Query params: department
     */
    public function orgChart()
    {
        try {
            $department = $this->request->getVar('department');

            $query = $this->db->table('org_hierarchy oh')
                ->select([
                    'oh.employee_id', 'oh.parent_id', 'oh.department', 'oh.division',
                    'oh.team', 'oh.org_level', 'oh.is_manager', 'oh.team_size', 'oh.hierarchy_path',
                    'e.first_name', 'e.last_name', 'e.email', 'e.profile_picture_url',
                    'ji.designation', 'ji.location',
                ])
                ->join('employees e', 'e.id = oh.employee_id')
                ->join('job_information ji', 'ji.employee_id = oh.employee_id', 'left')
                ->where('e.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->orderBy('oh.org_level', 'ASC')
                ->orderBy('oh.department', 'ASC');

            if ($department) {
                $query->where('oh.department', $department);
            }

            $nodes = $query->get()->getResultArray();

            $this->response->setHeader('Content-Disposition', 'attachment; filename="org_chart.json"');

            return $this->respond([
                'data'        => $nodes,
                'total_nodes' => count($nodes),
                'exported_at' => date('Y-m-d H:i:s'),
                'format'      => 'json',
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Export::orgChart - ' . $e->getMessage());
            return $this->failServerError('Error exporting organizational chart');
        }
    }

    /**
     * Export performance report
     * GET /export/performance-report
     * Query params: period, department
     */
    public function performanceReport()
    {
        try {
            $period     = $this->request->getVar('period');
            $department = $this->request->getVar('department');

            $query = $this->db->table('performance_reviews pr')
                ->select([
                    'e.employee_id', 'e.first_name', 'e.last_name',
                    'ji.designation', 'ji.department',
                    'pr.review_period', 'pr.review_date', 'pr.overall_rating',
                    'pr.performance_status', 'pr.approval_status', 'pr.goals_met',
                    'pr.strengths', 'pr.areas_for_improvement',
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

            $records   = $query->orderBy('ji.department', 'ASC')->orderBy('e.last_name', 'ASC')->get()->getResultArray();
            $ratings   = array_column($records, 'overall_rating');
            $avgRating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;

            $this->response->setHeader('Content-Disposition', 'attachment; filename="performance_report.json"');

            return $this->respond([
                'data'        => $records,
                'total'       => count($records),
                'summary'     => ['avg_rating' => $avgRating],
                'filters'     => ['period' => $period, 'department' => $department],
                'exported_at' => date('Y-m-d H:i:s'),
                'format'      => 'json',
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Export::performanceReport - ' . $e->getMessage());
            return $this->failServerError('Error exporting performance report');
        }
    }

    /**
     * Export skill audit
     * GET /export/skill-audit
     * Query params: department, category
     */
    public function skillAudit()
    {
        try {
            $department = $this->request->getVar('department');
            $category   = $this->request->getVar('category');

            $query = $this->db->table('employee_skills es')
                ->select([
                    'e.employee_id', 'e.first_name', 'e.last_name',
                    'ji.department', 'ji.designation',
                    's.skill_name', 's.skill_category',
                    'es.proficiency_level', 'es.years_of_experience', 'es.verified', 'es.last_used_date',
                ])
                ->join('employees e', 'e.id = es.employee_id')
                ->join('skills s', 's.id = es.skill_id')
                ->join('job_information ji', 'ji.employee_id = es.employee_id', 'left')
                ->where('e.deleted_at IS NULL')
                ->where('e.status', 'Active');

            if ($department) {
                $query->where('ji.department', $department);
            }
            if ($category) {
                $query->where('s.skill_category', $category);
            }

            $records = $query->orderBy('ji.department', 'ASC')->orderBy('e.last_name', 'ASC')->get()->getResultArray();

            // Employees with fewer than 3 skills (skill gap)
            $gapQuery = $this->db->table('employees e')
                ->select('e.employee_id, e.first_name, e.last_name, ji.department, COUNT(es.id) as skill_count')
                ->join('employee_skills es', 'es.employee_id = e.id', 'left')
                ->join('job_information ji', 'ji.employee_id = e.id', 'left')
                ->where('e.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->groupBy('e.id')
                ->having('skill_count <', 3);

            if ($department) {
                $gapQuery->where('ji.department', $department);
            }

            $gaps = $gapQuery->get()->getResultArray();

            $this->response->setHeader('Content-Disposition', 'attachment; filename="skill_audit.json"');

            return $this->respond([
                'data'        => $records,
                'total'       => count($records),
                'skill_gaps'  => $gaps,
                'filters'     => ['department' => $department, 'category' => $category],
                'exported_at' => date('Y-m-d H:i:s'),
                'format'      => 'json',
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Export::skillAudit - ' . $e->getMessage());
            return $this->failServerError('Error exporting skill audit');
        }
    }
}
