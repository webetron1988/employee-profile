<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReportingFieldsToJobInformation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('job_information', [
            'reporting_since' => ['type' => 'DATE', 'null' => true, 'after' => 'reporting_manager_id'],
            'matrix_relationship' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'functional_manager_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('job_information', ['reporting_since', 'matrix_relationship']);
    }
}
