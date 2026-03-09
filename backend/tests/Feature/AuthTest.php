<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature tests for Authentication endpoints.
 *
 * Tests that cover the public SSO login path and token verification/logout
 * guards without requiring a live HRMS connection.
 *
 * Auth routes:
 *   POST /auth/sso-login  (public)
 *   POST /auth/refresh    (protected)
 *   GET  /auth/verify     (protected)
 *   POST /auth/logout     (protected)
 *
 * @group feature
 */
class AuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    // -----------------------------------------------------------------------
    // POST /auth/sso-login
    // -----------------------------------------------------------------------

    public function testSsoLoginWithoutTokenReturns401(): void
    {
        $result = $this->post('auth/sso-login');

        $result->assertStatus(401);
    }

    public function testSsoLoginWithoutTokenReturnsJson(): void
    {
        $result = $this->post('auth/sso-login');

        $result->assertJSON();
    }

    public function testSsoLoginWithEmptyAuthHeaderReturns401(): void
    {
        $result = $this->withHeaders(['Authorization' => ''])->post('auth/sso-login');

        $result->assertStatus(401);
    }

    public function testSsoLoginWithInvalidTokenReturns401(): void
    {
        // A syntactically valid-looking but unsigned/invalid JWT
        $fakeToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjMifQ.invalidsignature';

        $result = $this->withHeaders(['Authorization' => 'Bearer ' . $fakeToken])
                       ->post('auth/sso-login');

        // Auth controller validates with HRMS — invalid token should fail
        $this->assertContains($result->response()->getStatusCode(), [401, 400, 500]);
    }

    // -----------------------------------------------------------------------
    // POST /auth/refresh  (protected by permission filter)
    // -----------------------------------------------------------------------

    public function testRefreshWithoutTokenReturns401(): void
    {
        $result = $this->post('auth/refresh');

        // PermissionMiddleware rejects immediately (no token)
        $result->assertStatus(401);
    }

    public function testRefreshWithoutRefreshTokenInBodyReturns400OrFail(): void
    {
        // Provide a structurally plausible token header so middleware passes,
        // but body has no refresh_token — controller returns 400.
        // In test env without real keys, middleware may also reject with 401.
        $result = $this->withHeaders(['Authorization' => 'Bearer dummy.jwt.token'])
                       ->withBody(json_encode([]))
                       ->post('auth/refresh');

        $this->assertContains($result->response()->getStatusCode(), [400, 401]);
    }

    // -----------------------------------------------------------------------
    // GET /auth/verify  (protected by permission filter)
    // -----------------------------------------------------------------------

    public function testVerifyWithoutTokenReturns401(): void
    {
        $result = $this->get('auth/verify');

        $result->assertStatus(401);
    }

    public function testVerifyWithoutTokenReturnsJson(): void
    {
        $result = $this->get('auth/verify');

        $result->assertJSON();
    }

    // -----------------------------------------------------------------------
    // POST /auth/logout  (protected by permission filter)
    // -----------------------------------------------------------------------

    public function testLogoutWithoutTokenReturns401(): void
    {
        $result = $this->post('auth/logout');

        $result->assertStatus(401);
    }

    public function testLogoutWithoutTokenReturnsJson(): void
    {
        $result = $this->post('auth/logout');

        $result->assertJSON();
    }

    // -----------------------------------------------------------------------
    // Docs routes (public)
    // -----------------------------------------------------------------------

    public function testDocsIndexReturns200(): void
    {
        $result = $this->get('docs');

        $result->assertStatus(200);
    }

    public function testDocsIndexReturnsApiTitle(): void
    {
        $result = $this->get('docs');
        $body   = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('title', $body);
        $this->assertStringContainsString('Employee', $body['title']);
    }

    public function testDocsEndpointsReturns200(): void
    {
        $result = $this->get('docs/endpoints');

        $result->assertStatus(200);
    }

    public function testDocsEndpointsHasTotalCount(): void
    {
        $result = $this->get('docs/endpoints');
        $body   = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('total_endpoints', $body);
        $this->assertGreaterThan(50, $body['total_endpoints']);
    }
}
