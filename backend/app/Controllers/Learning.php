<?php

namespace App\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\TrainingHistory;
use App\Models\MentoringProgram;
use App\Models\SkillsGapAnalysis;
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
    protected $mentoringProgram;
    protected $skillsGap;
    protected $skill;
    protected $competency;

    public function __construct()
    {
        $this->course = new Course();
        $this->enrollment = new CourseEnrollment();
        $this->trainingHistory = new TrainingHistory();
        $this->mentoringProgram = new MentoringProgram();
        $this->skillsGap = new SkillsGapAnalysis();
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
                ->orderBy('course_name', 'ASC')
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
     * Create training history entry
     * POST /learning/training-history
     */
    public function createTrainingHistory()
    {
        try {
            $userId = auth()->user()->id;
            $data   = $this->request->getJSON(true);

            $required = ['training_name', 'training_provider'];
            foreach ($required as $f) {
                if (empty($data[$f])) {
                    return $this->failValidationErrors("$f is required");
                }
            }

            $record = [
                'employee_id'          => $userId,
                'training_name'        => $data['training_name'],
                'training_type'        => $data['training_type'] ?? 'Other',
                'training_provider'    => $data['training_provider'],
                'training_date'        => $data['training_date'] ?? date('Y-m-d'),
                'duration_hours'       => !empty($data['duration_hours']) ? (int) $data['duration_hours'] : null,
                'location'             => $data['location'] ?? null,
                'mode'                 => $data['mode'] ?? 'Online',
                'cost'                 => isset($data['cost']) ? (float) $data['cost'] : null,
                'trainer_name'         => $data['trainer_name'] ?? null,
                'assessment_score'     => isset($data['assessment_score']) ? (int) $data['assessment_score'] : null,
                'certificate_obtained' => !empty($data['certificate_obtained']) ? 1 : 0,
                'certificate_url'      => $data['certificate_url'] ?? null,
                'feedback'             => $data['feedback'] ?? null,
            ];

            $id = $this->trainingHistory->insert($record);

            return $this->respond([
                'message' => 'Training record created',
                'data'    => $this->trainingHistory->find($id),
            ], 201);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating training history');
        }
    }

    /**
     * Update training history entry
     * PUT /learning/training-history/{id}
     */
    public function updateTrainingHistory($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->trainingHistory->find($id);

            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Not found or unauthorized');
            }

            $data     = $this->request->getJSON(true);
            $allowed  = ['training_name', 'training_type', 'training_provider', 'training_date', 'duration_hours', 'location', 'mode', 'cost', 'trainer_name', 'assessment_score', 'certificate_obtained', 'certificate_url', 'feedback'];
            $updateData = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    $updateData[$f] = $data[$f];
                }
            }
            if (!empty($updateData)) {
                $this->trainingHistory->update($id, $updateData);
            }

            return $this->respond([
                'message' => 'Training record updated',
                'data'    => $this->trainingHistory->find($id),
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating training history');
        }
    }

    /**
     * Delete training history entry
     * DELETE /learning/training-history/{id}
     */
    public function deleteTrainingHistory($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->trainingHistory->find($id);

            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Not found or unauthorized');
            }

            $this->trainingHistory->delete($id);

            return $this->respond(['message' => 'Training record deleted']);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting training history');
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

    /**
     * Get mentoring programs
     * GET /learning/mentoring
     */
    public function getMentoring()
    {
        try {
            $userId = auth()->user()->id;
            $programs = $this->mentoringProgram
                ->where('employee_id', $userId)
                ->orderBy('start_date', 'DESC')
                ->findAll();
            return $this->respond(['data' => $programs], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching mentoring programs');
        }
    }

    /**
     * Create mentoring program
     * POST /learning/mentoring
     */
    public function createMentoring()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);

            $required = ['program_name', 'role', 'partner_name'];
            foreach ($required as $f) {
                if (empty($data[$f])) {
                    return $this->failValidationErrors("$f is required");
                }
            }

            $record = [
                'employee_id'  => $userId,
                'program_name' => $data['program_name'],
                'role'         => $data['role'],
                'partner_name' => $data['partner_name'],
                'status'       => $data['status'] ?? 'Active',
                'start_date'   => $data['start_date'] ?? null,
                'end_date'     => $data['end_date'] ?? null,
                'description'  => $data['description'] ?? null,
                'goals'        => $data['goals'] ?? null,
                'frequency'    => $data['frequency'] ?? null,
            ];

            $id = $this->mentoringProgram->insert($record);
            return $this->respond([
                'message' => 'Mentoring program created',
                'data'    => $this->mentoringProgram->find($id),
            ], 201);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating mentoring program');
        }
    }

    /**
     * Update mentoring program
     * PUT /learning/mentoring/{id}
     */
    public function updateMentoring($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->mentoringProgram->find($id);

            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Not found or unauthorized');
            }

            $data = $this->request->getJSON(true);
            $allowed = ['program_name', 'role', 'partner_name', 'status', 'start_date', 'end_date', 'description', 'goals', 'frequency'];
            $updateData = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    $updateData[$f] = $data[$f];
                }
            }

            if (!empty($updateData)) {
                $this->mentoringProgram->update($id, $updateData);
            }

            return $this->respond([
                'message' => 'Mentoring program updated',
                'data'    => $this->mentoringProgram->find($id),
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating mentoring program');
        }
    }

    /**
     * Delete mentoring program
     * DELETE /learning/mentoring/{id}
     */
    public function deleteMentoring($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->mentoringProgram->find($id);

            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Not found or unauthorized');
            }

            $this->mentoringProgram->delete($id);
            return $this->respond(['message' => 'Mentoring program deleted']);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting mentoring program');
        }
    }

    /**
     * Get skills gap analysis
     * GET /learning/skills-gap
     */
    public function getSkillsGap()
    {
        try {
            $userId = auth()->user()->id;
            $items = $this->skillsGap
                ->where('employee_id', $userId)
                ->orderBy('target_role', 'ASC')
                ->orderBy('skill_name', 'ASC')
                ->findAll();
            return $this->respond(['data' => $items], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching skills gap analysis');
        }
    }

    /**
     * Create skills gap entry
     * POST /learning/skills-gap
     */
    public function createSkillsGap()
    {
        try {
            $userId = auth()->user()->id;
            $data = $this->request->getJSON(true);

            $required = ['skill_name', 'target_role'];
            foreach ($required as $f) {
                if (empty($data[$f])) {
                    return $this->failValidationErrors("$f is required");
                }
            }

            $record = [
                'employee_id'   => $userId,
                'target_role'   => $data['target_role'],
                'skill_name'    => $data['skill_name'],
                'current_level' => isset($data['current_level']) ? (int) $data['current_level'] : 0,
                'target_level'  => isset($data['target_level']) ? (int) $data['target_level'] : 0,
                'priority'      => $data['priority'] ?? 'Medium',
                'notes'         => $data['notes'] ?? null,
            ];

            $id = $this->skillsGap->insert($record);
            return $this->respond([
                'message' => 'Skills gap entry created',
                'data'    => $this->skillsGap->find($id),
            ], 201);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating skills gap entry');
        }
    }

    /**
     * Update skills gap entry
     * PUT /learning/skills-gap/{id}
     */
    public function updateSkillsGap($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->skillsGap->find($id);

            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Not found or unauthorized');
            }

            $data = $this->request->getJSON(true);
            $allowed = ['target_role', 'skill_name', 'current_level', 'target_level', 'priority', 'notes'];
            $updateData = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    $updateData[$f] = $data[$f];
                }
            }

            if (!empty($updateData)) {
                $this->skillsGap->update($id, $updateData);
            }

            return $this->respond([
                'message' => 'Skills gap entry updated',
                'data'    => $this->skillsGap->find($id),
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating skills gap entry');
        }
    }

    /**
     * Delete skills gap entry
     * DELETE /learning/skills-gap/{id}
     */
    public function deleteSkillsGap($id)
    {
        try {
            $userId = auth()->user()->id;
            $record = $this->skillsGap->find($id);

            if (!$record || $record['employee_id'] != $userId) {
                return $this->failForbidden('Not found or unauthorized');
            }

            $this->skillsGap->delete($id);
            return $this->respond(['message' => 'Skills gap entry deleted']);
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting skills gap entry');
        }
    }
}
