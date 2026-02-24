<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAddressesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'address_type' => ['type' => 'ENUM', 'constraint' => ['Residential', 'Permanent', 'Official', 'Other'], 'default' => 'Residential'],
            'street_address' => ['type' => 'VARCHAR', 'constraint' => 200],
            'city' => ['type' => 'VARCHAR', 'constraint' => 100],
            'state' => ['type' => 'VARCHAR', 'constraint' => 100],
            'postal_code' => ['type' => 'VARCHAR', 'constraint' => 20],
            'country' => ['type' => 'VARCHAR', 'constraint' => 100],
            'is_primary' => ['type' => 'BOOLEAN', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_address_employee');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('addresses', true);
    }

    public function down()
    {
        $this->forge->dropTable('addresses', true);
    }
}
