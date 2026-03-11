<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEpSkillsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'hrms_employee_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'source' => ['type' => 'ENUM', 'constraint' => ['ep', 'hrms_override'], 'default' => 'ep'],
            'hrms_original_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'skill_title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'proficiency_level' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'proficiency_label' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'years_of_experience' => ['type' => 'DECIMAL', 'constraint' => '3,1', 'null' => true],
            'last_used_date' => ['type' => 'DATE', 'null' => true],
            'skill_ref_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'acquisition_source' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'certificate_validity' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('hrms_employee_id');
        $this->forge->addKey('hrms_original_id');
        $this->forge->createTable('ep_skills');
    }

    public function down()
    {
        $this->forge->dropTable('ep_skills');
    }
}
