<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSkillsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'skill_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'skill_category' => ['type' => 'VARCHAR', 'constraint' => 100],
            'skill_level' => ['type' => 'INT', 'constraint' => 11],
            'description' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Active', 'Inactive'], 'default' => 'Active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('skill_category');
        $this->forge->addUniqueKey('skill_name');
        $this->forge->createTable('skills', true);
    }

    public function down()
    {
        $this->forge->dropTable('skills', true);
    }
}
