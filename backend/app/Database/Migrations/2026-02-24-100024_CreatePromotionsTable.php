<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePromotionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'previous_designation' => ['type' => 'VARCHAR', 'constraint' => 100],
            'new_designation' => ['type' => 'VARCHAR', 'constraint' => 100],
            'previous_grade' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'new_grade' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'promotion_date' => ['type' => 'DATE'],
            'effective_date' => ['type' => 'DATE'],
            'promotion_reason' => ['type' => 'ENUM', 'constraint' => ['Merit', 'Seniority', 'Vacancy', 'Other'], 'default' => 'Merit'],
            'salary_increment_percentage' => ['type' => 'DECIMAL', 'constraint' => [5,2], 'null' => true],
            'approval_status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Rejected'], 'default' => 'Pending'],
            'approved_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_date' => ['type' => 'DATE', 'null' => true],
            'comments' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_promotion_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('promotion_date');
        $this->forge->createTable('promotions', true);
    }

    public function down()
    {
        $this->forge->dropTable('promotions', true);
    }
}
