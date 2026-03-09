<?php

namespace Tests\Unit\Libraries;

use App\Libraries\JwtHandler;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for the JwtHandler library.
 * Tests RS256 token generation, validation, refresh, and expiry detection.
 * No database required — RSA keys are generated in-memory for each test run.
 */
class JwtHandlerTest extends CIUnitTestCase
{
    private JwtHandler $handler;

    /** @var string Generated RSA private key (PEM) */
    private static string $privateKey = '';

    /** @var string Generated RSA public key (PEM) */
    private static string $publicKey = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Generate a 2048-bit RSA key pair once for the entire test class.
        // On Windows without OPENSSL_CONF set at PHP start-up, key generation
        // may fail — fall back to the project's pre-generated key files.
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource !== false) {
            openssl_pkey_export($resource, self::$privateKey);
            $details         = openssl_pkey_get_details($resource);
            self::$publicKey = $details['key'];
        } else {
            $root           = realpath(__DIR__ . '/../../../');
            $privateKeyFile = $root . '/config/keys/private.pem';
            $publicKeyFile  = $root . '/config/keys/public.pem';

            if (file_exists($privateKeyFile) && file_exists($publicKeyFile)) {
                self::$privateKey = file_get_contents($privateKeyFile);
                self::$publicKey  = file_get_contents($publicKeyFile);
            } else {
                static::markTestSkipped('OpenSSL key generation failed and no fallback key files found.');
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Inject keys via environment variables (read by JwtHandler constructor)
        putenv('jwt.private_key=' . self::$privateKey);
        putenv('jwt.public_key=' . self::$publicKey);
        putenv('app.baseURL=http://localhost:8000');

        $this->handler = new JwtHandler();
    }

    protected function tearDown(): void
    {
        // Clean up env vars after each test
        putenv('jwt.private_key');
        putenv('jwt.public_key');
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // generateToken
    // -----------------------------------------------------------------------

    public function testGenerateTokenReturnsSuccessStatus(): void
    {
        $result = $this->handler->generateToken(['user_id' => 1, 'role' => 'employee']);

        $this->assertTrue($result['status']);
    }

    public function testGenerateTokenReturnsTokenString(): void
    {
        $result = $this->handler->generateToken(['user_id' => 42]);

        $this->assertArrayHasKey('token', $result);
        $this->assertIsString($result['token']);
        $this->assertNotEmpty($result['token']);
    }

    public function testGenerateTokenIncludesExpiresIn(): void
    {
        $result = $this->handler->generateToken(['user_id' => 1]);

        $this->assertArrayHasKey('expires_in', $result);
        $this->assertIsInt($result['expires_in']);
        $this->assertGreaterThan(0, $result['expires_in']);
    }

    public function testGenerateTokenReturnsBearer(): void
    {
        $result = $this->handler->generateToken(['user_id' => 1]);

        $this->assertEquals('Bearer', $result['type']);
    }

    public function testGenerateTokenWithCustomExpiry(): void
    {
        $result = $this->handler->generateToken(['user_id' => 1], 7200);

        $this->assertTrue($result['status']);
        $this->assertEquals(7200, $result['expires_in']);
    }

    // -----------------------------------------------------------------------
    // validateToken
    // -----------------------------------------------------------------------

    public function testValidateTokenReturnsSuccessForValidToken(): void
    {
        $generated = $this->handler->generateToken(['user_id' => 5, 'email' => 'test@example.com']);
        $result    = $this->handler->validateToken($generated['token']);

        $this->assertTrue($result['status']);
    }

    public function testValidateTokenReturnsCorrectData(): void
    {
        $payload   = ['user_id' => 7, 'email' => 'jane@example.com', 'role' => 'hr'];
        $generated = $this->handler->generateToken($payload);
        $result    = $this->handler->validateToken($generated['token']);

        $this->assertEquals(7, $result['data']['user_id']);
        $this->assertEquals('jane@example.com', $result['data']['email']);
        $this->assertEquals('hr', $result['data']['role']);
    }

    public function testValidateTokenStripsBearerPrefix(): void
    {
        $generated   = $this->handler->generateToken(['user_id' => 3]);
        $withBearer  = 'Bearer ' . $generated['token'];
        $result      = $this->handler->validateToken($withBearer);

        $this->assertTrue($result['status']);
    }

    public function testValidateTokenReturnsFailForInvalidToken(): void
    {
        $result = $this->handler->validateToken('this.is.not.a.valid.jwt');

        $this->assertFalse($result['status']);
    }

    public function testValidateTokenReturnsFailForEmptyString(): void
    {
        $result = $this->handler->validateToken('');

        $this->assertFalse($result['status']);
    }

    public function testValidateTokenReturnsFailForExpiredToken(): void
    {
        // Generate token that expired 1 second ago
        $generated = $this->handler->generateToken(['user_id' => 9], -1);
        $result    = $this->handler->validateToken($generated['token']);

        $this->assertFalse($result['status']);
        $this->assertEquals('token_expired', $result['reason']);
    }

    public function testValidateTokenReturnsClaimsArray(): void
    {
        $generated = $this->handler->generateToken(['user_id' => 11]);
        $result    = $this->handler->validateToken($generated['token']);

        $this->assertArrayHasKey('claims', $result);
        $this->assertIsArray($result['claims']);
    }

    // -----------------------------------------------------------------------
    // refreshToken
    // -----------------------------------------------------------------------

    public function testRefreshTokenReturnsNewToken(): void
    {
        $original = $this->handler->generateToken(['user_id' => 2, 'role' => 'manager']);
        $refreshed = $this->handler->refreshToken($original['token']);

        $this->assertTrue($refreshed['status']);
        $this->assertNotEmpty($refreshed['token']);
    }

    public function testRefreshTokenFailsForInvalidToken(): void
    {
        $result = $this->handler->refreshToken('invalid.token.here');

        $this->assertFalse($result['status']);
    }

    // -----------------------------------------------------------------------
    // generateRefreshToken
    // -----------------------------------------------------------------------

    public function testGenerateRefreshTokenSucceeds(): void
    {
        $result = $this->handler->generateRefreshToken(['user_id' => 1, 'type' => 'refresh']);

        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('token', $result);
    }

    public function testRefreshTokenHasLongerExpiryThanAccessToken(): void
    {
        $access  = $this->handler->generateToken(['user_id' => 1]);
        $refresh = $this->handler->generateRefreshToken(['user_id' => 1]);

        $this->assertGreaterThan($access['expires_in'], $refresh['expires_in']);
    }

    // -----------------------------------------------------------------------
    // isTokenExpiringSoon
    // -----------------------------------------------------------------------

    public function testNewTokenIsNotExpiringSoon(): void
    {
        $generated = $this->handler->generateToken(['user_id' => 1]);

        $this->assertFalse($this->handler->isTokenExpiringSoon($generated['token']));
    }

    public function testAlmostExpiredTokenIsExpiringSoon(): void
    {
        // Token that expires in 60 seconds (well within the 5-minute threshold)
        $generated = $this->handler->generateToken(['user_id' => 1], 60);

        $this->assertTrue($this->handler->isTokenExpiringSoon($generated['token']));
    }

    // -----------------------------------------------------------------------
    // extractClaims
    // -----------------------------------------------------------------------

    public function testExtractClaimsReturnsArrayForValidToken(): void
    {
        $generated = $this->handler->generateToken(['user_id' => 99]);
        $claims    = $this->handler->extractClaims($generated['token']);

        $this->assertIsArray($claims);
        $this->assertArrayHasKey('exp', $claims);
        $this->assertArrayHasKey('iat', $claims);
    }

    public function testExtractClaimsReturnsNullForInvalidToken(): void
    {
        $this->assertNull($this->handler->extractClaims('bad.token'));
    }
}
