<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCertificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'certification_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'certification_code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'issuing_organization' => ['type' => 'VARCHAR', 'constraint' => 100],
            'issue_date' => ['type' => 'DATE'],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'certificate_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'certificate_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Active', 'Expired', 'Revoked', 'Pending'], 'default' => 'Active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_cert_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('expiry_date');
        $this->forge->createTable('certifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('certifications', true);
    }
}
