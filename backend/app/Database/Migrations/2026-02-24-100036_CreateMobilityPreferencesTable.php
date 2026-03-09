<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMobilityPreferencesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'open_to_mobility' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'preferred_function' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'preferred_location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'preferred_role' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'available_from' => ['type' => 'DATE', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('mobility_preferences');
    }

    public function down()
    {
        $this->forge->dropTable('mobility_preferences');
    }
}
