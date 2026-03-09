<?php

namespace App\Commands;

use App\Models\User;
use App\Models\HrmsEmployee;
use App\Models\SyncLog;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class HrmsSync extends BaseCommand
{
    protected $group = 'HRMS';
    protected $name = 'hrms:sync';
    protected $description = 'Synchronize user data from HRMS database (hrms_employee_id on users)';
    protected $usage = 'hrms:sync [--type=full|incremental] [--dry-run] [--limit=N]';
    protected $arguments = [];
    protected $options = [
        'type'    => 'Sync type: full (all employees) or incremental (active only). Default: full',
        'dry-run' => 'Preview changes without saving to database',
        'limit'   => 'Limit number of employees to sync for testing',
    ];

    protected $syncType = 'full';
    protected $isDryRun = false;
    protected $limit = null;
    protected $syncStartTime;

    protected $hrmsEmployeeModel;
    protected $userModel;
    protected $syncLogModel;

    public function run(array $params)
    {
        try {
            $this->initialize();

            CLI::write('==========================================================', 'cyan');
            CLI::write('HRMS Employee + User Synchronization', 'cyan');
            CLI::write('==========================================================', 'cyan');
            CLI::newLine();

            CLI::write('Configuration:', 'yellow');
            CLI::write("  Sync Type: {$this->syncType}", 'white');
            CLI::write("  Dry Run: " . ($this->isDryRun ? 'YES' : 'NO'), 'white');
            if ($this->limit) {
                CLI::write("  Limit: {$this->limit} employees", 'white');
            }
            CLI::newLine();

            $result = $this->performSync();
            $this->displayResults($result);

            if (!$this->isDryRun) {
                $this->createSyncLog($result);
            }

            CLI::newLine();
            CLI::write('==========================================================', 'cyan');
            CLI::write('Synchronization Completed', 'green');
            CLI::write('==========================================================', 'cyan');

            return 0;
        } catch (\Throwable $e) {
            CLI::error('SYNC FAILED: ' . $e->getMessage());
            CLI::error('Stack Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    protected function initialize()
    {
        $this->syncType  = CLI::getOption('type') ?? 'full';
        $this->isDryRun  = CLI::getOption('dry-run') !== null;
        $this->limit     = CLI::getOption('limit');

        if (!in_array($this->syncType, ['full', 'incremental'])) {
            throw new \Exception("Invalid sync type: {$this->syncType}. Use 'full' or 'incremental'");
        }

        $this->syncStartTime    = date('Y-m-d H:i:s');
        $this->hrmsEmployeeModel = new HrmsEmployee();
        $this->userModel        = new User();
        $this->syncLogModel     = new SyncLog();
    }

    protected function performSync(): array
    {
        $result = [
            'sync_type'       => $this->syncType,
            'total_processed' => 0,
            'created'         => 0,
            'updated'         => 0,
            'unchanged'       => 0,
            'failed'          => 0,
            'errors'          => [],
            'start_time'      => $this->syncStartTime,
            'end_time'        => null,
            'duration'        => 0,
        ];

        // Fetch all active employees from HRMS database directly
        CLI::write('Fetching employees from HRMS database...', 'yellow');
        $hrmsEmployees = $this->hrmsEmployeeModel->getAllActive();

        if ($this->limit) {
            $hrmsEmployees = array_slice($hrmsEmployees, 0, (int) $this->limit);
        }

        CLI::write('  Total fetched: ' . count($hrmsEmployees), 'green');
        CLI::newLine();

        if (empty($hrmsEmployees)) {
            CLI::write('No employees to sync.', 'yellow');
            return $result;
        }

        CLI::write('Processing employees → users table...', 'yellow');

        foreach ($hrmsEmployees as $hrmsRow) {
            $result['total_processed']++;
            $name = trim(($hrmsRow['name'] ?? '') . ' ' . ($hrmsRow['last_name'] ?? ''));

            try {
                $userData = $this->hrmsEmployeeModel->formatForEp($hrmsRow);
                $empId    = $hrmsRow['empID'];

                // ── users table (hrms_employee_id stored directly) ──
                $existingUser = $this->userModel
                    ->where('hrms_employee_id', (string) $empId)
                    ->first();

                // Fallback: try by email
                if (!$existingUser) {
                    $existingUser = $this->userModel->where('email', $hrmsRow['email'])->first();
                }

                $action = 'UNCHANGED';

                if ($existingUser) {
                    if ($this->hasChanged($existingUser, $userData)) {
                        if (!$this->isDryRun) {
                            $userData['updated_at'] = date('Y-m-d H:i:s');
                            $this->userModel->skipValidation(true)->update($existingUser['id'], $userData);
                        }
                        $result['updated']++;
                        $action = 'UPDATED';
                    } else {
                        $result['unchanged']++;
                    }
                } else {
                    if (!$this->isDryRun) {
                        $userData['password_hash'] = null;
                        $userData['permissions']   = json_encode([]);
                        $userData['created_at']    = date('Y-m-d H:i:s');
                        $userData['updated_at']    = date('Y-m-d H:i:s');
                        $this->userModel->skipValidation(true)->insert($userData);
                    }
                    $result['created']++;
                    $action = 'CREATED';
                }

                $tag = ($action !== 'UNCHANGED') ? 'green' : 'white';
                CLI::write("  [{$empId}] {$name} — {$action}", $tag);

            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = [
                    'employee_id' => $hrmsRow['empID'] ?? 'Unknown',
                    'name'        => $name,
                    'error'       => $e->getMessage(),
                ];
                CLI::write("  [{$hrmsRow['empID']}] ERROR: " . $e->getMessage(), 'red');
            }
        }

        $result['end_time'] = date('Y-m-d H:i:s');
        $result['duration'] = abs(strtotime($result['end_time']) - strtotime($result['start_time']));

        return $result;
    }

    protected function hasChanged(array $existing, array $updated): bool
    {
        $keys = ['email', 'first_name', 'last_name', 'phone', 'role', 'is_active'];
        foreach ($keys as $key) {
            if (isset($updated[$key]) && ($existing[$key] ?? null) != ($updated[$key] ?? null)) {
                return true;
            }
        }
        return false;
    }

    protected function displayResults(array $result)
    {
        CLI::newLine();
        CLI::write('Results:', 'yellow');
        CLI::write("  Total Processed:  {$result['total_processed']}", 'white');
        CLI::write("  Users Created:    {$result['created']}", 'green');
        CLI::write("  Users Updated:    {$result['updated']}", 'green');
        CLI::write("  Unchanged:        {$result['unchanged']}", 'cyan');
        CLI::write("  Failed:           {$result['failed']}", $result['failed'] > 0 ? 'red' : 'white');
        CLI::write("  Duration:         {$result['duration']}s", 'cyan');

        if (!empty($result['errors'])) {
            CLI::newLine();
            CLI::write('Errors:', 'red');
            foreach ($result['errors'] as $error) {
                CLI::write("  [{$error['employee_id']}] {$error['name']}: {$error['error']}", 'red');
            }
        }

        if ($this->isDryRun) {
            CLI::newLine();
            CLI::write('DRY RUN — No changes were saved to database', 'yellow');
        }
    }

    protected function createSyncLog(array $result)
    {
        try {
            $this->syncLogModel->insert([
                'sync_type'         => $result['sync_type'],
                'sync_date'         => $result['start_time'],
                'completed_at'      => $result['end_time'],
                'status'            => $result['failed'] > 0 ? 'Completed with Errors' : 'Completed',
                'records_processed' => $result['total_processed'],
                'records_created'   => $result['created'],
                'records_updated'   => $result['updated'],
                'records_failed'    => $result['failed'],
                'error_details'     => !empty($result['errors']) ? json_encode($result['errors']) : null,
                'duration_seconds'  => $result['duration'],
            ]);
            CLI::write('Sync log entry created.', 'green');
        } catch (\Throwable $e) {
            CLI::write('Failed to create sync log: ' . $e->getMessage(), 'yellow');
        }
    }
}
