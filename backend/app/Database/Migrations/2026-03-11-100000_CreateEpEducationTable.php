<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEpEducationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'hrms_employee_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'source' => ['type' => 'ENUM', 'constraint' => ['ep', 'hrms_override'], 'default' => 'ep'],
            'hrms_original_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'degree_name' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'field_of_study' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'college_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'start_year' => ['type' => 'YEAR', 'null' => true],
            'end_year' => ['type' => 'YEAR', 'null' => true],
            'grade' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'honors' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'thesis' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('hrms_employee_id');
        $this->forge->addKey('hrms_original_id');
        $this->forge->createTable('ep_education');
    }

    public function down()
    {
        $this->forge->dropTable('ep_education');
    }
}
