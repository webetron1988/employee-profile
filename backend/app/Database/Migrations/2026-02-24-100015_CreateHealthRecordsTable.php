<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHealthRecordsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'blood_group' => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'allergies' => ['type' => 'TEXT', 'null' => true],
            'chronic_conditions' => ['type' => 'TEXT', 'null' => true],
            'medications' => ['type' => 'TEXT', 'null' => true],
            'health_insurance_provider' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'health_insurance_number_encrypted' => ['type' => 'LONGTEXT', 'null' => true],
            'emergency_contact_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'emergency_contact_phone' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'emergency_contact_relation' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'last_medical_checkup_date' => ['type' => 'DATE', 'null' => true],
            'medical_notes' => ['type' => 'LONGTEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_health_employee');
        $this->forge->addUniqueKey('employee_id');
        $this->forge->createTable('health_records', true);
    }

    public function down()
    {
        $this->forge->dropTable('health_records', true);
    }
}
