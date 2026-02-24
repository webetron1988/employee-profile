<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeCompetenciesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'competency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'proficiency_level' => ['type' => 'INT', 'constraint' => 11],
            'self_assessment' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'manager_assessment' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'development_goal' => ['type' => 'TEXT', 'null' => true],
            'assessment_date' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_emp_comp_employee');
        $this->forge->addForeignKey('competency_id', 'competencies', 'id', 'CASCADE', 'CASCADE', 'fk_emp_comp_competency');
        $this->forge->addKey('employee_id');
        $this->forge->addUniqueKey(['employee_id', 'competency_id']);
        $this->forge->createTable('employee_competencies', true);
    }

    public function down()
    {
        $this->forge->dropTable('employee_competencies', true);
    }
}
