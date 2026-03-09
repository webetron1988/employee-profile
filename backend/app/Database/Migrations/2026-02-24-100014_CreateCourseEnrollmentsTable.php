<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCourseEnrollmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'course_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'enrollment_date' => ['type' => 'DATE'],
            'scheduled_start_date' => ['type' => 'DATE', 'null' => true],
            'scheduled_end_date' => ['type' => 'DATE', 'null' => true],
            'actual_start_date' => ['type' => 'DATE', 'null' => true],
            'actual_end_date' => ['type' => 'DATE', 'null' => true],
            'completion_status' => ['type' => 'ENUM', 'constraint' => ['Not Started', 'In Progress', 'Completed', 'Dropped'], 'default' => 'Not Started'],
            'completion_percentage' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'score' => ['type' => 'DECIMAL', 'constraint' => [5,2], 'null' => true],
            'passing_score' => ['type' => 'DECIMAL', 'constraint' => [5,2], 'null' => true],
            'passed' => ['type' => 'BOOLEAN', 'null' => true],
            'certificate_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_enrollment_employee');
        $this->forge->addForeignKey('course_id', 'courses', 'id', 'CASCADE', 'CASCADE', 'fk_enrollment_course');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('course_id');
        $this->forge->addUniqueKey(['employee_id', 'course_id']);
        $this->forge->createTable('course_enrollments', true);
    }

    public function down()
    {
        $this->forge->dropTable('course_enrollments', true);
    }
}
