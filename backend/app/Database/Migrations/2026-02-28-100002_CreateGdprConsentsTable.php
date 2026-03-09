<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGdprConsentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'consent_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'comment'    => 'data_processing, data_sharing, marketing, analytics',
            ],
            'consent_given' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'consent_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'withdrawal_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'consent_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => '1.0',
                'comment'    => 'Version of consent policy accepted',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['employee_id', 'consent_type']);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('gdpr_consents', true);
    }

    public function down()
    {
        $this->forge->dropTable('gdpr_consents', true);
    }
}
