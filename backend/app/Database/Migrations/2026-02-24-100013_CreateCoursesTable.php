<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoursesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'course_code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'description' => ['type' => 'LONGTEXT', 'null' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 100],
            'course_type' => ['type' => 'ENUM', 'constraint' => ['Online', 'Classroom', 'Hybrid', 'Self-Paced'], 'default' => 'Online'],
            'duration_hours' => ['type' => 'INT', 'constraint' => 11],
            'is_mandatory' => ['type' => 'BOOLEAN', 'default' => 0],
            'passing_score' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'cost' => ['type' => 'DECIMAL', 'constraint' => [10,2], 'null' => true],
            'skill_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'competency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Active', 'Inactive', 'Archived'], 'default' => 'Active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('skill_id', 'skills', 'id', 'SET NULL', 'CASCADE', 'fk_course_skill');
        $this->forge->addForeignKey('competency_id', 'competencies', 'id', 'SET NULL', 'CASCADE', 'fk_course_competency');
        $this->forge->addKey('course_code');
        $this->forge->createTable('courses', true);
    }

    public function down()
    {
        $this->forge->dropTable('courses', true);
    }
}
