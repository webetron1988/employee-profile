<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhysicalLocationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'office_name'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'building'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'floor'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'desk'             => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'work_arrangement' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'office_days'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'time_zone'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'country'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'region'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('physical_locations');
    }

    public function down()
    {
        $this->forge->dropTable('physical_locations');
    }
}
