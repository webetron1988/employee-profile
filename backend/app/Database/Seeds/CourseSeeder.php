<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the learning course catalog with sample courses across departments.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            // Onboarding
            [
                'course_name'     => 'New Employee Orientation',
                'course_code'     => 'OB-001',
                'course_type'     => 'Online',
                'provider'        => 'Internal HR',
                'description'     => 'Company policies, culture, systems overview for new joiners',
                'duration_hours'  => 8,
                'cost'            => 0,
                'passing_score'   => 70,
                'is_mandatory'    => 1,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'Code of Conduct & Ethics',
                'course_code'     => 'OB-002',
                'course_type'     => 'Online',
                'provider'        => 'Internal HR',
                'description'     => 'Company code of conduct, ethics policy, and compliance essentials',
                'duration_hours'  => 2,
                'cost'            => 0,
                'passing_score'   => 80,
                'is_mandatory'    => 1,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'Information Security Awareness',
                'course_code'     => 'OB-003',
                'course_type'     => 'Online',
                'provider'        => 'Internal IT',
                'description'     => 'Data protection, password policy, phishing awareness',
                'duration_hours'  => 3,
                'cost'            => 0,
                'passing_score'   => 75,
                'is_mandatory'    => 1,
                'status'          => 'Active',
            ],

            // Technical
            [
                'course_name'     => 'PHP 8 & CodeIgniter 4 Fundamentals',
                'course_code'     => 'TEC-101',
                'course_type'     => 'Online',
                'provider'        => 'Udemy',
                'description'     => 'Modern PHP 8 features and building REST APIs with CodeIgniter 4',
                'duration_hours'  => 20,
                'cost'            => 2500,
                'passing_score'   => 70,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'React.js — Complete Guide',
                'course_code'     => 'TEC-102',
                'course_type'     => 'Online',
                'provider'        => 'Udemy',
                'description'     => 'Hooks, Redux Toolkit, TypeScript, and real-world app projects',
                'duration_hours'  => 40,
                'cost'            => 2500,
                'passing_score'   => 70,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'MySQL Performance Tuning',
                'course_code'     => 'TEC-103',
                'course_type'     => 'Online',
                'provider'        => 'Pluralsight',
                'description'     => 'Query optimisation, indexing strategies, and EXPLAIN plans',
                'duration_hours'  => 12,
                'cost'            => 3000,
                'passing_score'   => 75,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'Docker & Kubernetes for Developers',
                'course_code'     => 'TEC-104',
                'course_type'     => 'Online',
                'provider'        => 'Linux Foundation',
                'description'     => 'Containerise applications and deploy on Kubernetes clusters',
                'duration_hours'  => 30,
                'cost'            => 5000,
                'passing_score'   => 70,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'AWS Solutions Architect — Associate',
                'course_code'     => 'TEC-105',
                'course_type'     => 'Online',
                'provider'        => 'AWS Training',
                'description'     => 'Cloud architecture patterns and AWS core services',
                'duration_hours'  => 40,
                'cost'            => 8000,
                'passing_score'   => 72,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'RESTful API Security',
                'course_code'     => 'TEC-106',
                'course_type'     => 'Online',
                'provider'        => 'SANS Institute',
                'description'     => 'JWT, OAuth2, OWASP API Top 10, rate limiting',
                'duration_hours'  => 16,
                'cost'            => 6000,
                'passing_score'   => 80,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],

            // Leadership & Management
            [
                'course_name'     => 'First-Time Manager Programme',
                'course_code'     => 'LDR-201',
                'course_type'     => 'Classroom',
                'provider'        => 'Internal L&D',
                'description'     => 'Transition from individual contributor to people manager',
                'duration_hours'  => 16,
                'cost'            => 0,
                'passing_score'   => 60,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'Coaching & Feedback Skills',
                'course_code'     => 'LDR-202',
                'course_type'     => 'Classroom',
                'provider'        => 'Internal L&D',
                'description'     => 'Techniques for giving constructive feedback and coaching teams',
                'duration_hours'  => 8,
                'cost'            => 0,
                'passing_score'   => 60,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'Strategic Planning & OKRs',
                'course_code'     => 'LDR-203',
                'course_type'     => 'Online',
                'provider'        => 'Coursera',
                'description'     => 'Setting OKRs, cascading goals, and tracking business outcomes',
                'duration_hours'  => 10,
                'cost'            => 1500,
                'passing_score'   => 70,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],

            // HR & Compliance
            [
                'course_name'     => 'Prevention of Sexual Harassment (POSH)',
                'course_code'     => 'COM-301',
                'course_type'     => 'Online',
                'provider'        => 'Internal HR',
                'description'     => 'POSH Act awareness, reporting procedures, and responsibilities',
                'duration_hours'  => 2,
                'cost'            => 0,
                'passing_score'   => 80,
                'is_mandatory'    => 1,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'GDPR & Data Privacy',
                'course_code'     => 'COM-302',
                'course_type'     => 'Online',
                'provider'        => 'Internal Legal',
                'description'     => 'Data protection regulations, employee rights, and GDPR obligations',
                'duration_hours'  => 3,
                'cost'            => 0,
                'passing_score'   => 75,
                'is_mandatory'    => 1,
                'status'          => 'Active',
            ],

            // Soft Skills
            [
                'course_name'     => 'Effective Business Communication',
                'course_code'     => 'SS-401',
                'course_type'     => 'Online',
                'provider'        => 'LinkedIn Learning',
                'description'     => 'Written communication, presentation skills, and stakeholder management',
                'duration_hours'  => 6,
                'cost'            => 800,
                'passing_score'   => 65,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
            [
                'course_name'     => 'Agile & Scrum Fundamentals',
                'course_code'     => 'SS-402',
                'course_type'     => 'Online',
                'provider'        => 'Scrum Alliance',
                'description'     => 'Agile values, Scrum ceremonies, roles, and artefacts',
                'duration_hours'  => 8,
                'cost'            => 1200,
                'passing_score'   => 70,
                'is_mandatory'    => 0,
                'status'          => 'Active',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($courses as &$c) {
            $c['created_at'] = $now;
            $c['updated_at'] = $now;
        }

        $this->db->table('courses')->insertBatch($courses);
        echo "  ✓ Seeded " . count($courses) . " courses.\n";
    }
}
