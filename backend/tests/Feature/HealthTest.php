<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature tests for the Health Check endpoint.
 * GET /health — public route, no auth required.
 *
 * NOTE: The health endpoint performs a live DB query ("SELECT 1").
 * These tests require a working database connection.
 * Run: CREATE DATABASE employee_profile_test; (once)
 * Then run migrations: php spark migrate --env=testing
 *
 * @group feature
 * @group db
 */
class HealthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthEndpointReturns200(): void
    {
        $result = $this->get('health');

        $result->assertStatus(200);
    }

    public function testHealthEndpointReturnsJson(): void
    {
        $result = $this->get('health');

        $result->assertJSON();
    }

    public function testHealthEndpointHasStatusOk(): void
    {
        $result = $this->get('health');
        $body   = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('status', $body);
        $this->assertEquals('ok', $body['status']);
    }

    public function testHealthEndpointHasTimestamp(): void
    {
        $result = $this->get('health');
        $body   = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('timestamp', $body);
        $this->assertNotEmpty($body['timestamp']);
    }

    public function testHealthEndpointHasVersion(): void
    {
        $result = $this->get('health');
        $body   = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('version', $body);
        $this->assertEquals('1.0.0', $body['version']);
    }

    public function testHealthEndpointHasEnvironment(): void
    {
        $result = $this->get('health');
        $body   = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('environment', $body);
    }
}
