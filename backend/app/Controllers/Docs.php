<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Docs extends Controller
{
    use ResponseTrait;

    /**
     * API documentation index
     * GET /docs/
     */
    public function index()
    {
        return $this->respond([
            'title'       => 'Employee Profile & HRMS System API',
            'version'     => '1.0.0',
            'description' => 'Comprehensive HR management and employee profile API',
            'base_url'    => base_url(),
            'links'       => [
                'authentication' => base_url('docs/api'),
                'endpoints'      => base_url('docs/endpoints'),
                'health'         => base_url('health'),
            ],
            'modules' => [
                'Authentication'      => 'JWT-based SSO authentication with HRMS integration',
                'Profile'             => 'Personal profile, addresses, emergency contacts, bank details, govt IDs, health records, family',
                'Job & Organization'  => 'Job info, employment history, org hierarchy, promotions, transfers',
                'Performance'         => 'Performance reviews, goals, feedback, ratings',
                'Talent Management'   => 'Skills, competencies, certifications, IDP, awards',
                'Learning & Dev'      => 'Courses, enrollments, training history, learning paths',
                'Compliance'          => 'Compliance documents, digital signatures',
                'Admin / HR'          => 'Employee CRUD, user management, HRMS sync, audit logs, configuration',
                'Analytics'           => 'Org structure, department stats, skill inventory, performance, training analytics',
                'Dashboard'           => 'Role-specific dashboards (Employee, Manager, HR, Admin)',
                'Reports'             => 'Employee summary, org structure, performance, training, compliance, headcount',
                'Search'              => 'Employee, skill, course and global search',
                'Upload'              => 'Profile picture, certificate, document, bulk employee CSV upload',
                'Export'              => 'Employee profile, org chart, performance report, skill audit export',
            ],
        ], 200);
    }

    /**
     * API authentication documentation
     * GET /docs/api
     */
    public function apiDocumentation()
    {
        return $this->respond([
            'authentication' => [
                'type'        => 'Bearer Token (JWT RS256)',
                'description' => 'All protected endpoints require a valid JWT token in the Authorization header.',
                'header'      => 'Authorization: Bearer <access_token>',
                'flow' => [
                    'step_1' => 'User logs in to HRMS system',
                    'step_2' => 'HRMS issues a signed JWT (RS256)',
                    'step_3' => 'Client sends JWT to POST /auth/sso-login',
                    'step_4' => 'API validates JWT, creates/syncs user, returns app access token + refresh token',
                    'step_5' => 'Client uses access token for all subsequent API calls',
                    'step_6' => 'When access token expires, use POST /auth/refresh with the refresh token',
                ],
                'token_expiry' => [
                    'access_token'  => '1 hour',
                    'refresh_token' => '7 days',
                ],
                'endpoints' => [
                    ['method' => 'POST', 'path' => '/auth/sso-login',  'auth' => false, 'description' => 'Exchange HRMS JWT for app access token'],
                    ['method' => 'POST', 'path' => '/auth/refresh',    'auth' => true,  'description' => 'Refresh access token using refresh token'],
                    ['method' => 'GET',  'path' => '/auth/verify',     'auth' => true,  'description' => 'Verify current access token'],
                    ['method' => 'POST', 'path' => '/auth/logout',     'auth' => true,  'description' => 'Invalidate session'],
                ],
            ],
            'roles' => [
                'admin'    => 'Full access to all modules and all employee data',
                'hr'       => 'Access to talent, learning, compliance, and reporting modules',
                'manager'  => 'Own profile + direct reports profiles + team analytics',
                'employee' => 'Own profile only',
                'system'   => 'Service account for HRMS sync operations',
            ],
            'response_format' => [
                'success' => ['data' => '...resource or list', 'message' => 'optional'],
                'error'   => ['messages' => '...validation errors or message', 'status' => 400],
            ],
            'http_status_codes' => [
                200 => 'OK - Successful GET/PUT',
                201 => 'Created - Resource created',
                400 => 'Bad Request - Validation or input error',
                401 => 'Unauthorized - Missing or invalid token',
                403 => 'Forbidden - Insufficient permissions',
                404 => 'Not Found - Resource does not exist',
                422 => 'Unprocessable Entity - Validation failed',
                500 => 'Internal Server Error',
            ],
        ], 200);
    }

    /**
     * Full endpoint reference listing
     * GET /docs/endpoints
     */
    public function endpoints()
    {
        $endpoints = [
            'Health' => [
                ['GET', '/health', false, 'Health check with DB status'],
            ],
            'Authentication' => [
                ['POST', '/auth/sso-login', false, 'SSO login with HRMS JWT'],
                ['POST', '/auth/refresh',   true,  'Refresh access token'],
                ['GET',  '/auth/verify',    true,  'Verify access token'],
                ['POST', '/auth/logout',    true,  'Logout and invalidate session'],
            ],
            'Profile' => [
                ['GET',    '/profile',                          true, 'Get own profile'],
                ['GET',    '/profile/{id}',                     true, 'View another employee profile (scoped)'],
                ['PUT',    '/profile',                          true, 'Update own profile'],
                ['GET',    '/profile/personal-details',         true, 'Get personal details'],
                ['PUT',    '/profile/personal-details',         true, 'Update personal details'],
                ['GET',    '/profile/addresses',                true, 'List addresses'],
                ['POST',   '/profile/addresses',                true, 'Add address'],
                ['PUT',    '/profile/addresses/{id}',           true, 'Update address'],
                ['DELETE', '/profile/addresses/{id}',           true, 'Delete address'],
                ['GET',    '/profile/emergency-contacts',       true, 'List emergency contacts'],
                ['POST',   '/profile/emergency-contacts',       true, 'Add emergency contact'],
                ['PUT',    '/profile/emergency-contacts/{id}',  true, 'Update emergency contact'],
                ['GET',    '/profile/govt-ids',                 true, 'List government IDs (masked)'],
                ['POST',   '/profile/govt-ids',                 true, 'Add government ID (encrypted)'],
                ['PUT',    '/profile/govt-ids/{id}',            true, 'Update government ID'],
                ['GET',    '/profile/bank-details',             true, 'List bank details (masked)'],
                ['POST',   '/profile/bank-details',             true, 'Add bank detail (encrypted)'],
                ['PUT',    '/profile/bank-details/{id}',        true, 'Update bank detail'],
                ['GET',    '/profile/health',                   true, 'Get health records'],
                ['PUT',    '/profile/health',                   true, 'Update health records'],
                ['GET',    '/profile/family-dependents',        true, 'List family dependents'],
                ['POST',   '/profile/family-dependents',        true, 'Add family dependent'],
                ['PUT',    '/profile/family-dependents/{id}',   true, 'Update family dependent'],
            ],
            'Job & Organization' => [
                ['GET',  '/job/information',          true, 'Get own job information'],
                ['GET',  '/job/information/{id}',     true, 'Get job info by employee ID'],
                ['GET',  '/job/history',              true, 'Get employment history'],
                ['GET',  '/job/history/{id}',         true, 'Get specific employment history record'],
                ['POST', '/job/history',              true, 'Add employment history'],
                ['GET',  '/job/org-hierarchy',        true, 'Get org hierarchy'],
                ['GET',  '/job/org-hierarchy/{id}',   true, 'Get hierarchy for specific node'],
                ['GET',  '/job/team-members',         true, 'List team members (manager view)'],
                ['GET',  '/job/reporting-structure',  true, 'Get reporting chain'],
                ['GET',  '/job/promotions',           true, 'List promotions'],
                ['POST', '/job/promotions',           true, 'Record promotion'],
                ['GET',  '/job/transfers',            true, 'List transfers'],
                ['POST', '/job/transfers',            true, 'Record transfer'],
            ],
            'Performance' => [
                ['GET',  '/performance/reviews',         true, 'List performance reviews'],
                ['GET',  '/performance/reviews/{id}',    true, 'Get specific review'],
                ['POST', '/performance/reviews',         true, 'Create performance review'],
                ['PUT',  '/performance/reviews/{id}',    true, 'Update review'],
                ['GET',  '/performance/goals',           true, 'List performance goals'],
                ['GET',  '/performance/goals/{id}',      true, 'Get specific goal'],
                ['POST', '/performance/goals',           true, 'Create goal'],
                ['PUT',  '/performance/goals/{id}',      true, 'Update goal'],
                ['GET',  '/performance/feedback',        true, 'List performance feedback'],
                ['GET',  '/performance/feedback/{id}',   true, 'Get specific feedback'],
                ['POST', '/performance/feedback',        true, 'Submit feedback'],
                ['GET',  '/performance/ratings',         true, 'Get performance ratings'],
                ['PUT',  '/performance/ratings/{id}',    true, 'Update rating'],
            ],
            'Talent Management' => [
                ['GET',  '/talent/skills',               true, 'Get skill catalog'],
                ['GET',  '/talent/skills/{id}',          true, 'Get specific skill'],
                ['POST', '/talent/skills',               true, 'Add skill to profile'],
                ['PUT',  '/talent/skills/{id}',          true, 'Update skill proficiency'],
                ['GET',  '/talent/competencies',         true, 'Get competency framework'],
                ['GET',  '/talent/competencies/{id}',    true, 'Get specific competency'],
                ['GET',  '/talent/my-competencies',      true, 'Get own competency assessments'],
                ['PUT',  '/talent/my-competencies/{id}', true, 'Update competency assessment'],
                ['GET',  '/talent/certifications',       true, 'List certifications'],
                ['GET',  '/talent/certifications/{id}',  true, 'Get specific certification'],
                ['POST', '/talent/certifications',       true, 'Add certification'],
                ['PUT',  '/talent/certifications/{id}',  true, 'Update certification'],
                ['GET',  '/talent/idp',                  true, 'Get Individual Development Plan'],
                ['POST', '/talent/idp',                  true, 'Create IDP'],
                ['PUT',  '/talent/idp/{id}',             true, 'Update IDP'],
                ['GET',  '/talent/awards',               true, 'List awards & recognition'],
                ['GET',  '/talent/awards/{id}',          true, 'Get specific award'],
            ],
            'Learning & Development' => [
                ['GET',  '/learning/courses',              true, 'Browse course catalog'],
                ['GET',  '/learning/courses/{id}',         true, 'Get course details'],
                ['GET',  '/learning/enrollments',          true, 'List course enrollments'],
                ['GET',  '/learning/enrollments/{id}',     true, 'Get specific enrollment'],
                ['POST', '/learning/enrollments',          true, 'Enroll in course'],
                ['PUT',  '/learning/enrollments/{id}',     true, 'Update enrollment status/progress'],
                ['GET',  '/learning/training-history',     true, 'List training history'],
                ['GET',  '/learning/training-history/{id}',true, 'Get specific training record'],
                ['GET',  '/learning/paths',                true, 'Get recommended learning paths'],
            ],
            'Compliance' => [
                ['GET',  '/compliance/documents',      true, 'List compliance documents'],
                ['GET',  '/compliance/documents/{id}', true, 'Get specific document'],
                ['POST', '/compliance/documents',      true, 'Upload compliance document'],
                ['PUT',  '/compliance/documents/{id}', true, 'Update document'],
                ['GET',  '/compliance/status',         true, 'Get compliance status overview'],
                ['POST', '/compliance/sign/{id}',      true, 'Digitally sign a document'],
            ],
            'Admin / HR' => [
                ['GET',    '/admin/employees',             true, 'List all employees (paginated, filterable)'],
                ['POST',   '/admin/employees',             true, 'Create employee'],
                ['PUT',    '/admin/employees/{id}',        true, 'Update employee'],
                ['DELETE', '/admin/employees/{id}',        true, 'Soft-delete employee'],
                ['GET',    '/admin/users',                 true, 'List all users'],
                ['POST',   '/admin/users',                 true, 'Create user account'],
                ['PUT',    '/admin/users/{id}',            true, 'Update user account'],
                ['POST',   '/admin/sync/employees',        true, 'Trigger HRMS employee sync'],
                ['GET',    '/admin/sync/status',           true, 'Get latest sync status'],
                ['GET',    '/admin/sync/logs',             true, 'Get sync logs'],
                ['GET',    '/admin/audit-logs',            true, 'Get audit logs (paginated, filterable)'],
                ['GET',    '/admin/audit-logs/{id}',       true, 'Get specific audit log'],
                ['GET',    '/admin/configuration',         true, 'Get system configuration'],
                ['PUT',    '/admin/configuration/{key}',   true, 'Update configuration value'],
            ],
            'Analytics' => [
                ['GET', '/analytics/org-structure',      true, 'Org structure analytics'],
                ['GET', '/analytics/department-stats',   true, 'Department headcount and breakdown'],
                ['GET', '/analytics/team-stats',         true, 'Team size and structure stats'],
                ['GET', '/analytics/performance-summary',true, 'Performance rating summary'],
                ['GET', '/analytics/review-statistics',  true, 'Review count and period breakdown'],
                ['GET', '/analytics/skill-inventory',    true, 'Skill catalog and proficiency distribution'],
                ['GET', '/analytics/competency-matrix',  true, 'Competency assessment heatmap'],
                ['GET', '/analytics/training-stats',     true, 'Training enrollment and completion rates'],
                ['GET', '/analytics/course-effectiveness',true,'Course pass rates and scores'],
                ['GET', '/analytics/hr-dashboard',       true, 'HR overview metrics'],
                ['GET', '/analytics/employee-engagement', true, 'Employee engagement rates'],
            ],
            'Dashboards' => [
                ['GET', '/dashboard/my-dashboard',      true, 'Current employee\'s dashboard'],
                ['GET', '/dashboard/manager-dashboard', true, 'Manager team dashboard'],
                ['GET', '/dashboard/hr-dashboard',      true, 'HR overview dashboard'],
                ['GET', '/dashboard/admin-dashboard',   true, 'Admin system dashboard'],
            ],
            'Reports' => [
                ['GET', '/reports/employee-summary', true, 'Employee list report (filterable, paginated)'],
                ['GET', '/reports/org-structure',    true, 'Org structure report grouped by department'],
                ['GET', '/reports/performance',      true, 'Performance reviews report'],
                ['GET', '/reports/training',         true, 'Training enrollment/completion report'],
                ['GET', '/reports/compliance',       true, 'Compliance document status report'],
                ['GET', '/reports/headcount',        true, 'Headcount by department/location/type'],
            ],
            'Search' => [
                ['GET', '/search/employees', true, 'Search employees by name, email, ID, department'],
                ['GET', '/search/skills',    true, 'Search skill catalog'],
                ['GET', '/search/courses',   true, 'Search course catalog'],
                ['GET', '/search/global',    true, 'Global search across employees, skills and courses'],
            ],
            'Upload' => [
                ['POST', '/upload/profile-picture', true, 'Upload employee profile picture (JPEG/PNG/WebP)'],
                ['POST', '/upload/certificate',     true, 'Upload certificate file (PDF/image)'],
                ['POST', '/upload/document',        true, 'Upload compliance document (PDF/image)'],
                ['POST', '/upload/bulk-employees',  true, 'Bulk import employees via CSV'],
            ],
            'Export' => [
                ['GET', '/export/employee-profile/{id}', true, 'Export full employee profile as JSON'],
                ['GET', '/export/org-chart',             true, 'Export org chart data as JSON'],
                ['GET', '/export/performance-report',    true, 'Export performance report as JSON'],
                ['GET', '/export/skill-audit',           true, 'Export skill audit as JSON'],
            ],
        ];

        // Format as structured array with method/path/auth/description
        $formatted = [];
        foreach ($endpoints as $group => $routes) {
            $formatted[$group] = array_map(fn($r) => [
                'method'      => $r[0],
                'path'        => $r[1],
                'auth'        => $r[2],
                'description' => $r[3],
            ], $routes);
        }

        return $this->respond([
            'total_endpoints' => array_sum(array_map('count', $formatted)),
            'groups'          => count($formatted),
            'endpoints'       => $formatted,
        ], 200);
    }
}
