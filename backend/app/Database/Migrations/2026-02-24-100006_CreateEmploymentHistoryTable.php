<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmploymentHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'designation' => ['type' => 'VARCHAR', 'constraint' => 100],
            'department' => ['type' => 'VARCHAR', 'constraint' => 100],
            'grade' => ['type' => 'VARCHAR', 'constraint' => 50],
            'reporting_manager_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 100],
            'employment_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'start_date' => ['type' => 'DATE'],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'reason_for_change' => ['type' => 'ENUM', 'constraint' => ['Promotion', 'Transfer', 'Demotion', 'Resignation', 'Retirement', 'Termination', 'Other'], 'null' => true],
            'approval_status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Rejected'], 'default' => 'Pending'],
            'comments' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_emp_history_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('start_date');
        $this->forge->createTable('employment_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('employment_history', true);
    }
}
