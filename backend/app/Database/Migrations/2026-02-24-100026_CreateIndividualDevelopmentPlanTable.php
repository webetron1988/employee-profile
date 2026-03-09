<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIndividualDevelopmentPlanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_year' => ['type' => 'INT', 'constraint' => 4],
            'career_goal' => ['type' => 'LONGTEXT', 'null' => true],
            'skill_gaps' => ['type' => 'LONGTEXT', 'null' => true],
            'development_activities' => ['type' => 'LONGTEXT', 'null' => true],
            'training_needs' => ['type' => 'LONGTEXT', 'null' => true],
            'mentor_assigned_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Draft', 'In Progress', 'Completed', 'Postponed'], 'default' => 'Draft'],
            'reviewed_by_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reviewed_date' => ['type' => 'DATE', 'null' => true],
            'comments' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE', 'fk_idp_employee');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('plan_year');
        $this->forge->createTable('individual_development_plan', true);
    }

    public function down()
    {
        $this->forge->dropTable('individual_development_plan', true);
    }
}
