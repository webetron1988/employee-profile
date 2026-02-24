<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePersonalDetailsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'gender' => [
                'type' => 'ENUM',
                'constraint' => ['Male', 'Female', 'Other', 'Prefer Not to Say'],
                'null' => true,
            ],
            'marital_status' => [
                'type' => 'ENUM',
                'constraint' => ['Single', 'Married', 'Widowed', 'Divorced'],
                'null' => true,
            ],
            'blood_group' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true,
            ],
            'religion' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'passport_number_encrypted' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'passport_expiry' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'visa_status' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'work_authorization_number_encrypted' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'work_authorization_expiry' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'employee_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('personal_details');
    }

    public function down()
    {
        $this->forge->dropTable('personal_details');
    }
}
