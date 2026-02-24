<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBankDetailsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'bank_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'account_number_encrypted' => ['type' => 'LONGTEXT'],
            'account_number_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'account_type' => ['type' => 'ENUM', 'constraint' => ['Savings', 'Current', 'Other'], 'default' => 'Savings'],
            'ifsc_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'branch_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'account_holder_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'is_primary' => ['type' => 'BOOLEAN', 'default' => 1],
            'verified' => ['type' => 'BOOLEAN', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_bank_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addUniqueKey('account_number_hash');
        $this->forge->createTable('bank_details', true);
    }

    public function down()
    {
        $this->forge->dropTable('bank_details', true);
    }
}
