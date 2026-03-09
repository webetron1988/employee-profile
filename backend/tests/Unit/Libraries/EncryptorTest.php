<?php

namespace Tests\Unit\Libraries;

use App\Libraries\Encryptor;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for the Encryptor library.
 * Tests AES-256-CBC encrypt/decrypt, field hashing, and data masking.
 * No database required.
 */
class EncryptorTest extends CIUnitTestCase
{
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        parent::setUp();

        // Provide a deterministic 32-char key for all tests
        putenv('encryption.key=testkey_employee_profile_app_256');
        putenv('app.baseURL=http://localhost:8000');

        $this->encryptor = new Encryptor();
    }

    // -----------------------------------------------------------------------
    // Encrypt / Decrypt
    // -----------------------------------------------------------------------

    public function testEncryptReturnsNonEmptyString(): void
    {
        $result = $this->encryptor->encrypt('hello world');

        $this->assertNotNull($result);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testEncryptedValueDiffersFromOriginal(): void
    {
        $original = 'sensitive data 123';
        $encrypted = $this->encryptor->encrypt($original);

        $this->assertNotEquals($original, $encrypted);
    }

    public function testDecryptRoundTrip(): void
    {
        $original  = 'John Doe SSN 123-45-6789';
        $encrypted = $this->encryptor->encrypt($original);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }

    public function testEncryptProducesDifferentOutputEachCall(): void
    {
        // Because a random IV is used every time
        $a = $this->encryptor->encrypt('same value');
        $b = $this->encryptor->encrypt('same value');

        $this->assertNotEquals($a, $b);

        // But both decrypt to the same plaintext
        $this->assertEquals($this->encryptor->decrypt($a), $this->encryptor->decrypt($b));
    }

    public function testEncryptReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->encryptor->encrypt(''));
    }

    public function testEncryptReturnsNullForNull(): void
    {
        $this->assertNull($this->encryptor->encrypt(null));
    }

    public function testDecryptReturnsNullForEmptyString(): void
    {
        $this->assertNull($this->encryptor->decrypt(''));
    }

    public function testDecryptReturnsNullForNull(): void
    {
        $this->assertNull($this->encryptor->decrypt(null));
    }

    public function testDecryptReturnsNullForGarbage(): void
    {
        // Random garbage that is not a valid encrypted payload
        $this->assertNull($this->encryptor->decrypt('this_is_not_encrypted_data!!@#$'));
    }

    // -----------------------------------------------------------------------
    // Hash field
    // -----------------------------------------------------------------------

    public function testHashFieldReturnsDeterministicHash(): void
    {
        $hash1 = $this->encryptor->hashField('test@example.com');
        $hash2 = $this->encryptor->hashField('test@example.com');

        $this->assertEquals($hash1, $hash2);
    }

    public function testHashFieldReturnsDifferentHashForDifferentInput(): void
    {
        $hash1 = $this->encryptor->hashField('alice@example.com');
        $hash2 = $this->encryptor->hashField('bob@example.com');

        $this->assertNotEquals($hash1, $hash2);
    }

    public function testHashFieldReturnsHexString(): void
    {
        $hash = $this->encryptor->hashField('employee123');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    // -----------------------------------------------------------------------
    // Mask sensitive data
    // -----------------------------------------------------------------------

    public function testMaskBankAccountShowsLastFourDigits(): void
    {
        $masked = $this->encryptor->maskSensitiveData('1234567890123456', 'bank_account');

        $this->assertStringEndsWith('3456', $masked);
        $this->assertStringStartsWith('*', $masked);
    }

    public function testMaskGovtIdShowsFirstTwoAndLastTwo(): void
    {
        $masked = $this->encryptor->maskSensitiveData('AB123456CD', 'govt_id');

        $this->assertStringStartsWith('AB', $masked);
        $this->assertStringEndsWith('CD', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    public function testMaskEmailShowsFirstCharAndDomain(): void
    {
        $masked = $this->encryptor->maskSensitiveData('john.doe@example.com', 'email');

        $this->assertStringStartsWith('j', $masked);
        $this->assertStringEndsWith('@example.com', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    public function testMaskPhoneShowsLastFourDigits(): void
    {
        $masked = $this->encryptor->maskSensitiveData('9876543210', 'phone');

        $this->assertStringEndsWith('3210', $masked);
        $this->assertStringStartsWith('*', $masked);
    }

    public function testMaskGenericShowsPartialData(): void
    {
        $masked = $this->encryptor->maskSensitiveData('ABCDEFGHIJ', 'generic');

        $this->assertStringContainsString('*', $masked);
        $this->assertNotEquals('ABCDEFGHIJ', $masked);
    }

    public function testMaskReturnsNullForEmptyInput(): void
    {
        $this->assertNull($this->encryptor->maskSensitiveData(''));
        $this->assertNull($this->encryptor->maskSensitiveData(null));
    }

    // -----------------------------------------------------------------------
    // Is encrypted
    // -----------------------------------------------------------------------

    public function testIsEncryptedReturnsTrueForEncryptedData(): void
    {
        $encrypted = $this->encryptor->encrypt('some sensitive value');

        $this->assertTrue($this->encryptor->isEncrypted($encrypted));
    }

    public function testIsEncryptedReturnsFalseForPlainText(): void
    {
        $this->assertFalse($this->encryptor->isEncrypted('plain text string'));
    }

    public function testIsEncryptedReturnsFalseForEmpty(): void
    {
        $this->assertFalse($this->encryptor->isEncrypted(''));
        $this->assertFalse($this->encryptor->isEncrypted(null));
    }
}
