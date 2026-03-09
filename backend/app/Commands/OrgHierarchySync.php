<?php

namespace App\Commands;

use App\Models\User;
use App\Models\JobInformation;
use App\Models\OrgHierarchy;
use App\Models\SyncLog;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class OrgHierarchySync extends BaseCommand
{
    protected $group = 'HRMS';
    protected $name = 'hrms:sync-org';
    protected $description = 'Synchronize organizational hierarchy from HRMS';
    protected $usage = 'hrms:sync-org [--dry-run] [--force]';
    protected $arguments = [];
    protected $options = [
        'dry-run' => 'Preview changes without saving',
        'force' => 'Force sync even if recent sync exists',
    ];

    protected $isDryRun = false;
    protected $isForce = false;
    protected $hrmsClient;
    protected $employeeModel;
    protected $jobInfoModel;
    protected $orgHierarchyModel;
    protected $syncLogModel;

    public function run(array $params)
    {
        try {
            $this->initialize();

            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::write('Organization Hierarchy Synchronization Started', 'cyan');
            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::newLine();

            // Check if recent sync exists
            if (!$this->isForce && !$this->isDryRun) {
                $recentSync = $this->syncLogModel
                    ->where('sync_type', 'org_hierarchy')
                    ->where('status', 'Completed')
                    ->where('sync_date >', date('Y-m-d H:i:s', strtotime('-6 hours')))
                    ->first();

                if ($recentSync) {
                    CLI::write('Recent sync found (within last 6 hours)', 'yellow');
                    CLI::write('Use --force to override', 'yellow');
                    return 0;
                }
            }

            CLI::write('Fetching organizational hierarchy from HRMS...', 'yellow');
            $orgStructure = $this->hrmsClient->getOrgHierarchy();

            if (empty($orgStructure)) {
                CLI::write('No organizational data to sync', 'yellow');
                return 0;
            }

            CLI::write("Received {$orgStructure['departments']} departments", 'green');
            CLI::newLine();

            // Process org hierarchy
            $result = [
                'departments_processed' => 0,
                'relationships_created' => 0,
                'relationships_updated' => 0,
                'errors' => [],
                'start_time' => date('Y-m-d H:i:s')
            ];

            CLI::write('Processing organizational units...', 'yellow');

            // First pass: Create/update departments
            $departments = $this->hrmsClient->getDepartments();
            foreach ($departments as $dept) {
                try {
                    $this->syncDepartment($dept);
                    $result['departments_processed']++;
                    CLI::write("  ✓ {$dept['name']}", 'green');
                } catch (\Throwable $e) {
                    $result['errors'][] = $dept['name'] . ': ' . $e->getMessage();
                    CLI::write("  ✗ {$dept['name']}: {$e->getMessage()}", 'red');
                }
            }

            CLI::newLine();
            CLI::write('Processing reporting relationships...', 'yellow');

            // Second pass: Create reporting relationships
            $managers = $this->hrmsClient->getManagerAssignments();
            foreach ($managers as $assignment) {
                try {
                    $this->syncManagerAssignment($assignment);
                    $result['relationships_created']++;
                    CLI::write("  ✓ Relationship synced", 'green');
                } catch (\Throwable $e) {
                    $result['errors'][] = 'Manager assignment: ' . $e->getMessage();
                    CLI::write("  ✗ Error: {$e->getMessage()}", 'red');
                }
            }

            $result['end_time'] = date('Y-m-d H:i:s');

            // Display results
            $this->displayResults($result);

            // Create sync log
            if (!$this->isDryRun) {
                $this->createSyncLog($result);
            }

            CLI::newLine();
            CLI::write('Organization Hierarchy Sync Completed', 'green');

            return 0;
        } catch (\Throwable $e) {
            CLI::error('SYNC FAILED: ' . $e->getMessage());
            return 1;
        }
    }

    protected function initialize()
    {
        $this->isDryRun = $this->getOption('dry-run') !== null;
        $this->isForce = $this->getOption('force') !== null;

        $this->hrmsClient = service('hrmsclient');
        $this->employeeModel = new User();
        $this->jobInfoModel = new JobInformation();
        $this->orgHierarchyModel = new OrgHierarchy();
        $this->syncLogModel = new SyncLog();
    }

    protected function syncDepartment(array $dept)
    {
        if ($this->isDryRun) {
            return;
        }

        // Find or create department
        $existing = $this->orgHierarchyModel
            ->where('external_id', $dept['id'])
            ->first();

        $data = [
            'external_id' => $dept['id'],
            'name' => $dept['name'],
            'department_code' => $dept['code'] ?? null,
            'parent_id' => $dept['parent_id'] ?? null,
            'level' => $dept['level'] ?? 1,
            'description' => $dept['description'] ?? null,
            'is_active' => isset($dept['is_active']) ? (bool)$dept['is_active'] : true
        ];

        if ($existing) {
            $this->orgHierarchyModel->update($existing['id'], $data);
        } else {
            $this->orgHierarchyModel->insert($data);
        }
    }

    protected function syncManagerAssignment(array $assignment)
    {
        if ($this->isDryRun) {
            return;
        }

        // Find employee
        $employee = $this->employeeModel
            ->where('hrms_employee_id', $assignment['employee_id'])
            ->first();

        if (!$employee) {
            throw new \Exception("Employee not found: {$assignment['employee_id']}");
        }

        // Find manager
        $manager = $this->employeeModel
            ->where('hrms_employee_id', $assignment['manager_id'])
            ->first();

        $managerEmployeeId = $manager ? $manager['id'] : null;

        // Find or create job info
        $jobInfo = $this->jobInfoModel
            ->where('employee_id', $employee['id'])
            ->first();

        $data = [
            'reporting_manager_id' => $managerEmployeeId,
            'department' => $assignment['department'] ?? null
        ];

        if ($jobInfo) {
            $this->jobInfoModel->update($jobInfo['id'], $data);
        } else {
            $data['employee_id'] = $employee['id'];
            $this->jobInfoModel->insert($data);
        }
    }

    protected function displayResults(array $result)
    {
        CLI::write('Results:', 'yellow');
        CLI::write("  Departments Processed: {$result['departments_processed']}", 'green');
        CLI::write("  Relationships Created: {$result['relationships_created']}", 'green');
        CLI::write("  Errors: " . count($result['errors']), count($result['errors']) > 0 ? 'red' : 'green');

        if (!empty($result['errors'])) {
            CLI::newLine();
            CLI::write('Errors:', 'red');
            foreach ($result['errors'] as $error) {
                CLI::write("  - {$error}", 'red');
            }
        }

        if ($this->isDryRun) {
            CLI::write('DRY RUN - No changes saved', 'yellow');
        }
    }

    protected function createSyncLog(array $result)
    {
        try {
            $this->syncLogModel->insert([
                'sync_type' => 'org_hierarchy',
                'sync_date' => $result['start_time'],
                'completed_at' => $result['end_time'],
                'status' => empty($result['errors']) ? 'Completed' : 'Completed with Errors',
                'records_processed' => $result['departments_processed'],
                'error_details' => !empty($result['errors']) ? json_encode($result['errors']) : null
            ]);
        } catch (\Throwable $e) {
            CLI::warn('Failed to create sync log: ' . $e->getMessage());
        }
    }
}
