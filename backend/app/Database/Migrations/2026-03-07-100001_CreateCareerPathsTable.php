<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCareerPathsTable extends Migration
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
            'position_title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'grade_level' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'timeline' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'e.g., Current, 1-2 Years, 3-5 Years',
            ],
            'is_current' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'sort_order' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
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
        $this->forge->createTable('career_paths', true);
    }

    public function down()
    {
        $this->forge->dropTable('career_paths', true);
    }
}
