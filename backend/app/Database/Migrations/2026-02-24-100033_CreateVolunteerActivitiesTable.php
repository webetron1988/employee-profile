<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVolunteerActivitiesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'activity' => ['type' => 'VARCHAR', 'constraint' => 255],
            'organization' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'hours' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'period' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('volunteer_activities');
    }

    public function down()
    {
        $this->forge->dropTable('volunteer_activities');
    }
}
