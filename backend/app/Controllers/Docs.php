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
            'title' => 'Employee Profile & HRMS System API',
            'version' => '1.0.0',
            'description' => 'Comprehensive HR management system API',
            'base_url' => base_url('api'),
            'docs' => [
                'authentication' => base_url('docs/api'),
                'endpoints' => base_url('docs/endpoints'),
                'postman_collection' => base_url('assets/postman/employee-profile-api.json')
            ]
        ], 200);
    }

    /**
     * API documentation
     * GET /docs/api
     */
    public function apiDocumentation()
    {
        return $this->respond([
            'message' => 'API documentation - not yet implemented'
        ], 200);
    }

    /**
     * API endpoints listing
     * GET /docs/endpoints
     */
    public function endpoints()
    {
        return $this->respond([
            'message' => 'Endpoints documentation - not yet implemented'
        ], 200);
    }
}
