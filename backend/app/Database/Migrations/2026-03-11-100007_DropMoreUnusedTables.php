<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drop additional unused tables:
 * - courses: 0 rows, not used by frontend
 * - course_enrollments: 0 rows, depends on empty courses
 * - skills: 0 rows, superseded by HRMS skills via HrmsData
 * - gdpr_consents: 0 rows, never populated
 * - sync_logs: 0 rows, never populated
 */
class DropMoreUnusedTables extends Migration
{
    public function up()
    {
        $this->forge->dropTable('course_enrollments', true);
        $this->forge->dropTable('courses', true);
        $this->forge->dropTable('skills', true);
        $this->forge->dropTable('gdpr_consents', true);
        $this->forge->dropTable('sync_logs', true);
    }

    public function down()
    {
        // Tables are obsolete; restore from backup if needed.
    }
}
