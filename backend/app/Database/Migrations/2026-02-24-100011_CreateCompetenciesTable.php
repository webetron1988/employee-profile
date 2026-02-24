<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetenciesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'competency_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'competency_category' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'LONGTEXT', 'null' => true],
            'proficiency_levels' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Active', 'Inactive'], 'default' => 'Active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('competency_category');
        $this->forge->addUniqueKey('competency_name');
        $this->forge->createTable('competencies', true);
    }

    public function down()
    {
        $this->forge->dropTable('competencies', true);
    }
}
