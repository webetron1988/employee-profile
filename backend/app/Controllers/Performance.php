<?php

namespace App\Controllers;

use App\Models\PerformanceReview;
use App\Models\PerformanceGoal;
use App\Models\PerformanceFeedback;
use App\Models\User;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Performance extends Controller
{
    use ResponseTrait;

    protected $performanceReview;
    protected $performanceGoal;
    protected $performanceFeedback;
    protected $employee;

    public function __construct()
    {
        $this->performanceReview = new PerformanceReview();
        $this->performanceGoal = new PerformanceGoal();
        $this->performanceFeedback = new PerformanceFeedback();
        $this->employee = new User();
    }

    /**
     * Get performance reviews for current user
     * GET /performance/reviews
     */
    public function getReviews()
    {
        try {
            $userId = auth()->user()->id;

            // Get reviews where user is the subject
            $reviews = $this->performanceReview
                ->where('employee_id', $userId)
                ->orderBy('review_date', 'DESC')
                ->findAll();

            // Enhance with reviewer details
            foreach ($reviews as &$review) {
                if ($review['reviewer_id']) {
                    $reviewer = $this->employee->find($review['reviewer_id']);
                    $review['reviewer'] = $reviewer ? [
                        'id' => $reviewer['id'],
                        'name' => $reviewer['first_name'] . ' ' . $reviewer['last_name']
                    ] : null;
                }
            }

            return $this->respond(['data' => $reviews], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching performance reviews');
        }
    }

    /**
     * Get specific performance review
     * GET /performance/reviews/{id}
     */
    public function getReviewId($id)
    {
        try {
            $review = $this->performanceReview->find($id);

            if (!$review) {
                return $this->failNotFound('Performance review not found');
            }

            // Add reviewer details
            if ($review['reviewer_id']) {
                $reviewer = $this->employee->find($review['reviewer_id']);
                $review['reviewer'] = $reviewer ? [
                    'id' => $reviewer['id'],
                    'name' => $reviewer['first_name'] . ' ' . $reviewer['last_name']
                ] : null;
            }

            return $this->respond(['data' => $review], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching performance review');
        }
    }

    /**
     * Create performance review
     * POST /performance/reviews
     */
    public function createReview()
    {
        try {
            $data = $this->request->getJSON(true);
            $userId = auth()->user()->id;
            $data['reviewer_id'] = $userId;

            if ($this->performanceReview->insert($data)) {
                return $this->respond(['message' => 'Performance review created'], 201);
            }

            return $this->fail($this->performanceReview->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating performance review');
        }
    }

    /**
     * Update performance review
     * PUT /performance/reviews/{id}
     */
    public function updateReview($id)
    {
        try {
            $review = $this->performanceReview->find($id);

            if (!$review) {
                return $this->failNotFound('Performance review not found');
            }

            $data = $this->request->getJSON(true);

            if ($this->performanceReview->update($id, $data)) {
                return $this->respond(['message' => 'Performance review updated'], 200);
            }

            return $this->fail($this->performanceReview->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating performance review');
        }
    }

    /**
     * Get performance goals for current user
     * GET /performance/goals
     */
    public function getGoals()
    {
        try {
            $userId = auth()->user()->id;

            $goals = $this->performanceGoal
                ->where('employee_id', $userId)
                ->orderBy('end_date', 'ASC')
                ->findAll();

            return $this->respond(['data' => $goals], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching performance goals');
        }
    }

    /**
     * Get specific performance goal
     * GET /performance/goals/{id}
     */
    public function getGoalId($id)
    {
        try {
            $goal = $this->performanceGoal->find($id);

            if (!$goal) {
                return $this->failNotFound('Performance goal not found');
            }

            return $this->respond(['data' => $goal], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching performance goal');
        }
    }

    /**
     * Create performance goal
     * POST /performance/goals
     */
    public function createGoal()
    {
        try {
            $data = $this->request->getJSON(true);
            $userId = auth()->user()->id;
            $data['employee_id'] = $userId;

            if ($this->performanceGoal->insert($data)) {
                return $this->respond(['message' => 'Performance goal created'], 201);
            }

            return $this->fail($this->performanceGoal->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating performance goal');
        }
    }

    /**
     * Update performance goal
     * PUT /performance/goals/{id}
     */
    public function updateGoal($id)
    {
        try {
            $userId = auth()->user()->id;
            $goal = $this->performanceGoal->find($id);

            if (!$goal || $goal['employee_id'] != $userId) {
                return $this->failForbidden('Performance goal not found or unauthorized');
            }

            $data = $this->request->getJSON(true);

            if ($this->performanceGoal->update($id, $data)) {
                return $this->respond(['message' => 'Performance goal updated'], 200);
            }

            return $this->fail($this->performanceGoal->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating performance goal');
        }
    }

    /**
     * Delete performance goal
     * DELETE /performance/goals/{id}
     */
    public function deleteGoal($id)
    {
        try {
            $userId = auth()->user()->id;
            $goal = $this->performanceGoal->find($id);

            if (!$goal || $goal['employee_id'] != $userId) {
                return $this->failForbidden('Performance goal not found or unauthorized');
            }

            if ($this->performanceGoal->delete($id)) {
                return $this->respond(['message' => 'Performance goal deleted'], 200);
            }

            return $this->failServerError('Error deleting performance goal');
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting performance goal');
        }
    }

    /**
     * Get performance feedback for current user
     * GET /performance/feedback
     */
    public function getFeedback()
    {
        try {
            $userId = auth()->user()->id;

            // Get feedback received by user OR given by user (as reviewer)
            $feedback = $this->performanceFeedback
                ->groupStart()
                    ->where('employee_id', $userId)
                    ->orWhere('reviewer_id', $userId)
                ->groupEnd()
                ->orderBy('created_at', 'DESC')
                ->findAll();

            // Enhance with reviewer details
            foreach ($feedback as &$item) {
                if (!empty($item['reviewer_id'])) {
                    $reviewer = $this->employee->find($item['reviewer_id']);
                    $item['reviewer'] = $reviewer ? [
                        'id' => $reviewer['id'],
                        'name' => $reviewer['first_name'] . ' ' . $reviewer['last_name']
                    ] : null;
                }
                // Flag if current user is the reviewer (for frontend edit/delete permission)
                $item['is_own_review'] = ((int)$item['reviewer_id'] === (int)$userId) ? 1 : 0;
            }

            return $this->respond(['data' => $feedback], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching performance feedback');
        }
    }

    /**
     * Get specific feedback
     * GET /performance/feedback/{id}
     */
    public function getFeedbackId($id)
    {
        try {
            $feedback = $this->performanceFeedback->find($id);

            if (!$feedback) {
                return $this->failNotFound('Feedback not found');
            }

            return $this->respond(['data' => $feedback], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching feedback');
        }
    }

    /**
     * Create performance feedback
     * POST /performance/feedback
     */
    public function createFeedback()
    {
        try {
            $data = $this->request->getJSON(true);
            $userId = auth()->user()->id;
            $data['reviewer_id'] = $userId;
            // Ensure employee_id is set from auth if not provided
            if (empty($data['employee_id'])) {
                $data['employee_id'] = $userId;
            }
            // Remove null/empty rating to avoid validation issues
            if (array_key_exists('rating', $data) && ($data['rating'] === null || $data['rating'] === '')) {
                unset($data['rating']);
            }

            if ($this->performanceFeedback->insert($data)) {
                return $this->respond(['message' => 'Feedback created', 'id' => $this->performanceFeedback->getInsertID()], 201);
            }

            return $this->respond(['status' => 422, 'error' => 422, 'messages' => ['error' => implode(', ', $this->performanceFeedback->errors())]], 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error creating feedback: ' . $e->getMessage());
        }
    }

    /**
     * Update performance feedback
     * PUT /performance/feedback/{id}
     */
    public function updateFeedback($id)
    {
        try {
            $feedback = $this->performanceFeedback->find($id);

            if (!$feedback) {
                return $this->failNotFound('Feedback not found');
            }

            $userId = auth()->user()->id;
            $userRole = auth()->user()->role;

            // Only admin/hr/manager can edit any, employees can only edit own
            if (!in_array($userRole, ['admin', 'hr', 'manager']) && (int)$feedback['reviewer_id'] !== (int)$userId) {
                return $this->failForbidden('You can only edit your own feedback');
            }

            $data = $this->request->getJSON(true);
            // Remove null/empty rating to avoid validation issues
            if (array_key_exists('rating', $data) && ($data['rating'] === null || $data['rating'] === '')) {
                unset($data['rating']);
            }

            if ($this->performanceFeedback->update($id, $data)) {
                return $this->respond(['message' => 'Feedback updated'], 200);
            }

            return $this->respond(['status' => 422, 'error' => 422, 'messages' => ['error' => implode(', ', $this->performanceFeedback->errors())]], 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating feedback');
        }
    }

    /**
     * Delete performance feedback
     * DELETE /performance/feedback/{id}
     */
    public function deleteFeedback($id)
    {
        try {
            $feedback = $this->performanceFeedback->find($id);

            if (!$feedback) {
                return $this->failNotFound('Feedback not found');
            }

            $userId = auth()->user()->id;
            $userRole = auth()->user()->role;

            // Only admin/hr can delete any, manager/employee can delete own
            if (!in_array($userRole, ['admin', 'hr']) && (int)$feedback['reviewer_id'] !== (int)$userId) {
                return $this->failForbidden('You can only delete your own feedback');
            }

            if ($this->performanceFeedback->delete($id)) {
                return $this->respond(['message' => 'Feedback deleted'], 200);
            }

            return $this->failServerError('Error deleting feedback');
        } catch (\Throwable $e) {
            return $this->failServerError('Error deleting feedback');
        }
    }

    /**
     * Get performance ratings
     * GET /performance/ratings
     */
    public function getRatings()
    {
        try {
            $userId = auth()->user()->id;

            $ratings = $this->performanceReview
                ->where('employee_id', $userId)
                ->select(['id', 'overall_rating', 'review_period', 'review_date', 'performance_status', 'approval_status'])
                ->orderBy('review_date', 'DESC')
                ->findAll();

            return $this->respond(['data' => $ratings], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching ratings');
        }
    }

    /**
     * Update rating for specific review
     * PUT /performance/ratings/{id}
     */
    public function updateRating($id)
    {
        try {
            $review = $this->performanceReview->find($id);

            if (!$review) {
                return $this->failNotFound('Review not found');
            }

            $data = $this->request->getJSON(true);

            if ($this->performanceReview->update($id, $data)) {
                return $this->respond(['message' => 'Rating updated'], 200);
            }

            return $this->fail($this->performanceReview->errors(), 422);
        } catch (\Throwable $e) {
            return $this->failServerError('Error updating rating');
        }
    }
}
