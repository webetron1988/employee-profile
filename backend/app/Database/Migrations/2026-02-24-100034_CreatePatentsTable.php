<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'filing_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Granted', 'Rejected'], 'default' => 'Pending'],
            'patent_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reward_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('patents');
    }

    public function down()
    {
        $this->forge->dropTable('patents');
    }
}
