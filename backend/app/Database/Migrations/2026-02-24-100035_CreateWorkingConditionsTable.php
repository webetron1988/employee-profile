<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWorkingConditionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'accommodation_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'accommodation_type' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'special_equipment' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_ergonomic_assessment' => ['type' => 'DATE', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('working_conditions');
    }

    public function down()
    {
        $this->forge->dropTable('working_conditions');
    }
}
