<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrainingHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'training_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'training_type' => ['type' => 'ENUM', 'constraint' => ['Technical', 'Behavioral', 'Compliance', 'Leadership', 'Soft Skills', 'Other'], 'default' => 'Technical'],
            'training_provider' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'training_date' => ['type' => 'DATE'],
            'duration_hours' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'location' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'mode' => ['type' => 'ENUM', 'constraint' => ['Online', 'Classroom', 'Hybrid', 'On-the-job'], 'default' => 'Classroom'],
            'cost' => ['type' => 'DECIMAL', 'constraint' => [10,2], 'null' => true],
            'trainer_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'assessment_score' => ['type' => 'DECIMAL', 'constraint' => [5,2], 'null' => true],
            'certificate_obtained' => ['type' => 'BOOLEAN', 'default' => 0],
            'certificate_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'feedback' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_training_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('training_date');
        $this->forge->createTable('training_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('training_history', true);
    }
}
