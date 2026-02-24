<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAwardsRecognitionTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'award_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'award_category' => ['type' => 'ENUM', 'constraint' => ['Performance', 'Innovation', 'Safety', 'Customer Service', 'Teamwork', 'Leadership', 'Other'], 'default' => 'Performance'],
            'award_date' => ['type' => 'DATE'],
            'awarding_organization' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'award_description' => ['type' => 'LONGTEXT', 'null' => true],
            'monetary_reward' => ['type' => 'DECIMAL', 'constraint' => [10,2], 'null' => true],
            'certificate_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'recognized_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_award_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('award_date');
        $this->forge->createTable('awards_recognition', true);
    }

    public function down()
    {
        $this->forge->dropTable('awards_recognition', true);
    }
}
