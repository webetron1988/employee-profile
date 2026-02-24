<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;

class NotFound extends Controller
{
    use ResponseTrait;

    /**
     * Handle 404 errors
     */
    public function handle($path = '')
    {
        return $this->failNotFound('The requested endpoint does not exist: ' . $path);
    }
}
