<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLanguagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'language' => ['type' => 'VARCHAR', 'constraint' => 100],
            'proficiency' => ['type' => 'ENUM', 'constraint' => ['Native', 'Fluent', 'Intermediate', 'Basic'], 'default' => 'Basic'],
            'can_read' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_write' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_speak' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('languages');
    }

    public function down()
    {
        $this->forge->dropTable('languages');
    }
}
