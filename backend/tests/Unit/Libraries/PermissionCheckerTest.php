<?php

namespace Tests\Unit\Libraries;

use App\Libraries\PermissionChecker;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

/**
 * Unit tests for PermissionChecker.
 * Tests role-based module/action access, data scope rules, field masking,
 * and role helper methods.
 *
 * Because PermissionChecker loads user data from DB in its constructor,
 * these tests inject the role via reflection — isolating the pure logic
 * from the database layer.
 */
class PermissionCheckerTest extends CIUnitTestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Create a PermissionChecker with the given role injected via reflection,
     * bypassing the DB lookup.
     */
    private function makeChecker(string $role): PermissionChecker
    {
        // Pass null userId so DB lookup returns null without an error
        $checker = new PermissionChecker(null);

        $ref  = new ReflectionClass($checker);
        $prop = $ref->getProperty('role');
        $prop->setAccessible(true);
        $prop->setValue($checker, $role);

        return $checker;
    }

    // -----------------------------------------------------------------------
    // Role helper methods
    // -----------------------------------------------------------------------

    public function testIsAdminReturnsTrueForAdmin(): void
    {
        $this->assertTrue($this->makeChecker('admin')->isAdmin());
    }

    public function testIsAdminReturnsFalseForOtherRoles(): void
    {
        foreach (['hr', 'manager', 'employee', 'system'] as $role) {
            $this->assertFalse($this->makeChecker($role)->isAdmin(), "Failed for role: $role");
        }
    }

    public function testIsHrReturnsTrueForHr(): void
    {
        $this->assertTrue($this->makeChecker('hr')->isHr());
    }

    public function testIsManagerReturnsTrueForManager(): void
    {
        $this->assertTrue($this->makeChecker('manager')->isManager());
    }

    public function testIsEmployeeReturnsTrueForEmployee(): void
    {
        $this->assertTrue($this->makeChecker('employee')->isEmployee());
    }

    public function testGetRoleReturnsCorrectRole(): void
    {
        foreach (['admin', 'hr', 'manager', 'employee', 'system'] as $role) {
            $this->assertEquals($role, $this->makeChecker($role)->getRole());
        }
    }

    // -----------------------------------------------------------------------
    // Module access
    // -----------------------------------------------------------------------

    public function testAdminHasAccessToAllModules(): void
    {
        $checker = $this->makeChecker('admin');

        $this->assertTrue($checker->hasModuleAccess('personal-profile'));
        $this->assertTrue($checker->hasModuleAccess('job-organization'));
        $this->assertTrue($checker->hasModuleAccess('performance'));
        $this->assertTrue($checker->hasModuleAccess('talent-management'));
        $this->assertTrue($checker->hasModuleAccess('learning-development'));
    }

    public function testHrHasAccessToAllModules(): void
    {
        $checker = $this->makeChecker('hr');

        $this->assertTrue($checker->hasModuleAccess('personal-profile'));
        $this->assertTrue($checker->hasModuleAccess('talent-management'));
    }

    public function testEmployeeHasNoTalentManagementAccess(): void
    {
        $checker = $this->makeChecker('employee');

        $this->assertFalse($checker->hasModuleAccess('talent-management'));
    }

    public function testEmployeeHasPersonalProfileAccess(): void
    {
        $checker = $this->makeChecker('employee');

        $this->assertTrue($checker->hasModuleAccess('personal-profile'));
    }

    public function testInvalidModuleReturnsFalse(): void
    {
        $checker = $this->makeChecker('admin');

        $this->assertFalse($checker->hasModuleAccess('nonexistent-module'));
    }

    // -----------------------------------------------------------------------
    // Action access
    // -----------------------------------------------------------------------

    public function testAdminCanDeletePerformance(): void
    {
        $checker = $this->makeChecker('admin');

        $this->assertTrue($checker->hasActionAccess('performance', 'delete'));
    }

    public function testAdminCanExportAllModules(): void
    {
        $checker = $this->makeChecker('admin');

        $this->assertTrue($checker->hasActionAccess('personal-profile', 'export'));
        $this->assertTrue($checker->hasActionAccess('performance', 'export'));
        $this->assertTrue($checker->hasActionAccess('talent-management', 'export'));
    }

    public function testEmployeeCannotDeletePerformance(): void
    {
        $checker = $this->makeChecker('employee');

        $this->assertFalse($checker->hasActionAccess('performance', 'delete'));
    }

    public function testEmployeeCanReadAndCommentOnPerformance(): void
    {
        $checker = $this->makeChecker('employee');

        $this->assertTrue($checker->hasActionAccess('performance', 'read'));
        $this->assertTrue($checker->hasActionAccess('performance', 'comment'));
    }

    public function testManagerCanApprovePerformance(): void
    {
        $checker = $this->makeChecker('manager');

        $this->assertTrue($checker->hasActionAccess('performance', 'approve'));
    }

    public function testManagerCannotExportPersonalProfile(): void
    {
        $checker = $this->makeChecker('manager');

        $this->assertFalse($checker->hasActionAccess('personal-profile', 'export'));
    }

    public function testEmployeeCanEnrollInLearning(): void
    {
        $checker = $this->makeChecker('employee');

        $this->assertTrue($checker->hasActionAccess('learning-development', 'enroll'));
    }

    public function testEmployeeCannotWriteTalentManagement(): void
    {
        // Employee has no access to talent-management module at all
        $checker = $this->makeChecker('employee');

        $this->assertFalse($checker->hasActionAccess('talent-management', 'write'));
    }

    public function testInvalidActionReturnsFalse(): void
    {
        $checker = $this->makeChecker('admin');

        $this->assertFalse($checker->hasActionAccess('performance', 'nonexistent-action'));
    }

    // -----------------------------------------------------------------------
    // Data scope
    // -----------------------------------------------------------------------

    public function testAdminDataScopeIsAll(): void
    {
        $scope = $this->makeChecker('admin')->getDataScope(1);

        $this->assertContains('all', $scope);
    }

    public function testEmployeeDataScopeIsSelf(): void
    {
        $scope = $this->makeChecker('employee')->getDataScope(1);

        $this->assertContains('self', $scope);
    }

    public function testManagerDataScopeIncludesTeam(): void
    {
        $scope = $this->makeChecker('manager')->getDataScope(1);

        $this->assertContains('team', $scope);
    }

    public function testHrDataScopeIncludes(): void
    {
        $scope = $this->makeChecker('hr')->getDataScope(1);

        // HR has either 'all' or 'department' scope
        $hasScope = in_array('all', $scope) || in_array('department', $scope);
        $this->assertTrue($hasScope);
    }

    // -----------------------------------------------------------------------
    // Available modules
    // -----------------------------------------------------------------------

    public function testGetAvailableModulesReturnsArrayForAdmin(): void
    {
        $modules = $this->makeChecker('admin')->getAvailableModules();

        $this->assertIsArray($modules);
        $this->assertNotEmpty($modules);
        $this->assertContains('performance', $modules);
    }

    public function testGetAvailableModulesForEmployeeDoesNotIncludeTalent(): void
    {
        $modules = $this->makeChecker('employee')->getAvailableModules();

        $this->assertNotContains('talent-management', $modules);
    }

    // -----------------------------------------------------------------------
    // Mask sensitive fields
    // -----------------------------------------------------------------------

    public function testMaskSensitiveFieldsMasksEmailField(): void
    {
        $checker = $this->makeChecker('manager');
        $data    = ['email' => 'alice@example.com', 'first_name' => 'Alice'];
        $masked  = $checker->maskSensitiveFields($data);

        // email is a sensitive field — should be masked
        $this->assertNotEquals('alice@example.com', $masked['email']);
        // non-sensitive fields should be untouched
        $this->assertEquals('Alice', $masked['first_name']);
    }

    public function testMaskSensitiveFieldsMasksBankAccount(): void
    {
        $checker = $this->makeChecker('employee');
        $data    = ['bank_account' => '1234567890123456'];
        $masked  = $checker->maskSensitiveFields($data);

        $this->assertNotEquals('1234567890123456', $masked['bank_account']);
        $this->assertStringEndsWith('3456', $masked['bank_account']);
    }

    public function testMaskSensitiveFieldsSkipsAllowedFields(): void
    {
        $checker = $this->makeChecker('admin');
        $data    = ['email' => 'test@test.com', 'salary' => '50000'];

        // Allow email — should not be masked
        $masked = $checker->maskSensitiveFields($data, ['email']);

        $this->assertEquals('test@test.com', $masked['email']);
    }

    public function testMaskSensitiveFieldsPassesThroughNonSensitiveData(): void
    {
        $checker = $this->makeChecker('employee');
        $data    = ['first_name' => 'Bob', 'last_name' => 'Smith', 'department' => 'Engineering'];
        $masked  = $checker->maskSensitiveFields($data);

        $this->assertEquals($data, $masked);
    }

    public function testMaskSensitiveFieldsReturnsNonArrayUnchanged(): void
    {
        $checker = $this->makeChecker('admin');

        $this->assertEquals('hello', $checker->maskSensitiveFields('hello'));
        $this->assertEquals(42, $checker->maskSensitiveFields(42));
    }

    // -----------------------------------------------------------------------
    // Allowed read fields
    // -----------------------------------------------------------------------

    public function testAdminGetAllowedReadFieldsReturnsWildcard(): void
    {
        $fields = $this->makeChecker('admin')->getAllowedReadFields('personal-profile');

        $this->assertContains('*', $fields);
    }

    public function testEmployeeReadFieldsForPersonalProfile(): void
    {
        $fields = $this->makeChecker('employee')->getAllowedReadFields('personal-profile');

        $this->assertContains('email', $fields);
        $this->assertContains('first_name', $fields);
    }

    public function testInvalidModuleReturnsEmptyFields(): void
    {
        $fields = $this->makeChecker('employee')->getAllowedReadFields('nonexistent-module');

        $this->assertEmpty($fields);
    }
}
