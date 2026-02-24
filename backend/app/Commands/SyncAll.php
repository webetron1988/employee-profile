<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncAll extends BaseCommand
{
    protected $group = 'HRMS';
    protected $name = 'hrms:sync-all';
    protected $description = 'Run all HRMS synchronization jobs in sequence';
    protected $usage = 'hrms:sync-all [--dry-run] [--skip-org] [--skip-jobs]';
    protected $arguments = [];
    protected $options = [
        'dry-run' => 'Preview changes without saving',
        'skip-org' => 'Skip organization hierarchy sync',
        'skip-jobs' => 'Skip job information sync',
    ];

    public function run(array $params)
    {
        try {
            $isDryRun = $this->getOption('dry-run') !== null;
            $skipOrg = $this->getOption('skip-org') !== null;
            $skipJobs = $this->getOption('skip-jobs') !== null;

            CLI::write('╔═══════════════════════════════════════════════════════════╗', 'cyan');
            CLI::write('║       HRMS COMPLETE SYNCHRONIZATION STARTED              ║', 'cyan');
            CLI::write('╚═══════════════════════════════════════════════════════════╝', 'cyan');
            CLI::newLine();

            if ($isDryRun) {
                CLI::write('⚠️  DRY RUN MODE ENABLED - No changes will be saved', 'yellow');
                CLI::newLine();
            }

            $startTime = microtime(true);
            $results = [
                'employees' => null,
                'org_hierarchy' => null,
                'job_info' => null,
                'total_time' => 0
            ];

            // 1. Sync Employees
            CLI::write('───────────────────────────────────────────────────────────', 'cyan');
            CLI::write('Step 1 of 3: Employee Master Sync', 'yellow');
            CLI::write('───────────────────────────────────────────────────────────', 'cyan');
            CLI::newLine();

            try {
                $command = new HrmsSync();
                $args = [];
                if ($isDryRun) $args[] = '--dry-run';
                $args[] = '--type=full';

                $exitCode = $command->run($args);
                $results['employees'] = [
                    'status' => $exitCode === 0 ? 'SUCCESS' : 'FAILED',
                    'exit_code' => $exitCode
                ];

                CLI::write($results['employees']['status'] === 'SUCCESS' ? '✓' : '✗', 
                    $results['employees']['status'] === 'SUCCESS' ? 'green' : 'red');
                CLI::newLine(2);
            } catch (\Throwable $e) {
                $results['employees'] = [
                    'status' => 'ERROR',
                    'error' => $e->getMessage()
                ];
                CLI::error('Employee sync failed: ' . $e->getMessage());
                CLI::newLine(2);
            }

            // 2. Sync Organization Hierarchy
            if (!$skipOrg) {
                CLI::write('───────────────────────────────────────────────────────────', 'cyan');
                CLI::write('Step 2 of 3: Organization Hierarchy Sync', 'yellow');
                CLI::write('───────────────────────────────────────────────────────────', 'cyan');
                CLI::newLine();

                try {
                    $command = new OrgHierarchySync();
                    $args = [];
                    if ($isDryRun) $args[] = '--dry-run';

                    $exitCode = $command->run($args);
                    $results['org_hierarchy'] = [
                        'status' => $exitCode === 0 ? 'SUCCESS' : 'FAILED',
                        'exit_code' => $exitCode
                    ];

                    CLI::write($results['org_hierarchy']['status'] === 'SUCCESS' ? '✓' : '✗', 
                        $results['org_hierarchy']['status'] === 'SUCCESS' ? 'green' : 'red');
                    CLI::newLine(2);
                } catch (\Throwable $e) {
                    $results['org_hierarchy'] = [
                        'status' => 'ERROR',
                        'error' => $e->getMessage()
                    ];
                    CLI::error('Organization hierarchy sync failed: ' . $e->getMessage());
                    CLI::newLine(2);
                }
            } else {
                $results['org_hierarchy'] = [
                    'status' => 'SKIPPED'
                ];
            }

            // 3. Sync Job Information
            if (!$skipJobs) {
                CLI::write('───────────────────────────────────────────────────────────', 'cyan');
                CLI::write('Step 3 of 3: Job Information Sync', 'yellow');
                CLI::write('───────────────────────────────────────────────────────────', 'cyan');
                CLI::newLine();

                try {
                    $command = new JobInfoSync();
                    $args = [];
                    if ($isDryRun) $args[] = '--dry-run';

                    $exitCode = $command->run($args);
                    $results['job_info'] = [
                        'status' => $exitCode === 0 ? 'SUCCESS' : 'FAILED',
                        'exit_code' => $exitCode
                    ];

                    CLI::write($results['job_info']['status'] === 'SUCCESS' ? '✓' : '✗', 
                        $results['job_info']['status'] === 'SUCCESS' ? 'green' : 'red');
                    CLI::newLine(2);
                } catch (\Throwable $e) {
                    $results['job_info'] = [
                        'status' => 'ERROR',
                        'error' => $e->getMessage()
                    ];
                    CLI::error('Job information sync failed: ' . $e->getMessage());
                    CLI::newLine(2);
                }
            } else {
                $results['job_info'] = [
                    'status' => 'SKIPPED'
                ];
            }

            // Summary
            $results['total_time'] = microtime(true) - $startTime;

            $this->displaySummary($results, $isDryRun);

            return 0;
        } catch (\Throwable $e) {
            CLI::error('CRITICAL ERROR: ' . $e->getMessage());
            return 1;
        }
    }

    protected function displaySummary(array $results, bool $isDryRun)
    {
        CLI::newLine();
        CLI::write('╔═══════════════════════════════════════════════════════════╗', 'cyan');
        CLI::write('║               SYNCHRONIZATION SUMMARY                     ║', 'cyan');
        CLI::write('╚═══════════════════════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        $this->displayStepResult('Step 1: Employee Master', $results['employees']);
        $this->displayStepResult('Step 2: Organization Hierarchy', $results['org_hierarchy']);
        $this->displayStepResult('Step 3: Job Information', $results['job_info']);

        CLI::newLine();
        CLI::write('Total Execution Time: ' . number_format($results['total_time'], 2) . ' seconds', 'cyan');

        if ($isDryRun) {
            CLI::newLine();
            CLI::write('⚠️  DRY RUN MODE - No changes were persisted', 'yellow');
        }

        CLI::newLine();
        CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
        CLI::write('Synchronization Complete', 'green');
        CLI::write('═══════════════════════════════════════════════════════════', 'cyan');
    }

    protected function displayStepResult(string $step, array $result)
    {
        $status = $result['status'] ?? 'UNKNOWN';
        $statusColor = match($status) {
            'SUCCESS' => 'green',
            'FAILED' => 'red',
            'ERROR' => 'red',
            'SKIPPED' => 'cyan',
            default => 'white'
        };

        $statusIcon = match($status) {
            'SUCCESS' => '✓',
            'FAILED' => '✗',
            'ERROR' => '✗',
            'SKIPPED' => '○',
            default => '?'
        };

        CLI::write("{$step}: ", 'white');
        CLI::write("{$statusIcon} {$status}", $statusColor);

        if (isset($result['error'])) {
            CLI::write("  Error: {$result['error']}", 'red');
        }
    }
}
