<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Master seeder — runs all seeders in dependency order.
 * Usage: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('SkillSeeder');
        $this->call('CompetencySeeder');
        $this->call('CourseSeeder');
        $this->call('SystemConfigSeeder');
        $this->call('EmployeeSeeder');
    }
}
