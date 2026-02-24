<?php

namespace App\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\TrainingHistory;
use App\Models\Skill;
use App\Models\Competency;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Learning extends Controller
{
    use ResponseTrait;

    protected $course;
    protected $enrollment;
    protected $trainingHistory;
    protected $skill;
    protected $competency;

    public function __construct()
    {
        $this->course = new Course();
        $this->enrollment = new CourseEnrollment();
        $this->trainingHistory = new TrainingHistory();
        $this->skill = new Skill();
        $this->competency = new Competency();
    }

    /**
     * Get all courses (catalog)
     * GET /learning/courses
     */
    public function getCourses()
    {
        try {
            $courses = $this->course
                ->orderBy('name', 'ASC')
                ->findAll();

            // Enhance with skill and competency details
            foreach ($courses as &$course) {
                if ($course['skill_id']) {
                    $course['skill'] = $this->skill->find($course['skill_id']);
                }
                if ($course['competency_id']) {
                    $course['competency'] = $this->competency->find($course['competency_id']);
                }
            }

            return $this->respond(['data' => $courses], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching courses');
        }
    }

    /**
     * Get specific course
     * GET /learning/courses/{id}
     */
    public function getCourseId($id)
    {
        try {
            $course = $this->course->find($id);

            if (!$course) {
                return $this->failNotFound('Course not found');
            }

            // Enhance details
            if ($course['skill_id']) {
                $course['skill'] = $this->skill->find($course['skill_id']);
            }
            if ($course['competency_id']) {
                $course['competency'] = $this->competency->find($course['competency_id']);
            }

            return $this->respond(['data' => $course], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching course');
        }
    }

    /**
     * Get user's course enrollments
     * GET /learning/enrollments
     */
    public function getEnrollments()
    {
        try {
            $userId = auth()->user()->id;

            $enrollments = $this->enrollment
                ->where('employee_id', $userId)
                ->orderBy('enrollment_date', 'DESC')
                ->findAll();

            // Enhance with course details
            foreach ($enrollments as &$enrollment) {
                $course = $this->course->find($enrollment['course_id']);
                $enrollment['course'] = $course;
            }

            return $this->respond(['data' => $enrollments], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching enrollments');
        }
    }

    /**
     * Get specific enrollment
     * GET /learning/enrollments/{id}
     */
    public function getEnrollmentId($id)
    {
        try {
            $userId = auth()->user()->id;
            $enrollment = $this->enrollment->find($id);

            if (!$enrollment || $enrollment['employee_id'] != $userId) {
                return $this->failForbidden('Enrollment not found or unauthorized');
            }

            // Add course details
            $course = $this->course->find($enrollment['course_id']);
            $enrollment['course'] = $course;

            return $this->respond(['data' => $enrollment], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching enrollment');
        }
    }

    /**
     * Create course enrollment
     * POST /learning/enrollments
     */
    public function createEnrollment()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);
            $data['employee_id'] = $userId;
            $data['enrollment_date'] = date('Y-m-d H:i:s');
            $data['completion_status'] = 'Enrolled';

            if ($this->enrollment->insert($data)) {
                return $this->respond(['message' => 'Enrollment created'], 201);
            }

            return $this->fail($this->enrollment->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating enrollment');
        }
    }

    /**
     * Update course enrollment (progress tracking)
     * PUT /learning/enrollments/{id}
     */
    public function updateEnrollment($id)
    {
        try {
            $userId = auth()->user()->id;
            $enrollment = $this->enrollment->find($id);

            if (!$enrollment || $enrollment['employee_id'] != $userId) {
                return $this->failForbidden('Enrollment not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->enrollment->update($id, $data)) {
                return $this->respond(['message' => 'Enrollment updated'], 200);
            }

            return $this->fail($this->enrollment->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating enrollment');
        }
    }

    /**
     * Get training history
     * GET /learning/training-history
     */
    public function getTrainingHistory()
    {
        try {
            $userId = auth()->user()->id;

            $history = $this->trainingHistory
                ->where('employee_id', $userId)
                ->orderBy('training_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $history], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching training history');
        }
    }

    /**
     * Get specific training history entry
     * GET /learning/training-history/{id}
     */
    public function getTrainingHistoryId($id)
    {
        try {
            $userId = auth()->user()->id;
            $history = $this->trainingHistory->find($id);

            if (!$history || $history['employee_id'] != $userId) {
                return $this->failForbidden('Training history not found or unauthorized');
            }

            return $this->respond(['data' => $history], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching training history');
        }
    }

    /**
     * Get learning paths
     * GET /learning/learning-paths
     */
    public function getLearningPaths()
    {
        try {
            $userId = auth()->user()->id;

            // Get all courses and group by competency
            $courses = $this->course->findAll();
            $paths = [];

            foreach ($courses as $course) {
                if ($course['competency_id']) {
                    if (!isset($paths[$course['competency_id']])) {
                        $competency = $this->competency->find($course['competency_id']);
                        $paths[$course['competency_id']] = [
                            'competency' => $competency,
                            'courses' => []
                        ];
                    }
                    $paths[$course['competency_id']]['courses'][] = $course;
                }
            }

            // Get user's enrollments
            $enrollments = $this->enrollment
                ->where('employee_id', $userId)
                ->findAll();

            $completedCourses = [];
            foreach ($enrollments as $enrollment) {
                if ($enrollment['completion_status'] === 'Completed') {
                    $completedCourses[] = $enrollment['course_id'];
                }
            }

            // Add progress to paths
            foreach ($paths as &$path) {
                $path['progress'] = 0;
                $completed = 0;

                foreach ($path['courses'] as &$course) {
                    $course['completed'] = in_array($course['id'], $completedCourses);
                    if ($course['completed']) {
                        $completed++;
                    }
                }

                if (count($path['courses']) > 0) {
                    $path['progress'] = round(($completed / count($path['courses'])) * 100);
                }
            }

            return $this->respond(['data' => array_values($paths)], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching learning paths');
        }
    }
}
