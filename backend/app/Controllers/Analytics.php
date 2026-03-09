<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Analytics extends Controller
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get organizational structure analytics
     * GET /analytics/org-structure
     */
    public function getOrgStructure()
    {
        try {
            $departments = $this->db->table('job_information ji')
                ->select('ji.department, ji.business_unit, COUNT(ji.employee_id) as headcount')
                ->join('employees e', 'e.id = ji.employee_id')
                ->where('ji.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->where('e.deleted_at IS NULL')
                ->groupBy('ji.department, ji.business_unit')
                ->orderBy('headcount', 'DESC')
                ->get()->getResultArray();

            $managerCount = $this->db->table('org_hierarchy')
                ->where('is_manager', 1)
                ->countAllResults();

            $totalEmployees = $this->db->table('employees')
                ->where('status', 'Active')
                ->where('deleted_at IS NULL')
                ->countAllResults();

            $levels = $this->db->table('org_hierarchy')
                ->select('org_level, COUNT(*) as count')
                ->groupBy('org_level')
                ->orderBy('org_level', 'ASC')
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'total_employees' => $totalEmployees,
                    'total_managers'  => $managerCount,
                    'departments'     => $departments,
                    'org_levels'      => $levels,
                    'generated_at'    => date('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getOrgStructure - ' . $e->getMessage());
            return $this->failServerError('Error fetching org structure analytics');
        }
    }

    /**
     * Get department statistics
     * GET /analytics/department-stats
     */
    public function getDepartmentStats()
    {
        try {
            $stats = $this->db->table('job_information ji')
                ->select([
                    'ji.department',
                    'ji.business_unit',
                    'COUNT(ji.employee_id) as headcount',
                    'COUNT(CASE WHEN ji.employment_type = "Full-Time" THEN 1 END) as full_time',
                    'COUNT(CASE WHEN ji.employment_type = "Part-Time" THEN 1 END) as part_time',
                    'COUNT(CASE WHEN ji.employment_type = "Contract" THEN 1 END) as contract',
                    'COUNT(CASE WHEN ji.employment_type = "Intern" THEN 1 END) as intern',
                ])
                ->join('employees e', 'e.id = ji.employee_id')
                ->where('ji.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->where('e.deleted_at IS NULL')
                ->groupBy('ji.department, ji.business_unit')
                ->orderBy('headcount', 'DESC')
                ->get()->getResultArray();

            $locations = $this->db->table('job_information')
                ->select('location, COUNT(*) as headcount')
                ->where('deleted_at IS NULL')
                ->groupBy('location')
                ->orderBy('headcount', 'DESC')
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'departments' => $stats,
                    'locations'   => $locations,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getDepartmentStats - ' . $e->getMessage());
            return $this->failServerError('Error fetching department stats');
        }
    }

    /**
     * Get team statistics
     * GET /analytics/team-stats
     */
    public function getTeamStats()
    {
        try {
            $teams = $this->db->table('org_hierarchy oh')
                ->select([
                    'oh.team',
                    'oh.department',
                    'COUNT(oh.id) as size',
                    'SUM(CAST(oh.is_manager AS UNSIGNED)) as manager_count',
                ])
                ->where('oh.team IS NOT NULL')
                ->groupBy('oh.team, oh.department')
                ->orderBy('size', 'DESC')
                ->get()->getResultArray();

            $avgTeamSize = $this->db->table('org_hierarchy')
                ->selectAvg('team_size', 'avg_team_size')
                ->where('is_manager', 1)
                ->get()->getRow();

            return $this->respond([
                'data' => [
                    'teams'         => $teams,
                    'avg_team_size' => round($avgTeamSize->avg_team_size ?? 0, 1),
                    'total_teams'   => count($teams),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getTeamStats - ' . $e->getMessage());
            return $this->failServerError('Error fetching team stats');
        }
    }

    /**
     * Get performance summary
     * GET /analytics/performance-summary
     */
    public function getPerformanceSummary()
    {
        try {
            $ratings = $this->db->table('performance_reviews')
                ->select('overall_rating, COUNT(*) as count')
                ->groupBy('overall_rating')
                ->orderBy('overall_rating', 'DESC')
                ->get()->getResultArray();

            $statuses = $this->db->table('performance_reviews')
                ->select('performance_status, COUNT(*) as count')
                ->groupBy('performance_status')
                ->get()->getResultArray();

            $avgRow = $this->db->table('performance_reviews')
                ->selectAvg('overall_rating', 'avg_rating')
                ->get()->getRow();

            $pending = $this->db->table('performance_reviews')
                ->where('approval_status', 'Pending')
                ->countAllResults();

            $goalStats = $this->db->table('performance_goals')
                ->select('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'avg_rating'          => round($avgRow->avg_rating ?? 0, 2),
                    'pending_approvals'   => $pending,
                    'rating_distribution' => $ratings,
                    'status_breakdown'    => $statuses,
                    'goal_stats'          => $goalStats,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getPerformanceSummary - ' . $e->getMessage());
            return $this->failServerError('Error fetching performance summary');
        }
    }

    /**
     * Get review statistics
     * GET /analytics/review-statistics
     */
    public function getReviewStatistics()
    {
        try {
            $period = $this->request->getVar('period');

            $byPeriodQuery = $this->db->table('performance_reviews')
                ->select('review_period, COUNT(*) as count, AVG(overall_rating) as avg_rating')
                ->groupBy('review_period')
                ->orderBy('review_period', 'DESC');

            if ($period) {
                $byPeriodQuery->where('review_period', $period);
            }

            $byPeriod = $byPeriodQuery->get()->getResultArray();

            $approvalStats = $this->db->table('performance_reviews')
                ->select('approval_status, COUNT(*) as count')
                ->groupBy('approval_status')
                ->get()->getResultArray();

            $total = $this->db->table('performance_reviews')->countAllResults();

            return $this->respond([
                'data' => [
                    'total_reviews'  => $total,
                    'by_period'      => $byPeriod,
                    'approval_stats' => $approvalStats,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getReviewStatistics - ' . $e->getMessage());
            return $this->failServerError('Error fetching review statistics');
        }
    }

    /**
     * Get skill inventory
     * GET /analytics/skill-inventory
     */
    public function getSkillInventory()
    {
        try {
            $byCategory = $this->db->table('skills s')
                ->select('s.skill_category, COUNT(DISTINCT s.id) as skill_count, COUNT(es.id) as employee_assignments')
                ->join('employee_skills es', 'es.skill_id = s.id', 'left')
                ->groupBy('s.skill_category')
                ->orderBy('employee_assignments', 'DESC')
                ->get()->getResultArray();

            $topSkills = $this->db->table('skills s')
                ->select('s.skill_name, s.skill_category, COUNT(es.id) as employee_count, AVG(es.years_of_experience) as avg_experience')
                ->join('employee_skills es', 'es.skill_id = s.id', 'left')
                ->groupBy('s.id')
                ->orderBy('employee_count', 'DESC')
                ->limit(10)
                ->get()->getResultArray();

            $proficiency = $this->db->table('employee_skills')
                ->select('proficiency_level, COUNT(*) as count')
                ->groupBy('proficiency_level')
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'by_category' => $byCategory,
                    'top_skills'  => $topSkills,
                    'proficiency' => $proficiency,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getSkillInventory - ' . $e->getMessage());
            return $this->failServerError('Error fetching skill inventory');
        }
    }

    /**
     * Get competency matrix
     * GET /analytics/competency-matrix
     */
    public function getCompetencyMatrix()
    {
        try {
            $matrix = $this->db->table('competencies c')
                ->select([
                    'c.competency_name',
                    'c.competency_category',
                    'COUNT(ec.id) as assessed_employees',
                    'AVG(ec.proficiency_level) as avg_proficiency',
                    'COUNT(CASE WHEN ec.proficiency_level >= 4 THEN 1 END) as high_proficiency',
                    'COUNT(CASE WHEN ec.proficiency_level <= 2 THEN 1 END) as low_proficiency',
                ])
                ->join('employee_competencies ec', 'ec.competency_id = c.id', 'left')
                ->groupBy('c.id')
                ->orderBy('assessed_employees', 'DESC')
                ->get()->getResultArray();

            $gaps = array_values(array_filter($matrix, fn($row) => ($row['avg_proficiency'] ?? 5) < 3 && $row['assessed_employees'] > 0));

            return $this->respond([
                'data' => [
                    'matrix' => $matrix,
                    'gaps'   => $gaps,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getCompetencyMatrix - ' . $e->getMessage());
            return $this->failServerError('Error fetching competency matrix');
        }
    }

    /**
     * Get training statistics
     * GET /analytics/training-stats
     */
    public function getTrainingStats()
    {
        try {
            $enrollmentStatus = $this->db->table('course_enrollments')
                ->select('completion_status, COUNT(*) as count')
                ->groupBy('completion_status')
                ->get()->getResultArray();

            $total     = $this->db->table('course_enrollments')->countAllResults();
            $completed = $this->db->table('course_enrollments')->where('completion_status', 'Completed')->countAllResults();
            $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

            $topCourses = $this->db->table('course_enrollments ce')
                ->select('c.course_name, c.course_type, COUNT(ce.id) as enrollments, AVG(ce.score) as avg_score')
                ->join('courses c', 'c.id = ce.course_id')
                ->groupBy('ce.course_id')
                ->orderBy('enrollments', 'DESC')
                ->limit(10)
                ->get()->getResultArray();

            $trainingHours = $this->db->table('training_history')
                ->selectSum('duration_hours', 'total_hours')
                ->get()->getRow();

            return $this->respond([
                'data' => [
                    'total_enrollments'    => $total,
                    'completed'            => $completed,
                    'completion_rate'      => $completionRate . '%',
                    'enrollment_status'    => $enrollmentStatus,
                    'top_courses'          => $topCourses,
                    'total_training_hours' => $trainingHours->total_hours ?? 0,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getTrainingStats - ' . $e->getMessage());
            return $this->failServerError('Error fetching training stats');
        }
    }

    /**
     * Get course effectiveness
     * GET /analytics/course-effectiveness
     */
    public function getCourseEffectiveness()
    {
        try {
            $effectiveness = $this->db->table('courses c')
                ->select([
                    'c.course_name',
                    'c.course_code',
                    'c.course_type',
                    'c.duration_hours',
                    'COUNT(ce.id) as total_enrollments',
                    'COUNT(CASE WHEN ce.completion_status = "Completed" THEN 1 END) as completions',
                    'COUNT(CASE WHEN ce.passed = 1 THEN 1 END) as passed',
                    'AVG(ce.score) as avg_score',
                    'AVG(ce.completion_percentage) as avg_completion_pct',
                ])
                ->join('course_enrollments ce', 'ce.course_id = c.id', 'left')
                ->groupBy('c.id')
                ->orderBy('total_enrollments', 'DESC')
                ->get()->getResultArray();

            foreach ($effectiveness as &$row) {
                $enrol = (int) $row['total_enrollments'];
                $row['pass_rate']        = $enrol > 0 ? round(((int) $row['passed'] / $enrol) * 100, 1) . '%' : '0%';
                $row['completion_rate']  = $enrol > 0 ? round(((int) $row['completions'] / $enrol) * 100, 1) . '%' : '0%';
                $row['avg_score']        = $row['avg_score'] ? round($row['avg_score'], 1) : null;
            }
            unset($row);

            return $this->respond(['data' => $effectiveness], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getCourseEffectiveness - ' . $e->getMessage());
            return $this->failServerError('Error fetching course effectiveness');
        }
    }

    /**
     * Get HR dashboard data
     * GET /analytics/hr-dashboard
     */
    public function getHrDashboard()
    {
        try {
            $db = $this->db;

            $totalEmployees  = $db->table('employees')->where('status', 'Active')->where('deleted_at IS NULL')->countAllResults();
            $newThisMonth    = $db->table('job_information')->where('MONTH(joined_date) = MONTH(NOW())')->where('YEAR(joined_date) = YEAR(NOW())')->where('deleted_at IS NULL')->countAllResults();
            $pendingReviews  = $db->table('performance_reviews')->where('approval_status', 'Pending')->countAllResults();
            $expiringDocs    = $db->table('compliance_documents')
                ->where('expiry_date <=', date('Y-m-d', strtotime('+30 days')))
                ->where('expiry_date >=', date('Y-m-d'))
                ->countAllResults();
            $openEnrollments = $db->table('course_enrollments')->where('completion_status', 'Enrolled')->countAllResults();

            $deptBreakdown = $db->table('job_information')
                ->select('department, COUNT(*) as count')
                ->where('deleted_at IS NULL')
                ->groupBy('department')
                ->orderBy('count', 'DESC')
                ->limit(8)
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'total_active_employees' => $totalEmployees,
                    'new_hires_this_month'   => $newThisMonth,
                    'pending_reviews'        => $pendingReviews,
                    'expiring_documents_30d' => $expiringDocs,
                    'open_enrollments'       => $openEnrollments,
                    'top_departments'        => $deptBreakdown,
                    'generated_at'           => date('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getHrDashboard - ' . $e->getMessage());
            return $this->failServerError('Error fetching HR dashboard');
        }
    }

    /**
     * Get employee engagement metrics
     * GET /analytics/employee-engagement
     */
    public function getEmployeeEngagement()
    {
        try {
            $db = $this->db;

            $totalEmployees       = $db->table('employees')->where('status', 'Active')->where('deleted_at IS NULL')->countAllResults();
            $trainingParticipants = $db->query('SELECT COUNT(DISTINCT employee_id) as c FROM course_enrollments')->getRow()->c ?? 0;
            $reviewedEmployees    = $db->query('SELECT COUNT(DISTINCT employee_id) as c FROM performance_reviews')->getRow()->c ?? 0;
            $idpEmployees         = $db->query('SELECT COUNT(DISTINCT employee_id) as c FROM individual_development_plan')->getRow()->c ?? 0;
            $awardRecipients      = $db->query('SELECT COUNT(DISTINCT employee_id) as c FROM awards_recognition')->getRow()->c ?? 0;

            $trainingRate = $totalEmployees > 0 ? round(($trainingParticipants / $totalEmployees) * 100, 1) : 0;
            $reviewRate   = $totalEmployees > 0 ? round(($reviewedEmployees / $totalEmployees) * 100, 1) : 0;
            $idpRate      = $totalEmployees > 0 ? round(($idpEmployees / $totalEmployees) * 100, 1) : 0;

            return $this->respond([
                'data' => [
                    'total_active_employees' => $totalEmployees,
                    'training_participation' => ['count' => $trainingParticipants, 'rate' => $trainingRate . '%'],
                    'performance_reviewed'   => ['count' => $reviewedEmployees, 'rate' => $reviewRate . '%'],
                    'idp_enrolled'           => ['count' => $idpEmployees, 'rate' => $idpRate . '%'],
                    'award_recipients'       => $awardRecipients,
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Analytics::getEmployeeEngagement - ' . $e->getMessage());
            return $this->failServerError('Error fetching engagement metrics');
        }
    }
}
