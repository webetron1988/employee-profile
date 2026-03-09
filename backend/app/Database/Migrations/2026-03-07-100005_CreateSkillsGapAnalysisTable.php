<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSkillsGapAnalysisTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'target_role' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'skill_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'current_level' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
            ],
            'target_level' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
            ],
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['High', 'Medium', 'Low'],
                'default' => 'Medium',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('skills_gap_analysis', true);
    }

    public function down()
    {
        $this->forge->dropTable('skills_gap_analysis', true);
    }
}
