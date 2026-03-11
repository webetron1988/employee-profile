<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEpAwardsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'hrms_employee_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'source' => ['type' => 'ENUM', 'constraint' => ['ep', 'hrms_override'], 'default' => 'ep'],
            'hrms_original_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'award_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'award_date' => ['type' => 'DATE', 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'award_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'awarded_by' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'reward_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->addKey('hrms_employee_id');
        $this->forge->addKey('hrms_original_id');
        $this->forge->createTable('ep_awards');
    }

    public function down()
    {
        $this->forge->dropTable('ep_awards');
    }
}
