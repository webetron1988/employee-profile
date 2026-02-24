<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class Health extends Controller
{
    use ResponseTrait;

    /**
     * Health check endpoint
     * GET /health
     */
    public function check()
    {
        try {
            $db = \Config\Database::connect();
            $db->simpleQuery("SELECT 1");

            return $this->respond([
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
                'environment' => ENVIRONMENT,
                'version' => '1.0.0'
            ], 200);
        } catch (\Throwable $e) {
            return $this->failServerError('Health check failed: ' . $e->getMessage());
        }
    }
}
