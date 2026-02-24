<?php

namespace App\Commands;

use App\Models\Employee;
use App\Models\SyncLog;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class HrmsSync extends BaseCommand
{
    protected $group = 'HRMS';
    protected $name = 'hrms:sync';
    protected $description = 'Synchronize employee data from HRMS system';
    protected $usage = 'hrms:sync [--type=full|incremental] [--dry-run]';
    protected $arguments = [];
    protected $options = [
        'type' => 'Sync type: full (all employees) or incremental (changes only). Default: full',
        'dry-run' => 'Preview changes without saving to database',
        'limit' => 'Limit number of employees to sync for testing',
        'retry' => 'Number of retries on sync failure. Default: 3',
    ];

    protected $syncType = 'full';
    protected $isDryRun = false;
    protected $limit = null;
    protected $maxRetries = 3;
    protected $syncStartTime;
    protected $hrmsClient;
    protected $employeeModel;
    protected $syncLogModel;

    /**
     * Execute the sync command
     */
    public function run(array $params)
    {
        try {
            $this->initialize();

            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::write('HRMS Employee Synchronization Started', 'cyan');
            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::newLine();

            // Display configuration
            CLI::write('Configuration:', 'yellow');
            CLI::write("  Sync Type: {$this->syncType}", 'white');
            CLI::write("  Dry Run: " . ($this->isDryRun ? 'YES' : 'NO'), 'white');
            if ($this->limit) {
                CLI::write("  Limit: {$this->limit} employees", 'white');
            }
            CLI::write("  Retry Attempts: {$this->maxRetries}", 'white');
            CLI::newLine();

            // Perform the sync
            $result = $this->performSync();

            // Display results
            $this->displayResults($result);

            // Create sync log entry
            if (!$this->isDryRun) {
                $this->createSyncLog($result);
            }

            CLI::newLine();
            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::write('HRMS Synchronization Completed Successfully', 'green');
            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');

            return 0;
        } catch (\Throwable $e) {
            CLI::error('SYNC FAILED: ' . $e->getMessage());
            CLI::error('Stack Trace: ' . $e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Initialize sync parameters and services
     */
    protected function initialize()
    {
        // Get options
        $this->syncType = $this->getOption('type') ?? 'full';
        $this->isDryRun = $this->getOption('dry-run') !== null;
        $this->limit = $this->getOption('limit');
        $this->maxRetries = $this->getOption('retry') ?? 3;

        // Validate sync type
        if (!in_array($this->syncType, ['full', 'incremental'])) {
            throw new \Exception("Invalid sync type: {$this->syncType}. Use 'full' or 'incremental'");
        }

        // Initialize services
        $this->syncStartTime = date('Y-m-d H:i:s');
        $this->hrmsClient = service('hrmsclient');
        $this->employeeModel = new Employee();
        $this->syncLogModel = new SyncLog();
    }

    /**
     * Perform the employee synchronization
     */
    protected function performSync(): array
    {
        $result = [
            'sync_type' => $this->syncType,
            'total_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'unchanged' => 0,
            'errors' => [],
            'start_time' => $this->syncStartTime,
            'end_time' => null,
            'duration' => 0
        ];

        try {
            // Fetch employees from HRMS
            CLI::write('Fetching employees from HRMS...', 'yellow');
            $hrmsBatch = 1;
            $pageSize = 100;
            $allEmployees = [];

            do {
                CLI::write("  Fetching batch {$hrmsBatch}...", 'white');
                $batchEmployees = $this->hrmsClient->getEmployees(
                    $this->syncType,
                    $hrmsBatch,
                    $pageSize
                );

                if (empty($batchEmployees)) {
                    break;
                }

                $allEmployees = array_merge($allEmployees, $batchEmployees);

                if ($this->limit && count($allEmployees) >= $this->limit) {
                    $allEmployees = array_slice($allEmployees, 0, $this->limit);
                    break;
                }

                $hrmsBatch++;
            } while (true);

            CLI::write("  Total employees fetched: " . count($allEmployees), 'green');
            CLI::newLine();

            // Process employees
            if (empty($allEmployees)) {
                CLI::write('No employees to sync', 'yellow');
                return $result;
            }

            CLI::write('Processing employees...', 'yellow');
            $progressBar = CLI::getProgressBar(count($allEmployees));

            foreach ($allEmployees as $index => $hrmsEmployee) {
                $result['total_processed']++;

                try {
                    // Find existing employee
                    $existingEmployee = $this->employeeModel
                        ->where('hrms_employee_id', $hrmsEmployee['id'])
                        ->first();

                    // Transform HRMS data to application format
                    $appEmployee = $this->transformEmployeeData($hrmsEmployee);

                    if ($existingEmployee) {
                        // Check if data has changed
                        if ($this->hasEmployeeDataChanged($existingEmployee, $appEmployee)) {
                            // Update existing employee
                            if (!$this->isDryRun) {
                                $this->employeeModel->update($existingEmployee['id'], $appEmployee);
                            }
                            $result['updated']++;
                            $status = 'UPDATED';
                        } else {
                            $result['unchanged']++;
                            $status = 'UNCHANGED';
                        }
                    } else {
                        // Create new employee
                        if (!$this->isDryRun) {
                            $this->employeeModel->insert($appEmployee);
                        }
                        $result['created']++;
                        $status = 'CREATED';
                    }

                    CLI::write("  [{$hrmsEmployee['id']}] {$hrmsEmployee['first_name']} {$hrmsEmployee['last_name']} - $status", 'green');
                } catch (\Throwable $e) {
                    $result['failed']++;
                    $result['errors'][] = [
                        'employee_id' => $hrmsEmployee['id'] ?? 'Unknown',
                        'name' => $hrmsEmployee['first_name'] . ' ' . $hrmsEmployee['last_name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];

                    CLI::write("  [{$hrmsEmployee['id'] ?? 'Unknown'}] ERROR: " . $e->getMessage(), 'red');
                }

                $progressBar->update($index + 1);
            }

            $progressBar->finish();

        } catch (\Throwable $e) {
            CLI::error("Error during fetch/process: " . $e->getMessage());
            throw $e;
        }

        $result['end_time'] = date('Y-m-d H:i:s');
        $result['duration'] = $this->calculateDuration($result['start_time'], $result['end_time']);

        return $result;
    }

    /**
     * Transform HRMS employee data to application format
     */
    protected function transformEmployeeData(array $hrmsEmployee): array
    {
        return [
            'hrms_employee_id' => $hrmsEmployee['id'] ?? null,
            'first_name' => $hrmsEmployee['first_name'] ?? '',
            'last_name' => $hrmsEmployee['last_name'] ?? '',
            'email' => $hrmsEmployee['email'] ?? '',
            'phone' => $hrmsEmployee['phone'] ?? '',
            'date_of_birth' => $hrmsEmployee['date_of_birth'] ?? null,
            'gender' => $hrmsEmployee['gender'] ?? null,
            'nationality' => $hrmsEmployee['nationality'] ?? null,
            'employment_status' => $hrmsEmployee['employment_status'] ?? 'Active',
            'date_of_joining' => $hrmsEmployee['date_of_joining'] ?? null,
            'is_active' => isset($hrmsEmployee['is_active']) ? (bool)$hrmsEmployee['is_active'] : true,
            'last_synced_at' => date('Y-m-d H:i:s'),
            'hrms_sync_status' => 'Synced'
        ];
    }

    /**
     * Check if employee data has changed
     */
    protected function hasEmployeeDataChanged(array $existing, array $updated): bool
    {
        $keysToCheck = [
            'first_name', 'last_name', 'email', 'phone',
            'date_of_birth', 'gender', 'nationality',
            'employment_status', 'date_of_joining', 'is_active'
        ];

        foreach ($keysToCheck as $key) {
            $existingValue = $existing[$key] ?? null;
            $updatedValue = $updated[$key] ?? null;

            if ($existingValue !== $updatedValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate sync duration
     */
    protected function calculateDuration(string $startTime, string $endTime): int
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        return abs($end - $start);
    }

    /**
     * Display sync results
     */
    protected function displayResults(array $result)
    {
        CLI::write('Results:', 'yellow');
        CLI::write("  Total Processed: {$result['total_processed']}", 'white');
        CLI::write("  Created: {$result['created']}", 'green');
        CLI::write("  Updated: {$result['updated']}", 'green');
        CLI::write("  Unchanged: {$result['unchanged']}", 'cyan');
        CLI::write("  Failed: {$result['failed']}", $result['failed'] > 0 ? 'red' : 'white');
        CLI::write("  Duration: {$result['duration']} seconds", 'cyan');

        if (!empty($result['errors'])) {
            CLI::newLine();
            CLI::write('Errors:', 'red');
            foreach ($result['errors'] as $error) {
                CLI::write("  [{$error['employee_id']}] {$error['name']}: {$error['error']}", 'red');
            }
        }

        CLI::newLine();

        if ($this->isDryRun) {
            CLI::write('⚠️  DRY RUN MODE - No changes were saved to database', 'yellow');
        }
    }

    /**
     * Create sync log entry
     */
    protected function createSyncLog(array $result)
    {
        try {
            $this->syncLogModel->insert([
                'sync_type' => $result['sync_type'],
                'sync_date' => $result['start_time'],
                'completed_at' => $result['end_time'],
                'status' => $result['failed'] > 0 ? 'Completed with Errors' : 'Completed',
                'records_processed' => $result['total_processed'],
                'records_created' => $result['created'],
                'records_updated' => $result['updated'],
                'records_failed' => $result['failed'],
                'error_details' => !empty($result['errors']) ? json_encode($result['errors']) : null,
                'duration_seconds' => $result['duration']
            ]);

            CLI::write('Sync log entry created', 'green');
        } catch (\Throwable $e) {
            CLI::warn('Failed to create sync log: ' . $e->getMessage());
        }
    }
}
