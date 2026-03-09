<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerformanceFeedbackTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'reviewer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'feedback_type' => ['type' => 'ENUM', 'constraint' => ['360 Degree', 'Manager', 'Self', 'Peer', 'Team'], 'default' => 'Manager'],
            'feedback_period' => ['type' => 'VARCHAR', 'constraint' => 50],
            'strengths' => ['type' => 'TEXT', 'null' => true],
            'areas_for_improvement' => ['type' => 'TEXT', 'null' => true],
            'suggestions' => ['type' => 'TEXT', 'null' => true],
            'overall_comment' => ['type' => 'LONGTEXT', 'null' => true],
            'rating' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'is_anonymous' => ['type' => 'BOOLEAN', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending Review', 'Shared with Employee', 'Acknowledged'], 'default' => 'Pending Review'],
            'shared_date' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_feedback_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('feedback_type');
        $this->forge->createTable('performance_feedback', true);
    }

    public function down()
    {
        $this->forge->dropTable('performance_feedback', true);
    }
}
