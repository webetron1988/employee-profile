<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransfersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'previous_department' => ['type' => 'VARCHAR', 'constraint' => 100],
            'new_department' => ['type' => 'VARCHAR', 'constraint' => 100],
            'previous_location' => ['type' => 'VARCHAR', 'constraint' => 100],
            'new_location' => ['type' => 'VARCHAR', 'constraint' => 100],
            'transfer_type' => ['type' => 'ENUM', 'constraint' => ['Departmental', 'Geographical', 'Both'], 'default' => 'Departmental'],
            'transfer_date' => ['type' => 'DATE'],
            'effective_date' => ['type' => 'DATE'],
            'transfer_reason' => ['type' => 'TEXT', 'null' => true],
            'approval_status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Rejected'], 'default' => 'Pending'],
            'approved_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_date' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_transfer_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('transfer_date');
        $this->forge->createTable('transfers', true);
    }

    public function down()
    {
        $this->forge->dropTable('transfers', true);
    }
}
