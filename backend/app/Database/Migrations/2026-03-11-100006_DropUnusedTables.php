<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drop tables that are no longer used:
 * - employees: eliminated, replaced by users table
 * - employment_history: superseded by ep_work_experience + HRMS read
 * - employee_skills: superseded by ep_skills + HRMS read
 * - certifications: superseded by ep_certifications + HRMS read
 * - awards_recognition: superseded by ep_awards + HRMS read
 * - compliance_documents: never used by frontend
 */
class DropUnusedTables extends Migration
{
    public function up()
    {
        $this->forge->dropTable('employees', true);
        $this->forge->dropTable('employment_history', true);
        $this->forge->dropTable('employee_skills', true);
        $this->forge->dropTable('certifications', true);
        $this->forge->dropTable('awards_recognition', true);
        $this->forge->dropTable('compliance_documents', true);
    }

    public function down()
    {
        // These tables are obsolete; no rollback needed.
        // If needed, restore from backup or re-create via original migrations.
    }
}
