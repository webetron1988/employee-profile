<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerformanceGoalsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'goal_title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'goal_description' => ['type' => 'LONGTEXT'],
            'goal_category' => ['type' => 'ENUM', 'constraint' => ['Technical', 'Behavioral', 'Leadership', 'Personal Development'], 'default' => 'Technical'],
            'start_date' => ['type' => 'DATE'],
            'end_date' => ['type' => 'DATE'],
            'target_value' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'measurement_criteria' => ['type' => 'TEXT', 'null' => true],
            'weightage' => ['type' => 'DECIMAL', 'constraint' => [5,2], 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Not Started', 'In Progress', 'Completed', 'On Hold', 'Cancelled'], 'default' => 'Not Started'],
            'progress_percentage' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'achievement_percentage' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_goal_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('status');
        $this->forge->createTable('performance_goals', true);
    }

    public function down()
    {
        $this->forge->dropTable('performance_goals', true);
    }
}
