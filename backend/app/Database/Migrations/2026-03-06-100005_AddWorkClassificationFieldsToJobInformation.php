<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkClassificationFieldsToJobInformation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('job_information', [
            'budget_authority'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'contract_end_date'],
            'signing_authority' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'budget_authority'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('job_information', ['budget_authority', 'signing_authority']);
    }
}
