<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Unit tests for the Employee model.
 * Verifies table name, primary key, allowed fields, validation rules,
 * soft-delete configuration, and relationship method existence.
 * No database connection required.
 */
class EmployeeModelTest extends CIUnitTestCase
{
    private Employee $model;
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new Employee();
        $this->ref   = new ReflectionClass($this->model);
    }

    // -----------------------------------------------------------------------
    // Table / Key / Return type
    // -----------------------------------------------------------------------

    public function testTableIsEmployees(): void
    {
        $prop = $this->ref->getProperty('table');
        $prop->setAccessible(true);

        $this->assertEquals('employees', $prop->getValue($this->model));
    }

    public function testPrimaryKeyIsId(): void
    {
        $prop = $this->ref->getProperty('primaryKey');
        $prop->setAccessible(true);

        $this->assertEquals('id', $prop->getValue($this->model));
    }

    public function testReturnTypeIsArray(): void
    {
        $prop = $this->ref->getProperty('returnType');
        $prop->setAccessible(true);

        $this->assertEquals('array', $prop->getValue($this->model));
    }

    // -----------------------------------------------------------------------
    // Soft deletes
    // -----------------------------------------------------------------------

    public function testSoftDeletesEnabled(): void
    {
        $prop = $this->ref->getProperty('useSoftDeletes');
        $prop->setAccessible(true);

        $this->assertTrue($prop->getValue($this->model));
    }

    // -----------------------------------------------------------------------
    // Allowed fields
    // -----------------------------------------------------------------------

    public function testAllowedFieldsContainsCoreFields(): void
    {
        $prop = $this->ref->getProperty('allowedFields');
        $prop->setAccessible(true);
        $fields = $prop->getValue($this->model);

        $required = ['employee_id', 'email', 'first_name', 'last_name', 'status'];
        foreach ($required as $field) {
            $this->assertContains($field, $fields, "Missing required field: $field");
        }
    }

    public function testAllowedFieldsContainsHrmsId(): void
    {
        $prop = $this->ref->getProperty('allowedFields');
        $prop->setAccessible(true);
        $fields = $prop->getValue($this->model);

        $this->assertContains('hrms_employee_id', $fields);
    }

    public function testAllowedFieldsContainsTimestamps(): void
    {
        $prop = $this->ref->getProperty('allowedFields');
        $prop->setAccessible(true);
        $fields = $prop->getValue($this->model);

        $this->assertContains('created_at', $fields);
        $this->assertContains('updated_at', $fields);
        $this->assertContains('deleted_at', $fields);
    }

    public function testAllowedFieldsContainsProfilePicture(): void
    {
        $prop = $this->ref->getProperty('allowedFields');
        $prop->setAccessible(true);
        $fields = $prop->getValue($this->model);

        $this->assertContains('profile_picture_url', $fields);
    }

    // -----------------------------------------------------------------------
    // Validation rules
    // -----------------------------------------------------------------------

    public function testValidationRulesRequireEmail(): void
    {
        $prop = $this->ref->getProperty('validationRules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($this->model);

        $this->assertArrayHasKey('email', $rules);
        $this->assertStringContainsString('required', $rules['email']);
        $this->assertStringContainsString('valid_email', $rules['email']);
    }

    public function testValidationRulesRequireFirstName(): void
    {
        $prop = $this->ref->getProperty('validationRules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($this->model);

        $this->assertArrayHasKey('first_name', $rules);
        $this->assertStringContainsString('required', $rules['first_name']);
    }

    public function testValidationRulesRequireLastName(): void
    {
        $prop = $this->ref->getProperty('validationRules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($this->model);

        $this->assertArrayHasKey('last_name', $rules);
        $this->assertStringContainsString('required', $rules['last_name']);
    }

    public function testValidationRulesRequireUniqueEmail(): void
    {
        $prop = $this->ref->getProperty('validationRules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($this->model);

        $this->assertStringContainsString('is_unique', $rules['email']);
    }

    public function testValidationRulesRequireHrmsEmployeeId(): void
    {
        $prop = $this->ref->getProperty('validationRules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($this->model);

        $this->assertArrayHasKey('hrms_employee_id', $rules);
        $this->assertStringContainsString('required', $rules['hrms_employee_id']);
    }

    // -----------------------------------------------------------------------
    // Relationship methods
    // -----------------------------------------------------------------------

    public function testPersonalDetailRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'personalDetail'));
    }

    public function testJobInformationRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'jobInformation'));
    }

    public function testEmploymentHistoryRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'employmentHistory'));
    }

    public function testSkillsRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'skills'));
    }

    public function testPerformanceReviewsRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'performanceReviews'));
    }

    public function testCertificationsRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'certifications'));
    }

    public function testAddressesRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'addresses'));
    }

    public function testEmergencyContactsRelationshipExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'emergencyContacts'));
    }

    // -----------------------------------------------------------------------
    // Helper methods
    // -----------------------------------------------------------------------

    public function testGetFullNameMethodExists(): void
    {
        $this->assertTrue(method_exists($this->model, 'getFullName'));
    }
}
