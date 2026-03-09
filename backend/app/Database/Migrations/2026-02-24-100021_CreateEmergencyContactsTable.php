<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmergencyContactsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'contact_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'relationship' => ['type' => 'VARCHAR', 'constraint' => 50],
            'phone_number' => ['type' => 'VARCHAR', 'constraint' => 20],
            'email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_primary' => ['type' => 'BOOLEAN', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_emergency_employee');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('emergency_contacts', true);
    }

    public function down()
    {
        $this->forge->dropTable('emergency_contacts', true);
    }
}
