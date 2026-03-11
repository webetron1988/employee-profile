<?php

namespace App\Controllers;

use App\Models\TrainingHistory;
use App\Models\MentoringProgram;
use App\Models\SkillsGapAnalysis;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Learning extends Controller
{
    use ResponseTrait;

    protected $trainingHistory;
    protected $mentoringProgram;
    protected $skillsGap;

    public function __construct()
    {
        $this->trainingHistory = new TrainingHistory();
        $this->mentoringProgram = new MentoringProgram();
        $this->skillsGap = new SkillsGapAnalysis();
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
