<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComplianceDocumentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'document_type' => ['type' => 'ENUM', 'constraint' => ['NDA', 'Non-Compete', 'Confidentiality', 'Policy Acknowledgment', 'Background Check', 'Medical Certificate'], 'default' => 'Policy Acknowledgment'],
            'document_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'document_url' => ['type' => 'VARCHAR', 'constraint' => 255],
            'issue_date' => ['type' => 'DATE'],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Signed', 'Pending', 'Expired', 'Renewed'], 'default' => 'Pending'],
            'signed_date' => ['type' => 'DATE', 'null' => true],
            'signed_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'comments' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_compliance_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('document_type');
        $this->forge->addKey('status');
        $this->forge->createTable('compliance_documents', true);
    }

    public function down()
    {
        $this->forge->dropTable('compliance_documents', true);
    }
}
