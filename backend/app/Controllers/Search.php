<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Search extends Controller
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Search employees
     * GET /search/employees?q=keyword&department=HR&status=Active&page=1&per_page=20
     */
    public function searchEmployees()
    {
        try {
            $q          = trim($this->request->getVar('q') ?? '');
            $department = $this->request->getVar('department');
            $status     = $this->request->getVar('status') ?? 'Active';
            $page       = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage    = min(100, max(1, (int) ($this->request->getVar('per_page') ?? 20)));
            $offset     = ($page - 1) * $perPage;

            if (strlen($q) < 2) {
                return $this->fail('Search query must be at least 2 characters', 400);
            }

            $query = $this->db->table('employees e')
                ->select([
                    'e.id',
                    'e.employee_id',
                    'e.first_name',
                    'e.last_name',
                    'e.email',
                    'e.phone',
                    'e.status',
                    'e.profile_picture_url',
                    'ji.designation',
                    'ji.department',
                    'ji.location',
                    'ji.employment_type',
                ])
                ->join('job_information ji', 'ji.employee_id = e.id', 'left')
                ->where('e.deleted_at IS NULL')
                ->groupStart()
                    ->like('e.first_name', $q)
                    ->orLike('e.last_name', $q)
                    ->orLike('e.email', $q)
                    ->orLike('e.employee_id', $q)
                    ->orLike('e.phone', $q)
                    ->orLike('ji.designation', $q)
                    ->orLike('ji.department', $q)
                ->groupEnd();

            if ($status) {
                $query->where('e.status', $status);
            }
            if ($department) {
                $query->where('ji.department', $department);
            }

            $total   = $query->countAllResults(false);
            $results = $query->orderBy('e.last_name', 'ASC')->orderBy('e.first_name', 'ASC')->limit($perPage, $offset)->get()->getResultArray();

            return $this->respond([
                'data'     => $results,
                'query'    => $q,
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int) ceil($total / $perPage),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Search::searchEmployees - ' . $e->getMessage());
            return $this->failServerError('Error searching employees');
        }
    }

    /**
     * Search skills
     * GET /search/skills?q=keyword&category=Technical&page=1&per_page=20
     */
    public function searchSkills()
    {
        try {
            $q        = trim($this->request->getVar('q') ?? '');
            $category = $this->request->getVar('category');
            $page     = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage  = min(100, max(1, (int) ($this->request->getVar('per_page') ?? 20)));
            $offset   = ($page - 1) * $perPage;

            if (strlen($q) < 2) {
                return $this->fail('Search query must be at least 2 characters', 400);
            }

            // Count matching skills
            $countQuery = $this->db->table('skills')
                ->groupStart()
                    ->like('skill_name', $q)
                    ->orLike('skill_category', $q)
                    ->orLike('description', $q)
                ->groupEnd();

            if ($category) {
                $countQuery->where('skill_category', $category);
            }

            $total = $countQuery->countAllResults();

            // Fetch with employee count
            $resultsQuery = $this->db->table('skills s')
                ->select([
                    's.id',
                    's.skill_name',
                    's.skill_category',
                    's.skill_level',
                    's.description',
                    's.status',
                    'COUNT(es.id) as employee_count',
                    'AVG(es.years_of_experience) as avg_experience',
                ])
                ->join('employee_skills es', 'es.skill_id = s.id', 'left')
                ->groupStart()
                    ->like('s.skill_name', $q)
                    ->orLike('s.skill_category', $q)
                    ->orLike('s.description', $q)
                ->groupEnd()
                ->groupBy('s.id');

            if ($category) {
                $resultsQuery->where('s.skill_category', $category);
            }

            $results = $resultsQuery->orderBy('employee_count', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

            return $this->respond([
                'data'     => $results,
                'query'    => $q,
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int) ceil($total / $perPage),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Search::searchSkills - ' . $e->getMessage());
            return $this->failServerError('Error searching skills');
        }
    }

    /**
     * Search courses
     * GET /search/courses?q=keyword&type=Online&page=1&per_page=20
     */
    public function searchCourses()
    {
        try {
            $q       = trim($this->request->getVar('q') ?? '');
            $type    = $this->request->getVar('type');
            $page    = max(1, (int) ($this->request->getVar('page') ?? 1));
            $perPage = min(100, max(1, (int) ($this->request->getVar('per_page') ?? 20)));
            $offset  = ($page - 1) * $perPage;

            if (strlen($q) < 2) {
                return $this->fail('Search query must be at least 2 characters', 400);
            }

            // Count
            $countQuery = $this->db->table('courses')
                ->groupStart()
                    ->like('course_name', $q)
                    ->orLike('course_code', $q)
                    ->orLike('provider', $q)
                    ->orLike('description', $q)
                ->groupEnd();

            if ($type) {
                $countQuery->where('course_type', $type);
            }

            $total = $countQuery->countAllResults();

            // Fetch with enrollment count
            $resultsQuery = $this->db->table('courses c')
                ->select([
                    'c.id',
                    'c.course_name',
                    'c.course_code',
                    'c.course_type',
                    'c.provider',
                    'c.duration_hours',
                    'c.cost',
                    'c.status',
                    's.skill_name',
                    'COUNT(ce.id) as enrollment_count',
                    'AVG(ce.score) as avg_score',
                ])
                ->join('skills s', 's.id = c.skill_id', 'left')
                ->join('course_enrollments ce', 'ce.course_id = c.id', 'left')
                ->groupStart()
                    ->like('c.course_name', $q)
                    ->orLike('c.course_code', $q)
                    ->orLike('c.provider', $q)
                    ->orLike('c.description', $q)
                ->groupEnd()
                ->groupBy('c.id');

            if ($type) {
                $resultsQuery->where('c.course_type', $type);
            }

            $results = $resultsQuery->orderBy('enrollment_count', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

            return $this->respond([
                'data'     => $results,
                'query'    => $q,
                'total'    => $total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int) ceil($total / $perPage),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Search::searchCourses - ' . $e->getMessage());
            return $this->failServerError('Error searching courses');
        }
    }

    /**
     * Global search across employees, skills and courses
     * GET /search/global?q=keyword&limit=5
     */
    public function globalSearch()
    {
        try {
            $q     = trim($this->request->getVar('q') ?? '');
            $limit = min(20, max(1, (int) ($this->request->getVar('limit') ?? 5)));

            if (strlen($q) < 2) {
                return $this->fail('Search query must be at least 2 characters', 400);
            }

            // Employees
            $employees = $this->db->table('employees e')
                ->select('e.id, e.employee_id, e.first_name, e.last_name, e.email, e.profile_picture_url, ji.designation, ji.department, "employee" as result_type')
                ->join('job_information ji', 'ji.employee_id = e.id', 'left')
                ->where('e.deleted_at IS NULL')
                ->where('e.status', 'Active')
                ->groupStart()
                    ->like('e.first_name', $q)
                    ->orLike('e.last_name', $q)
                    ->orLike('e.email', $q)
                    ->orLike('e.employee_id', $q)
                ->groupEnd()
                ->limit($limit)
                ->get()->getResultArray();

            // Skills
            $skills = $this->db->table('skills')
                ->select('id, skill_name, skill_category, skill_level, "skill" as result_type')
                ->groupStart()
                    ->like('skill_name', $q)
                    ->orLike('skill_category', $q)
                ->groupEnd()
                ->limit($limit)
                ->get()->getResultArray();

            // Courses
            $courses = $this->db->table('courses')
                ->select('id, course_name, course_code, course_type, provider, "course" as result_type')
                ->groupStart()
                    ->like('course_name', $q)
                    ->orLike('course_code', $q)
                    ->orLike('provider', $q)
                ->groupEnd()
                ->limit($limit)
                ->get()->getResultArray();

            return $this->respond([
                'data' => [
                    'employees' => $employees,
                    'skills'    => $skills,
                    'courses'   => $courses,
                ],
                'query' => $q,
                'total' => count($employees) + count($skills) + count($courses),
            ], 200);
        } catch (\Throwable $e) {
            log_message('error', 'Search::globalSearch - ' . $e->getMessage());
            return $this->failServerError('Error performing global search');
        }
    }
}
