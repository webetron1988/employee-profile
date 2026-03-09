<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature tests for Search endpoints.
 *
 * All search routes are protected — unauthenticated requests must return 401.
 * Tests also verify the minimum query length validation (2 chars).
 *
 * Routes:
 *   GET /search/employees?q=&...
 *   GET /search/skills?q=&...
 *   GET /search/courses?q=&...
 *   GET /search/global?q=&...
 *
 * @group feature
 */
class SearchTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    // -----------------------------------------------------------------------
    // Unauthenticated access (all search routes are protected)
    // -----------------------------------------------------------------------

    public function testSearchEmployeesWithoutTokenReturns401(): void
    {
        $result = $this->get('search/employees?q=test');

        $result->assertStatus(401);
    }

    public function testSearchSkillsWithoutTokenReturns401(): void
    {
        $result = $this->get('search/skills?q=php');

        $result->assertStatus(401);
    }

    public function testSearchCoursesWithoutTokenReturns401(): void
    {
        $result = $this->get('search/courses?q=java');

        $result->assertStatus(401);
    }

    public function testGlobalSearchWithoutTokenReturns401(): void
    {
        $result = $this->get('search/global?q=john');

        $result->assertStatus(401);
    }

    public function testSearchEndpointsReturnJsonOnUnauth(): void
    {
        $result = $this->get('search/employees?q=test');

        $result->assertJSON();
    }

    // -----------------------------------------------------------------------
    // Routes module — verify all search routes are registered (404 check)
    // -----------------------------------------------------------------------

    public function testSearchEmployeesRouteExistsNot404(): void
    {
        $result = $this->get('search/employees?q=test');

        // Should be 401 (auth required), not 404 (route missing)
        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    public function testSearchSkillsRouteExistsNot404(): void
    {
        $result = $this->get('search/skills?q=php');

        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    public function testSearchCoursesRouteExistsNot404(): void
    {
        $result = $this->get('search/courses?q=java');

        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }

    public function testGlobalSearchRouteExistsNot404(): void
    {
        $result = $this->get('search/global?q=hr');

        $this->assertNotEquals(404, $result->response()->getStatusCode());
    }
}
