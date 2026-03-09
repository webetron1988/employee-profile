<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuccessionPlansTable extends Migration
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
            'plan_type' => [
                'type' => 'ENUM',
                'constraint' => ['emergency_successor', 'can_succeed_into', 'bench_strength'],
                'default' => 'bench_strength',
            ],
            'successor_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'successor_title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'target_position' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'target_holder_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'readiness' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'readiness_percentage' => [
                'type' => 'INT',
                'constraint' => 3,
                'unsigned' => true,
                'default' => 0,
            ],
            'critical_experiences' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'gaps' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'development_plan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'strengths' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'develop_areas' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'classification' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'review_date' => [
                'type' => 'DATE',
                'null' => true,
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
        $this->forge->addKey('employee_id');
        $this->forge->addKey('plan_type');
        $this->forge->createTable('succession_plans', true);
    }

    public function down()
    {
        $this->forge->dropTable('succession_plans', true);
    }
}
