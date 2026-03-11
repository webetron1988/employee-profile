<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEpWorkExperienceTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'hrms_employee_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'source' => ['type' => 'ENUM', 'constraint' => ['ep', 'hrms_override'], 'default' => 'ep'],
            'hrms_original_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'designation' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'is_current' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('hrms_employee_id');
        $this->forge->addKey('hrms_original_id');
        $this->forge->createTable('ep_work_experience');
    }

    public function down()
    {
        $this->forge->dropTable('ep_work_experience');
    }
}
