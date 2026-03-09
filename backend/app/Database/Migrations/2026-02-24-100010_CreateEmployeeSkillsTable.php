<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeSkillsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'skill_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'proficiency_level' => ['type' => 'ENUM', 'constraint' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'], 'default' => 'Intermediate'],
            'years_of_experience' => ['type' => 'DECIMAL', 'constraint' => [5,2], 'null' => true],
            'last_used_date' => ['type' => 'DATE', 'null' => true],
            'endorsements' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'verified' => ['type' => 'BOOLEAN', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_emp_skill_employee');
        $this->forge->addForeignKey('skill_id', 'skills', 'id', 'CASCADE', 'CASCADE', 'fk_emp_skill_skill');
        $this->forge->addKey('employee_id');
        $this->forge->addUniqueKey(['employee_id', 'skill_id']);
        $this->forge->createTable('employee_skills', true);
    }

    public function down()
    {
        $this->forge->dropTable('employee_skills', true);
    }
}
