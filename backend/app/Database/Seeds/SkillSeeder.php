<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the skills catalog with common technical and soft skills.
 */
class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            // Technical — Programming
            ['skill_name' => 'PHP',               'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'PHP programming language', 'status' => 'Active'],
            ['skill_name' => 'Python',             'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'Python programming language', 'status' => 'Active'],
            ['skill_name' => 'JavaScript',         'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'JavaScript / ES6+', 'status' => 'Active'],
            ['skill_name' => 'TypeScript',         'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'Typed superset of JavaScript', 'status' => 'Active'],
            ['skill_name' => 'Java',               'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'Java programming language', 'status' => 'Active'],
            ['skill_name' => 'C#',                 'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'Microsoft C#', 'status' => 'Active'],
            ['skill_name' => 'Go',                 'skill_category' => 'Technical', 'skill_level' => 'Advanced',     'description' => 'Go programming language', 'status' => 'Active'],
            ['skill_name' => 'React',              'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'React.js front-end library', 'status' => 'Active'],
            ['skill_name' => 'Vue.js',             'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'Vue.js front-end framework', 'status' => 'Active'],
            ['skill_name' => 'Node.js',            'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'Node.js runtime', 'status' => 'Active'],

            // Technical — Databases
            ['skill_name' => 'MySQL',              'skill_category' => 'Database',  'skill_level' => 'Intermediate', 'description' => 'MySQL relational database', 'status' => 'Active'],
            ['skill_name' => 'PostgreSQL',         'skill_category' => 'Database',  'skill_level' => 'Intermediate', 'description' => 'PostgreSQL database', 'status' => 'Active'],
            ['skill_name' => 'MongoDB',            'skill_category' => 'Database',  'skill_level' => 'Intermediate', 'description' => 'MongoDB NoSQL database', 'status' => 'Active'],
            ['skill_name' => 'Redis',              'skill_category' => 'Database',  'skill_level' => 'Intermediate', 'description' => 'Redis in-memory data store', 'status' => 'Active'],
            ['skill_name' => 'Elasticsearch',      'skill_category' => 'Database',  'skill_level' => 'Advanced',     'description' => 'Elasticsearch search engine', 'status' => 'Active'],

            // Technical — DevOps / Cloud
            ['skill_name' => 'Docker',             'skill_category' => 'DevOps',    'skill_level' => 'Intermediate', 'description' => 'Docker containerisation', 'status' => 'Active'],
            ['skill_name' => 'Kubernetes',         'skill_category' => 'DevOps',    'skill_level' => 'Advanced',     'description' => 'Kubernetes orchestration', 'status' => 'Active'],
            ['skill_name' => 'AWS',                'skill_category' => 'Cloud',     'skill_level' => 'Intermediate', 'description' => 'Amazon Web Services', 'status' => 'Active'],
            ['skill_name' => 'Azure',              'skill_category' => 'Cloud',     'skill_level' => 'Intermediate', 'description' => 'Microsoft Azure', 'status' => 'Active'],
            ['skill_name' => 'CI/CD',              'skill_category' => 'DevOps',    'skill_level' => 'Intermediate', 'description' => 'Continuous Integration & Deployment', 'status' => 'Active'],
            ['skill_name' => 'Git',                'skill_category' => 'DevOps',    'skill_level' => 'Beginner',     'description' => 'Git version control', 'status' => 'Active'],

            // Technical — Frameworks / Tools
            ['skill_name' => 'CodeIgniter 4',      'skill_category' => 'Framework', 'skill_level' => 'Intermediate', 'description' => 'CodeIgniter 4 PHP framework', 'status' => 'Active'],
            ['skill_name' => 'Laravel',            'skill_category' => 'Framework', 'skill_level' => 'Intermediate', 'description' => 'Laravel PHP framework', 'status' => 'Active'],
            ['skill_name' => 'REST API Design',    'skill_category' => 'Technical', 'skill_level' => 'Intermediate', 'description' => 'RESTful API design principles', 'status' => 'Active'],
            ['skill_name' => 'GraphQL',            'skill_category' => 'Technical', 'skill_level' => 'Advanced',     'description' => 'GraphQL API query language', 'status' => 'Active'],

            // Soft Skills
            ['skill_name' => 'Communication',      'skill_category' => 'Soft Skill','skill_level' => 'Beginner',     'description' => 'Written and verbal communication', 'status' => 'Active'],
            ['skill_name' => 'Leadership',         'skill_category' => 'Soft Skill','skill_level' => 'Intermediate', 'description' => 'Team leadership and mentoring', 'status' => 'Active'],
            ['skill_name' => 'Problem Solving',    'skill_category' => 'Soft Skill','skill_level' => 'Beginner',     'description' => 'Analytical problem solving', 'status' => 'Active'],
            ['skill_name' => 'Time Management',    'skill_category' => 'Soft Skill','skill_level' => 'Beginner',     'description' => 'Prioritisation and scheduling', 'status' => 'Active'],
            ['skill_name' => 'Agile / Scrum',      'skill_category' => 'Methodology','skill_level' => 'Intermediate','description' => 'Agile methodology and Scrum framework', 'status' => 'Active'],
            ['skill_name' => 'Project Management', 'skill_category' => 'Management','skill_level' => 'Intermediate', 'description' => 'Project planning and delivery', 'status' => 'Active'],

            // HR / Business
            ['skill_name' => 'HR Policy',          'skill_category' => 'HR',        'skill_level' => 'Beginner',     'description' => 'HR policies and compliance', 'status' => 'Active'],
            ['skill_name' => 'Recruitment',        'skill_category' => 'HR',        'skill_level' => 'Intermediate', 'description' => 'End-to-end recruitment process', 'status' => 'Active'],
            ['skill_name' => 'Performance Management','skill_category' => 'HR',     'skill_level' => 'Intermediate', 'description' => 'Performance appraisal and development', 'status' => 'Active'],
            ['skill_name' => 'Data Analysis',      'skill_category' => 'Analytics', 'skill_level' => 'Intermediate', 'description' => 'Data interpretation and insights', 'status' => 'Active'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($skills as &$skill) {
            $skill['created_at'] = $now;
            $skill['updated_at'] = $now;
        }

        $this->db->table('skills')->insertBatch($skills);
        echo "  ✓ Seeded " . count($skills) . " skills.\n";
    }
}
