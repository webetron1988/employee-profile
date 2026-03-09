<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFlsaEeoToJobInformation extends Migration
{
    public function up()
    {
        $this->forge->addColumn('job_information', [
            'flsa_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'business_unit'],
            'eeo_category' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'flsa_status'],
            'job_family' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'eeo_category'],
            'job_sub_family' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'job_family'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('job_information', ['flsa_status', 'eeo_category', 'job_family', 'job_sub_family']);
    }
}
