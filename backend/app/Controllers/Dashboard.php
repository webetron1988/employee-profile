<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Dashboard extends Controller
{
    use ResponseTrait;

    /**
     * Get current user's dashboard
     * GET /dashboard/my-dashboard
     */
    public function myDashboard()
    {
        try {
            $userId = auth()->user()->id;

            return $this->respond([
                'message' => 'Dashboard endpoint - not yet implemented',
                'user_id' => $userId
            ], 200);
        } catch (\Throwable $e) {
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
            return $this->respond([
                'message' => 'Dashboard endpoint - not yet implemented'
            ], 200);
        } catch (\Throwable $e) {
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
            return $this->respond([
                'message' => 'Dashboard endpoint - not yet implemented'
            ], 200);
        } catch (\Throwable $e) {
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
            return $this->respond([
                'message' => 'Dashboard endpoint - not yet implemented'
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Error fetching admin dashboard');
        }
    }
}
