<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerformanceReviewsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'reviewer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'review_period' => ['type' => 'VARCHAR', 'constraint' => 50],
            'review_date' => ['type' => 'DATE'],
            'overall_rating' => ['type' => 'DECIMAL', 'constraint' => [3,2]],
            'performance_status' => ['type' => 'ENUM', 'constraint' => ['Excellent', 'Good', 'Average', 'Below Average', 'Poor'], 'default' => 'Average'],
            'strengths' => ['type' => 'TEXT', 'null' => true],
            'areas_for_improvement' => ['type' => 'TEXT', 'null' => true],
            'goals_met' => ['type' => 'TEXT', 'null' => true],
            'comments' => ['type' => 'LONGTEXT', 'null' => true],
            'approval_status' => ['type' => 'ENUM', 'constraint' => ['Draft', 'Pending Approval', 'Approved', 'Rejected'], 'default' => 'Draft'],
            'approved_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_perf_review_employee');
        $this->forge->addForeignKey('reviewer_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_perf_review_reviewer');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('review_date');
        $this->forge->createTable('performance_reviews', true);
    }

    public function down()
    {
        $this->forge->dropTable('performance_reviews', true);
    }
}
