<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFamilyDependentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'relationship' => ['type' => 'ENUM', 'constraint' => ['Spouse', 'Child', 'Parent', 'Sibling', 'Other'], 'default' => 'Other'],
            'date_of_birth' => ['type' => 'DATE', 'null' => true],
            'gender' => ['type' => 'ENUM', 'constraint' => ['Male', 'Female', 'Other'], 'null' => true],
            'contact_number' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'occupation' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'education_level' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'dependent_for_insurance' => ['type' => 'BOOLEAN', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_family_employee');
        $this->forge->addKey('employee_id');
        $this->forge->createTable('family_dependents', true);
    }

    public function down()
    {
        $this->forge->dropTable('family_dependents', true);
    }
}
