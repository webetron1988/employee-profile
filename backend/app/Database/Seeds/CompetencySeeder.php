<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the competency framework.
 */
class CompetencySeeder extends Seeder
{
    public function run(): void
    {
        $competencies = [
            // Core Competencies
            [
                'competency_name'     => 'Strategic Thinking',
                'competency_category' => 'Core',
                'description'         => 'Ability to set long-term goals and develop plans to achieve them',
                'proficiency_levels'  => json_encode([
                    1 => 'Understands basic strategy concepts',
                    2 => 'Contributes to departmental planning',
                    3 => 'Develops functional strategy',
                    4 => 'Shapes organisational direction',
                    5 => 'Drives enterprise-wide strategic vision',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Communication',
                'competency_category' => 'Core',
                'description'         => 'Clear and effective written and verbal communication across all levels',
                'proficiency_levels'  => json_encode([
                    1 => 'Communicates clearly within team',
                    2 => 'Presents ideas effectively to peers',
                    3 => 'Facilitates cross-team discussions',
                    4 => 'Influences senior stakeholders',
                    5 => 'Represents organisation externally',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Problem Solving',
                'competency_category' => 'Core',
                'description'         => 'Identifies root causes and develops effective solutions',
                'proficiency_levels'  => json_encode([
                    1 => 'Solves routine problems with guidance',
                    2 => 'Independently solves moderate complexity issues',
                    3 => 'Tackles complex, multi-faceted problems',
                    4 => 'Resolves organisational-level challenges',
                    5 => 'Creates frameworks to prevent systemic problems',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Collaboration',
                'competency_category' => 'Core',
                'description'         => 'Works effectively across teams, functions, and geographies',
                'proficiency_levels'  => json_encode([
                    1 => 'Cooperates within immediate team',
                    2 => 'Builds relationships across teams',
                    3 => 'Leads cross-functional initiatives',
                    4 => 'Builds strategic partnerships',
                    5 => 'Creates culture of collaboration',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Customer Focus',
                'competency_category' => 'Core',
                'description'         => 'Understands and delivers on customer needs and expectations',
                'proficiency_levels'  => json_encode([
                    1 => 'Responds to customer requests',
                    2 => 'Anticipates customer needs',
                    3 => 'Develops customer-centric solutions',
                    4 => 'Builds lasting customer partnerships',
                    5 => 'Drives customer-first culture',
                ]),
                'status' => 'Active',
            ],

            // Leadership Competencies
            [
                'competency_name'     => 'People Development',
                'competency_category' => 'Leadership',
                'description'         => 'Coaches and develops others to reach their full potential',
                'proficiency_levels'  => json_encode([
                    1 => 'Provides basic on-the-job guidance',
                    2 => 'Actively mentors team members',
                    3 => 'Builds high-performing teams',
                    4 => 'Develops future leaders',
                    5 => 'Creates a learning organisation',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Decision Making',
                'competency_category' => 'Leadership',
                'description'         => 'Makes timely, data-informed decisions aligned to business goals',
                'proficiency_levels'  => json_encode([
                    1 => 'Makes routine decisions with supervision',
                    2 => 'Takes ownership of team-level decisions',
                    3 => 'Makes complex, high-impact decisions',
                    4 => 'Decides under uncertainty at function level',
                    5 => 'Shapes governance and decision frameworks',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Change Management',
                'competency_category' => 'Leadership',
                'description'         => 'Leads and adapts through organisational change effectively',
                'proficiency_levels'  => json_encode([
                    1 => 'Adapts to change with support',
                    2 => 'Embraces and supports change initiatives',
                    3 => 'Leads team through change',
                    4 => 'Drives functional transformation',
                    5 => 'Architects large-scale organisational change',
                ]),
                'status' => 'Active',
            ],

            // Technical Competencies
            [
                'competency_name'     => 'Technical Expertise',
                'competency_category' => 'Technical',
                'description'         => 'Depth of technical knowledge and application in role',
                'proficiency_levels'  => json_encode([
                    1 => 'Has foundational technical knowledge',
                    2 => 'Applies technical skills independently',
                    3 => 'Subject matter expert in domain',
                    4 => 'Cross-domain technical authority',
                    5 => 'Industry-recognised technical thought leader',
                ]),
                'status' => 'Active',
            ],
            [
                'competency_name'     => 'Data & Analytics',
                'competency_category' => 'Technical',
                'description'         => 'Ability to collect, analyse, and derive insights from data',
                'proficiency_levels'  => json_encode([
                    1 => 'Reads and interprets basic reports',
                    2 => 'Performs data analysis with tools',
                    3 => 'Builds analytical models and dashboards',
                    4 => 'Designs data strategy for function',
                    5 => 'Drives enterprise analytics capability',
                ]),
                'status' => 'Active',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($competencies as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
        }

        $this->db->table('competencies')->insertBatch($competencies);
        echo "  ✓ Seeded " . count($competencies) . " competencies.\n";
    }
}
