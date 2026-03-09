<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds sample employees, users, job information, and org hierarchy
 * for development and testing purposes.
 *
 * Creates:
 *  - 1 Admin user
 *  - 1 HR user
 *  - 2 Managers
 *  - 6 Employees
 * Total: 10 records
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // ----------------------------------------------------------------
        // 1. Employees
        // ----------------------------------------------------------------
        $employees = [
            // Admin
            [
                'employee_id'       => 'EMP-0001',
                'hrms_employee_id'  => 'HRMS-0001',
                'email'             => 'admin@company.com',
                'first_name'        => 'System',
                'last_name'         => 'Administrator',
                'phone'             => '+91-9000000001',
                'date_of_birth'     => '1985-01-15',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            // HR Manager
            [
                'employee_id'       => 'EMP-0002',
                'hrms_employee_id'  => 'HRMS-0002',
                'email'             => 'hr.manager@company.com',
                'first_name'        => 'Priya',
                'last_name'         => 'Sharma',
                'phone'             => '+91-9000000002',
                'date_of_birth'     => '1988-03-22',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            // Engineering Manager
            [
                'employee_id'       => 'EMP-0003',
                'hrms_employee_id'  => 'HRMS-0003',
                'email'             => 'eng.manager@company.com',
                'first_name'        => 'Rahul',
                'last_name'         => 'Verma',
                'phone'             => '+91-9000000003',
                'date_of_birth'     => '1986-07-10',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            // Sales Manager
            [
                'employee_id'       => 'EMP-0004',
                'hrms_employee_id'  => 'HRMS-0004',
                'email'             => 'sales.manager@company.com',
                'first_name'        => 'Anita',
                'last_name'         => 'Patel',
                'phone'             => '+91-9000000004',
                'date_of_birth'     => '1987-11-05',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            // Engineers
            [
                'employee_id'       => 'EMP-0005',
                'hrms_employee_id'  => 'HRMS-0005',
                'email'             => 'john.doe@company.com',
                'first_name'        => 'John',
                'last_name'         => 'Doe',
                'phone'             => '+91-9000000005',
                'date_of_birth'     => '1995-04-18',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'employee_id'       => 'EMP-0006',
                'hrms_employee_id'  => 'HRMS-0006',
                'email'             => 'jane.smith@company.com',
                'first_name'        => 'Jane',
                'last_name'         => 'Smith',
                'phone'             => '+91-9000000006',
                'date_of_birth'     => '1996-09-25',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'employee_id'       => 'EMP-0007',
                'hrms_employee_id'  => 'HRMS-0007',
                'email'             => 'ravi.kumar@company.com',
                'first_name'        => 'Ravi',
                'last_name'         => 'Kumar',
                'phone'             => '+91-9000000007',
                'date_of_birth'     => '1994-12-30',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            // Sales reps
            [
                'employee_id'       => 'EMP-0008',
                'hrms_employee_id'  => 'HRMS-0008',
                'email'             => 'sara.wilson@company.com',
                'first_name'        => 'Sara',
                'last_name'         => 'Wilson',
                'phone'             => '+91-9000000008',
                'date_of_birth'     => '1997-06-14',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'employee_id'       => 'EMP-0009',
                'hrms_employee_id'  => 'HRMS-0009',
                'email'             => 'arjun.nair@company.com',
                'first_name'        => 'Arjun',
                'last_name'         => 'Nair',
                'phone'             => '+91-9000000009',
                'date_of_birth'     => '1993-02-08',
                'nationality'       => 'Indian',
                'status'            => 'Active',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            // Inactive employee (for testing filters)
            [
                'employee_id'       => 'EMP-0010',
                'hrms_employee_id'  => 'HRMS-0010',
                'email'             => 'ex.employee@company.com',
                'first_name'        => 'Former',
                'last_name'         => 'Employee',
                'phone'             => '+91-9000000010',
                'date_of_birth'     => '1990-08-20',
                'nationality'       => 'Indian',
                'status'            => 'Inactive',
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
        ];

        $this->db->table('employees')->insertBatch($employees);
        echo "  ✓ Seeded " . \count($employees) . " employees.\n";

        // Fetch inserted IDs by employee_id
        $empMap = [];
        foreach ($this->db->table('employees')->get()->getResultArray() as $row) {
            $empMap[$row['employee_id']] = $row['id'];
        }

        // ----------------------------------------------------------------
        // 2. Users (auth accounts)
        // ----------------------------------------------------------------
        $users = [
            ['email' => 'admin@company.com',        'role' => 'admin',    'employee_id' => $empMap['EMP-0001'] ?? null],
            ['email' => 'hr.manager@company.com',   'role' => 'hr',       'employee_id' => $empMap['EMP-0002'] ?? null],
            ['email' => 'eng.manager@company.com',  'role' => 'manager',  'employee_id' => $empMap['EMP-0003'] ?? null],
            ['email' => 'sales.manager@company.com','role' => 'manager',  'employee_id' => $empMap['EMP-0004'] ?? null],
            ['email' => 'john.doe@company.com',     'role' => 'employee', 'employee_id' => $empMap['EMP-0005'] ?? null],
            ['email' => 'jane.smith@company.com',   'role' => 'employee', 'employee_id' => $empMap['EMP-0006'] ?? null],
            ['email' => 'ravi.kumar@company.com',   'role' => 'employee', 'employee_id' => $empMap['EMP-0007'] ?? null],
            ['email' => 'sara.wilson@company.com',  'role' => 'employee', 'employee_id' => $empMap['EMP-0008'] ?? null],
            ['email' => 'arjun.nair@company.com',   'role' => 'employee', 'employee_id' => $empMap['EMP-0009'] ?? null],
        ];

        // Default passwords by role (change these before production!)
        $rolePasswords = [
            'admin'    => password_hash('Admin@123',    PASSWORD_BCRYPT),
            'hr'       => password_hash('Hr@123',       PASSWORD_BCRYPT),
            'manager'  => password_hash('Manager@123',  PASSWORD_BCRYPT),
            'employee' => password_hash('Employee@123', PASSWORD_BCRYPT),
        ];

        foreach ($users as &$u) {
            $u['password_hash'] = $rolePasswords[$u['role']] ?? null;
            $u['permissions']   = json_encode([]);
            $u['is_active']     = 1;
            $u['created_at']    = $now;
            $u['updated_at']    = $now;
        }
        unset($u);

        $this->db->table('users')->insertBatch($users);
        echo "  ✓ Seeded " . \count($users) . " users.\n";

        // ----------------------------------------------------------------
        // 3. Job Information
        // ----------------------------------------------------------------
        $jobData = [
            [$empMap['EMP-0001'] ?? 1, 'System Administrator', 'IT',          'Technology',   'Head Office', 'Full-Time', 'L7', '2020-01-01', null],
            [$empMap['EMP-0002'] ?? 2, 'HR Manager',           'HR',          'Corporate',    'Head Office', 'Full-Time', 'L6', '2019-03-15', $empMap['EMP-0001'] ?? 1],
            [$empMap['EMP-0003'] ?? 3, 'Engineering Manager',  'Engineering', 'Technology',   'Head Office', 'Full-Time', 'L6', '2018-07-01', $empMap['EMP-0001'] ?? 1],
            [$empMap['EMP-0004'] ?? 4, 'Sales Manager',        'Sales',       'Business',     'Mumbai',      'Full-Time', 'L6', '2019-11-10', $empMap['EMP-0001'] ?? 1],
            [$empMap['EMP-0005'] ?? 5, 'Senior Developer',     'Engineering', 'Technology',   'Head Office', 'Full-Time', 'L4', '2021-04-20', $empMap['EMP-0003'] ?? 3],
            [$empMap['EMP-0006'] ?? 6, 'Frontend Developer',   'Engineering', 'Technology',   'Head Office', 'Full-Time', 'L3', '2022-09-01', $empMap['EMP-0003'] ?? 3],
            [$empMap['EMP-0007'] ?? 7, 'Backend Developer',    'Engineering', 'Technology',   'Bangalore',   'Full-Time', 'L3', '2021-12-15', $empMap['EMP-0003'] ?? 3],
            [$empMap['EMP-0008'] ?? 8, 'Sales Executive',      'Sales',       'Business',     'Mumbai',      'Full-Time', 'L2', '2023-06-01', $empMap['EMP-0004'] ?? 4],
            [$empMap['EMP-0009'] ?? 9, 'Sales Executive',      'Sales',       'Business',     'Chennai',     'Full-Time', 'L2', '2022-02-14', $empMap['EMP-0004'] ?? 4],
        ];

        $jobRows = [];
        foreach ($jobData as $j) {
            $jobRows[] = [
                'employee_id'          => $j[0],
                'designation'          => $j[1],
                'department'           => $j[2],
                'business_unit'        => $j[3],
                'location'             => $j[4],
                'employment_type'      => $j[5],
                'grade'                => $j[6],
                'joined_date'          => $j[7],
                'reporting_manager_id' => $j[8],
                'created_at'           => $now,
                'updated_at'           => $now,
            ];
        }

        $this->db->table('job_information')->insertBatch($jobRows);
        echo "  ✓ Seeded " . \count($jobRows) . " job information records.\n";

        // ----------------------------------------------------------------
        // 4. Org Hierarchy
        // ----------------------------------------------------------------
        $orgRows = [
            [$empMap['EMP-0001'] ?? 1, null,                   'IT',          'Technology',  1, 1, 9, 'EMP-0001'],
            [$empMap['EMP-0002'] ?? 2, $empMap['EMP-0001'] ?? 1,'HR',         'Corporate',   2, 1, 1, 'EMP-0001/EMP-0002'],
            [$empMap['EMP-0003'] ?? 3, $empMap['EMP-0001'] ?? 1,'Engineering','Technology',  2, 1, 3, 'EMP-0001/EMP-0003'],
            [$empMap['EMP-0004'] ?? 4, $empMap['EMP-0001'] ?? 1,'Sales',      'Business',    2, 1, 2, 'EMP-0001/EMP-0004'],
            [$empMap['EMP-0005'] ?? 5, $empMap['EMP-0003'] ?? 3,'Engineering','Technology',  3, 0, 0, 'EMP-0001/EMP-0003/EMP-0005'],
            [$empMap['EMP-0006'] ?? 6, $empMap['EMP-0003'] ?? 3,'Engineering','Technology',  3, 0, 0, 'EMP-0001/EMP-0003/EMP-0006'],
            [$empMap['EMP-0007'] ?? 7, $empMap['EMP-0003'] ?? 3,'Engineering','Technology',  3, 0, 0, 'EMP-0001/EMP-0003/EMP-0007'],
            [$empMap['EMP-0008'] ?? 8, $empMap['EMP-0004'] ?? 4,'Sales',      'Business',    3, 0, 0, 'EMP-0001/EMP-0004/EMP-0008'],
            [$empMap['EMP-0009'] ?? 9, $empMap['EMP-0004'] ?? 4,'Sales',      'Business',    3, 0, 0, 'EMP-0001/EMP-0004/EMP-0009'],
        ];

        $orgInsert = [];
        foreach ($orgRows as $o) {
            $orgInsert[] = [
                'employee_id'    => $o[0],
                'parent_id'      => $o[1],
                'department'     => $o[2],
                'division'       => $o[3],
                'org_level'      => $o[4],
                'is_manager'     => $o[5],
                'team_size'      => $o[6],
                'hierarchy_path' => $o[7],
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        $this->db->table('org_hierarchy')->insertBatch($orgInsert);
        echo "  ✓ Seeded " . \count($orgInsert) . " org hierarchy records.\n";
    }
}
