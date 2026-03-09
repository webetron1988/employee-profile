<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get current user's dashboard
     * GET /dashboard/my-dashboard
     */
    public function myDashboard()
    {
        try {
            $user       = auth()->user();
            $employeeId = $user->employee_id;

            // Active course enrollments
            $activeCourses = $this->db->table('course_enrollments ce')
                ->select('c.course_name, c.course_type, ce.completion_status, ce.completion_percentage, ce.scheduled_end_date')
                ->join('courses c', 'c.id = ce.course_id')
                ->where('ce.employee_id', $employeeId)
                ->whereIn('ce.completion_status', ['Enrolled', 'In Progress'])
                ->orderBy('ce.scheduled_end_date', 'ASC')
                ->limit(5)
                ->get()->getResultArray();

            // Pending goals
            $pendingGoals = $this->db->table('performance_goals')
                ->select('goal_title, status, end_date, progress_percentage')
                ->where('employee_id', $employeeId)
                ->whereIn('status', ['Not Started', 'In Progress'])
                ->orderBy('end_date', 'ASC')
                ->limit(5)
                ->get()->getResultArray();

            // Latest performance review
            $latestReview = $this->db->table('performance_reviews')
                ->where('employee_id', $employeeId)
                ->orderBy('review_date', 'DESC')
                ->limit(1)
                ->get()->getRowArray();

            // Certifications expiring in 60 days
            $expiringCerts = $this->db->table('certifications')
                ->select('certification_name, issuing_organization, expiry_date')
                ->where('employee_id', $employeeId)
                ->where('expiry_date <=', date('Y-m-d', strtotime('+60 days')))
                ->where('expiry_date >=', date('Y-m-d'))
                ->orderBy('expiry_date', 'ASC')
                ->get()->getResultArray();

            // Compliance documents pending signature
            $pendingDocs = $this->db->table('compliance_documents')
                ->select('document_name, document_type, expiry_date')
                ->where('employee_id', $employeeId)
                ->where('status', 'Pending')
                ->get()->getResultArray();

            // Recent awards
            $recentAwards = $this->db->table('awards_recognition')
                ->select('award_name, award_date, award_description')
                ->where('employee_id', $employeeId)
                ->orderBy('award_date', 'DESC')
                ->limit(3)
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'active_courses'          => $activeCourses,
                    'pending_goals'           => $pendingGoals,
                    'latest_review'           => $latestReview,
                    'expiring_certifications' => $expiringCerts,
                    'pending_documents'       => $pendingDocs,
                    'recent_awards'           => $recentAwards,
                    'generated_at'            => date('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::myDashboard - ' . $e->getMessage());
            return $this->failServerError('Error fetching dashboard');
        }
    }

    /**
     * Manager's dashboard
     * GET /dashboard/manager-dashboard
     */
    public function managerDashboard()
    {
        try {
            $user       = auth()->user();
            $employeeId = $user->employee_id;

            // Direct reports
            $directReports = $this->db->table('job_information ji')
                ->select('e.id, e.employee_id, e.first_name, e.last_name, e.email, e.profile_picture_url, ji.designation, ji.department')
                ->join('employees e', 'e.id = ji.employee_id')
                ->where('ji.reporting_manager_id', $employeeId)
                ->where('ji.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->where('e.deleted_at IS NULL')
                ->get()->getResultArray();

            $reportIds     = array_column($directReports, 'id');
            $pendingReviews = 0;
            $teamPerformance = [];
            $teamTraining    = [];

            if (!empty($reportIds)) {
                $pendingReviews = $this->db->table('performance_reviews')
                    ->whereIn('employee_id', $reportIds)
                    ->where('approval_status', 'Pending')
                    ->countAllResults();

                $teamPerformance = $this->db->table('performance_reviews pr')
                    ->select('e.first_name, e.last_name, pr.overall_rating, pr.review_period, pr.performance_status, pr.review_date')
                    ->join('employees e', 'e.id = pr.employee_id')
                    ->whereIn('pr.employee_id', $reportIds)
                    ->orderBy('pr.review_date', 'DESC')
                    ->limit(10)
                    ->get()->getResultArray();

                $teamTraining = $this->db->table('course_enrollments ce')
                    ->select('e.first_name, e.last_name, c.course_name, ce.completion_status, ce.completion_percentage, ce.scheduled_end_date')
                    ->join('employees e', 'e.id = ce.employee_id')
                    ->join('courses c', 'c.id = ce.course_id')
                    ->whereIn('ce.employee_id', $reportIds)
                    ->whereIn('ce.completion_status', ['Enrolled', 'In Progress'])
                    ->orderBy('ce.scheduled_end_date', 'ASC')
                    ->get()->getResultArray();
            }

            // Pending goals across the team
            $teamPendingGoals = 0;
            if (!empty($reportIds)) {
                $teamPendingGoals = $this->db->table('performance_goals')
                    ->whereIn('employee_id', $reportIds)
                    ->whereIn('status', ['Not Started', 'In Progress'])
                    ->countAllResults();
            }

            return $this->respond([
                'data' => [
                    'team_size'          => count($directReports),
                    'direct_reports'     => $directReports,
                    'pending_reviews'    => $pendingReviews,
                    'team_pending_goals' => $teamPendingGoals,
                    'team_performance'   => $teamPerformance,
                    'team_training'      => $teamTraining,
                    'generated_at'       => date('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::managerDashboard - ' . $e->getMessage());
            return $this->failServerError('Error fetching manager dashboard');
        }
    }

    /**
     * HR dashboard
     * GET /dashboard/hr-dashboard
     */
    public function hrDashboard()
    {
        try {
            $db = $this->db;

            $totalEmployees    = $db->table('employees')->where('status', 'Active')->where('deleted_at IS NULL')->countAllResults();
            $newHires          = $db->table('job_information')->where('MONTH(joined_date) = MONTH(NOW())')->where('YEAR(joined_date) = YEAR(NOW())')->where('deleted_at IS NULL')->countAllResults();
            $pendingReviews    = $db->table('performance_reviews')->where('approval_status', 'Pending')->countAllResults();
            $expiringDocuments = $db->table('compliance_documents')
                ->where('expiry_date <=', date('Y-m-d', strtotime('+30 days')))
                ->where('expiry_date >=', date('Y-m-d'))
                ->countAllResults();
            $openEnrollments   = $db->table('course_enrollments')->where('completion_status', 'Enrolled')->countAllResults();

            // Department headcount
            $deptHeadcount = $db->table('job_information')
                ->select('department, COUNT(*) as count')
                ->where('deleted_at IS NULL')
                ->groupBy('department')
                ->orderBy('count', 'DESC')
                ->get()->getResultArray();

            // Employment type breakdown
            $empTypeBreakdown = $db->table('job_information')
                ->select('employment_type, COUNT(*) as count')
                ->where('deleted_at IS NULL')
                ->groupBy('employment_type')
                ->get()->getResultArray();

            // Recent activity
            $recentActivity = $db->table('audit_logs')
                ->select('action, entity_type, change_reason, created_at')
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'total_active_employees'  => $totalEmployees,
                    'new_hires_this_month'    => $newHires,
                    'pending_reviews'         => $pendingReviews,
                    'expiring_documents_30d'  => $expiringDocuments,
                    'open_enrollments'        => $openEnrollments,
                    'department_headcount'    => $deptHeadcount,
                    'employment_type_breakdown' => $empTypeBreakdown,
                    'recent_activity'         => $recentActivity,
                    'generated_at'            => date('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::hrDashboard - ' . $e->getMessage());
            return $this->failServerError('Error fetching HR dashboard');
        }
    }

    /**
     * Admin dashboard
     * GET /dashboard/admin-dashboard
     */
    public function adminDashboard()
    {
        try {
            $db = $this->db;

            $totalEmployees = $db->table('employees')->where('deleted_at IS NULL')->countAllResults();
            $activeUsers    = $db->table('users')->where('is_active', 1)->countAllResults();
            $totalUsers     = $db->table('users')->countAllResults();

            // Employee status breakdown
            $statusBreakdown = $db->table('employees')
                ->select('status, COUNT(*) as count')
                ->where('deleted_at IS NULL')
                ->groupBy('status')
                ->get()->getResultArray();

            // Latest HRMS sync
            $latestSync = $db->table('sync_logs')
                ->orderBy('started_at', 'DESC')
                ->limit(1)
                ->get()->getRowArray();

            // Sync health (last 7 days)
            $syncStats = $db->table('sync_logs')
                ->select('status, COUNT(*) as count')
                ->where('started_at >=', date('Y-m-d', strtotime('-7 days')))
                ->groupBy('status')
                ->get()->getResultArray();

            // System config count
            $configCount = $db->table('system_configurations')->countAllResults();

            // Recent audit logs
            $recentAudit = $db->table('audit_logs')
                ->select('action, entity_type, change_reason, ip_address, created_at')
                ->orderBy('created_at', 'DESC')
                ->limit(20)
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'total_employees'     => $totalEmployees,
                    'active_users'        => $activeUsers,
                    'total_users'         => $totalUsers,
                    'employee_status'     => $statusBreakdown,
                    'system_config_count' => $configCount,
                    'latest_hrms_sync'    => $latestSync,
                    'sync_health_7d'      => $syncStats,
                    'recent_audit_logs'   => $recentAudit,
                    'generated_at'        => date('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard::adminDashboard - ' . $e->getMessage());
            return $this->failServerError('Error fetching admin dashboard');
        }
    }
}
