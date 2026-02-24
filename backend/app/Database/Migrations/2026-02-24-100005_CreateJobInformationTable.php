<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJobInformationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'designation' => ['type' => 'VARCHAR', 'constraint' => 100],
            'department' => ['type' => 'VARCHAR', 'constraint' => 100],
            'grade' => ['type' => 'VARCHAR', 'constraint' => 50],
            'job_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'employment_type' => ['type' => 'ENUM', 'constraint' => ['Full-Time', 'Part-Time', 'Contract', 'Temporary', 'Intern'], 'default' => 'Full-Time'],
            'employment_status' => ['type' => 'ENUM', 'constraint' => ['Active', 'On Leave', 'Suspended', 'Terminated'], 'default' => 'Active'],
            'salary_grade' => ['type' => 'VARCHAR', 'constraint' => 50],
            'joined_date' => ['type' => 'DATE'],
            'confirmation_date' => ['type' => 'DATE', 'null' => true],
            'reporting_manager_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'functional_manager_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 100],
            'cost_center' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'business_unit' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_job_info_employee');
        $this->forge->addUniqueKey('employee_id');
        $this->forge->addKey('department');
        $this->forge->addKey('reporting_manager_id');
        $this->forge->createTable('job_information', true);
    }

    public function down()
    {
        $this->forge->dropTable('job_information', true);
    }
}
