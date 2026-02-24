<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGovtIdsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'id_type' => ['type' => 'ENUM', 'constraint' => ['Aadhaar', 'PAN', 'Passport', 'Driving License', 'Voter ID', 'Other'], 'default' => 'Aadhaar'],
            'id_number_encrypted' => ['type' => 'LONGTEXT'],
            'id_number_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'issue_date' => ['type' => 'DATE', 'null' => true],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'issuing_country' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'issuing_authority' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'document_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_primary' => ['type' => 'BOOLEAN', 'default' => 0],
            'verified' => ['type' => 'BOOLEAN', 'default' => 0],
            'verification_date' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_govt_id_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addUniqueKey('id_number_hash');
        $this->forge->createTable('govt_ids', true);
    }

    public function down()
    {
        $this->forge->dropTable('govt_ids', true);
    }
}
