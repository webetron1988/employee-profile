<?php

namespace App\Commands;

use App\Models\User;
use App\Models\JobInformation;
use App\Models\SyncLog;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class JobInfoSync extends BaseCommand
{
    protected $group = 'HRMS';
    protected $name = 'hrms:sync-jobs';
    protected $description = 'Synchronize job information from HRMS';
    protected $usage = 'hrms:sync-jobs [--dry-run]';
    protected $arguments = [];
    protected $options = [
        'dry-run' => 'Preview changes without saving',
    ];

    protected $isDryRun = false;
    protected $hrmsClient;
    protected $employeeModel;
    protected $jobInfoModel;
    protected $syncLogModel;

    public function run(array $params)
    {
        try {
            $this->initialize();

            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::write('Job Information Synchronization Started', 'cyan');
            CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
            CLI::newLine();

            CLI::write('Fetching job information from HRMS...', 'yellow');
            $jobsData = $this->hrmsClient->getJobInformation();

            if (empty($jobsData)) {
                CLI::write('No job information to sync', 'yellow');
                return 0;
            }

            CLI::write("Received job data for " . count($jobsData) . " employees", 'green');
            CLI::newLine();

            $result = [
                'total_processed' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
                'errors' => [],
                'start_time' => date('Y-m-d H:i:s')
            ];

            CLI::write('Processing job information...', 'yellow');
            $progressBar = CLI::getProgressBar(count($jobsData));

            foreach ($jobsData as $index => $jobData) {
                $result['total_processed']++;

                try {
                    // Find employee by HRMS ID
                    $employee = $this->employeeModel
                        ->where('hrms_employee_id', $jobData['employee_id'])
                        ->first();

                    if (!$employee) {
                        throw new \Exception("Employee not found in system");
                    }

                    // Transform data
                    $appJobData = $this->transformJobData($jobData, $employee['id']);

                    // Find existing job info
                    $existingJobInfo = $this->jobInfoModel
                        ->where('employee_id', $employee['id'])
                        ->first();

                    if ($existingJobInfo) {
                        if (!$this->isDryRun) {
                            $this->jobInfoModel->update($existingJobInfo['id'], $appJobData);
                        }
                        $result['updated']++;
                    } else {
                        if (!$this->isDryRun) {
                            $this->jobInfoModel->insert($appJobData);
                        }
                        $result['created']++;
                    }

                    CLI::write("  [{$jobData['employee_id']}] {$jobData['designation']} - OK", 'green');
                } catch (\Throwable $e) {
                    $result['failed']++;
                    $result['errors'][] = "Employee {$jobData['employee_id']}: {$e->getMessage()}";
                    CLI::write("  [{$jobData['employee_id']}] ERROR: {$e->getMessage()}", 'red');
                }

                $progressBar->update($index + 1);
            }

            $progressBar->finish();
            CLI::newLine();

            $result['end_time'] = date('Y-m-d H:i:s');

            // Display results
            $this->displayResults($result);

            // Create sync log
            if (!$this->isDryRun) {
                $this->createSyncLog($result);
            }

            CLI::newLine();
            CLI::write('Job Information Sync Completed', 'green');

            return 0;
        } catch (\Throwable $e) {
            CLI::error('SYNC FAILED: ' . $e->getMessage());
            return 1;
        }
    }

    protected function initialize()
    {
        $this->isDryRun = $this->getOption('dry-run') !== null;

        $this->hrmsClient = service('hrmsclient');
        $this->employeeModel = new User();
        $this->jobInfoModel = new JobInformation();
        $this->syncLogModel = new SyncLog();
    }

    protected function transformJobData(array $jobData, int $employeeId): array
    {
        return [
            'employee_id' => $employeeId,
            'designation' => $jobData['designation'] ?? null,
            'department' => $jobData['department'] ?? null,
            'employment_type' => $jobData['employment_type'] ?? 'Full-time',
            'employment_status' => $jobData['employment_status'] ?? 'Active',
            'grade' => $jobData['grade'] ?? null,
            'salary_band' => $jobData['salary_band'] ?? null,
            'work_location' => $jobData['work_location'] ?? null,
            'reporting_manager_id' => $this->getManagerEmployeeId($jobData['manager_hrms_id'] ?? null),
            'functional_manager_id' => $this->getManagerEmployeeId($jobData['functional_manager_hrms_id'] ?? null),
            'cost_center' => $jobData['cost_center'] ?? null,
            'business_unit' => $jobData['business_unit'] ?? null,
            'team' => $jobData['team'] ?? null,
            'shift_type' => $jobData['shift_type'] ?? null,
            'job_start_date' => $jobData['job_start_date'] ?? null,
            'last_promotion_date' => $jobData['last_promotion_date'] ?? null,
        ];
    }

    protected function getManagerEmployeeId(?string $managerHrmsId): ?int
    {
        if (!$managerHrmsId) {
            return null;
        }

        $manager = $this->employeeModel
            ->where('hrms_employee_id', $managerHrmsId)
            ->first();

        return $manager ? $manager['id'] : null;
    }

    protected function displayResults(array $result)
    {
        CLI::write('Results:', 'yellow');
        CLI::write("  Total Processed: {$result['total_processed']}", 'white');
        CLI::write("  Created: {$result['created']}", 'green');
        CLI::write("  Updated: {$result['updated']}", 'green');
        CLI::write("  Failed: {$result['failed']}", $result['failed'] > 0 ? 'red' : 'white');

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
                'sync_type' => 'job_information',
                'sync_date' => $result['start_time'],
                'completed_at' => $result['end_time'],
                'status' => $result['failed'] > 0 ? 'Completed with Errors' : 'Completed',
                'records_processed' => $result['total_processed'],
                'records_created' => $result['created'],
                'records_updated' => $result['updated'],
                'records_failed' => $result['failed'],
                'error_details' => !empty($result['errors']) ? json_encode($result['errors']) : null
            ]);
        } catch (\Throwable $e) {
            CLI::warn('Failed to create sync log: ' . $e->getMessage());
        }
    }
}
