<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrgStructureFieldsToJobInformation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('job_information', [
            'cost_centre_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'signing_authority'],
            'gl_code'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'cost_centre_name'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('job_information', ['cost_centre_name', 'gl_code']);
    }
}
