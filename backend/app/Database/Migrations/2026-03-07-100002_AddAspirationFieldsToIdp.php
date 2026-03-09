<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAspirationFieldsToIdp extends Migration
{
    public function up()
    {
        $this->forge->addColumn('individual_development_plan', [
            'timeline' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'career_goal',
            ],
            'readiness_level' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'timeline',
            ],
            'preferred_track' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'readiness_level',
            ],
            'geographic_preference' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'preferred_track',
            ],
            'functional_interest' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'geographic_preference',
            ],
            'international_assignment' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'functional_interest',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('individual_development_plan', [
            'timeline',
            'readiness_level',
            'preferred_track',
            'geographic_preference',
            'functional_interest',
            'international_assignment',
        ]);
    }
}
