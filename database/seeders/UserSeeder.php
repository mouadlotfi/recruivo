<?php

namespace Database\Seeders;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating users and canonical accounts...');

        $this->ensureSampleResumes();

        $defaultPassword = bcrypt('password');

        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@recruivo.work'],
            [
                'name' => 'Admin User',
                'password' => $defaultPassword,
                'is_recruiter' => false,
                'is_demo' => true,
                'email_verified_at' => now()->subMonths(3),
                'location' => 'Casablanca, Morocco',
                'profile_summary' => 'System administrator managing recruitment platform operations, security, and infrastructure.',
            ]
        );
        $admin->syncRoles(['Admin']);
        $this->command->info('Admin user ready: admin@recruivo.work / password');

        // 2. Demo Recruiter for Aetheris Dynamics
        $companies = Company::query()->orderBy('id')->get();
        $firstCompany = $companies->firstWhere('slug', 'aetheris-dynamics') ?? $companies->first();

        $demoRecruiter = User::firstOrCreate(
            ['email' => 'recruiter@recruivo.work'],
            [
                'name' => 'Demo Recruiter',
                'password' => $defaultPassword,
                'is_recruiter' => true,
                'is_demo' => true,
                'company_id' => $firstCompany->id,
                'job_title' => 'Head of Talent Acquisition',
                'email_verified_at' => now()->subMonths(3),
                'location' => 'Dublin, Ireland',
                'phone' => '+353 1 496 0123',
                'profile_summary' => 'Head of Talent Acquisition at Aetheris Dynamics, scaling distributed cloud infrastructure and engineering teams across Europe and North America.',
            ]
        );
        $demoRecruiter->syncRoles(['Recruiter']);
        $this->seedRecruiterTemplates($demoRecruiter);
        $this->command->info('Demo recruiter ready: recruiter@recruivo.work / password');

        // 3. Recruiters for all companies
        $recruiterRoster = [
            'aetheris-dynamics' => [
                ['name' => 'Sarah Jenkins', 'email' => 'sarah.jenkins@aetheris-dynamics.example', 'title' => 'Senior Technical Recruiter', 'phone' => '+353 1 496 0145'],
                ['name' => 'Liam O’Donnell', 'email' => 'liam.odonnell@aetheris-dynamics.example', 'title' => 'Engineering Hiring Lead', 'phone' => '+353 1 496 0188'],
            ],
            'bitforge-software' => [
                ['name' => 'Klaus Weber', 'email' => 'klaus.weber@bitforge-software.example', 'title' => 'Talent Acquisition Partner', 'phone' => '+49 30 5683 9101'],
                ['name' => 'Anja Becker', 'email' => 'anja.becker@bitforge-software.example', 'title' => 'Head of People & Culture', 'phone' => '+49 30 5683 9102'],
            ],
            'cipherwave-security' => [
                ['name' => 'Tariq Mansour', 'email' => 'tariq.mansour@cipherwave-security.example', 'title' => 'Security Talent Lead', 'phone' => '+970 2 298 4410'],
            ],
            'datavortex-systems' => [
                ['name' => 'Emily Watson', 'email' => 'emily.watson@datavortex-systems.example', 'title' => 'Principal Talent Partner', 'phone' => '+44 20 7946 0912'],
                ['name' => 'James Sterling', 'email' => 'james.sterling@datavortex-systems.example', 'title' => 'Data Engineering Recruiter', 'phone' => '+44 20 7946 0913'],
            ],
            'echologic-ai' => [
                ['name' => 'Camille Tremblay', 'email' => 'camille.tremblay@echologic-ai.example', 'title' => 'AI/ML Recruiting Lead', 'phone' => '+1 514 555 0178'],
            ],
            'fluxcore-technologies' => [
                ['name' => 'Lars van Dijk', 'email' => 'lars.vandijk@fluxcore-technologies.example', 'title' => 'DevOps Talent Specialist', 'phone' => '+31 20 555 3820'],
            ],
            'gigabyte-foundry' => [
                ['name' => 'Chen Wei-Lin', 'email' => 'weilin.chen@gigabyte-foundry.example', 'title' => 'Hardware Systems Recruiter', 'phone' => '+886 2 2718 9011'],
            ],
            'hyperion-networks' => [
                ['name' => 'Astrid Lindholm', 'email' => 'astrid.lindholm@hyperion-networks.example', 'title' => 'Telecom Talent Lead', 'phone' => '+46 8 123 4567'],
            ],
            'ionsphere-labs' => [
                ['name' => 'Dr. Stefan Meyer', 'email' => 'stefan.meyer@ionsphere-labs.example', 'title' => 'Research Talent Director', 'phone' => '+41 22 767 6111'],
            ],
            'krypton-solutions' => [
                ['name' => 'Beatrix Keller', 'email' => 'beatrix.keller@krypton-solutions.example', 'title' => 'Cybersecurity Recruiter', 'phone' => '+41 44 632 1100'],
            ],
            'lumina-software-house' => [
                ['name' => 'Claire Dubois', 'email' => 'claire.dubois@lumina-software-house.example', 'title' => 'Design & Frontend Hiring Lead', 'phone' => '+33 1 42 68 55 00'],
            ],
            'nexusnode-tech' => [
                ['name' => 'Matias Korhonen', 'email' => 'matias.korhonen@nexusnode-tech.example', 'title' => 'IoT Talent Specialist', 'phone' => '+358 9 4711'],
            ],
            'omnistack-engineering' => [
                ['name' => 'Marcus Vance', 'email' => 'marcus.vance@omnistack-engineering.example', 'title' => 'VP of Talent Acquisition', 'phone' => '+1 212 555 0144'],
                ['name' => 'Jessica Taylor', 'email' => 'jessica.taylor@omnistack-engineering.example', 'title' => 'Technical Recruiter', 'phone' => '+1 212 555 0145'],
            ],
            'pixelcraft-digital' => [
                ['name' => 'Chloe Bennett', 'email' => 'chloe.bennett@pixelcraft-digital.example', 'title' => 'Creative & Tech Recruiter', 'phone' => '+1 310 555 0199'],
            ],
            'quantumleap-it' => [
                ['name' => 'Nadia Benali', 'email' => 'nadia.benali@quantumleap-it.example', 'title' => 'Consulting Talent Partner', 'phone' => '+212 522 20 40 60'],
                ['name' => 'Mehdi Idrissi', 'email' => 'mehdi.idrissi@quantumleap-it.example', 'title' => 'Senior Recruiter', 'phone' => '+212 522 20 40 61'],
            ],
        ];

        foreach ($companies as $company) {
            $roster = $recruiterRoster[$company->slug] ?? [
                ['name' => 'Recruiter at '.$company->name, 'email' => 'recruiter@'.$company->slug.'.example', 'title' => 'Talent Partner', 'phone' => null],
            ];

            foreach ($roster as $person) {
                $recruiter = User::firstOrCreate(
                    ['email' => $person['email']],
                    [
                        'name' => $person['name'],
                        'password' => $defaultPassword,
                        'is_recruiter' => true,
                        'company_id' => $company->id,
                        'job_title' => $person['title'],
                        'phone' => $person['phone'],
                        'email_verified_at' => now()->subMonths(2),
                        'location' => $company->location,
                        'profile_summary' => "Recruiting high-impact engineering and product talent for {$company->name}.",
                    ]
                );
                $recruiter->syncRoles(['Recruiter']);
                $this->seedRecruiterTemplates($recruiter);
            }
        }
        $this->command->info('Recruiters synced for all companies.');

        // 4. Demo Candidate
        $demoCandidate = User::firstOrCreate(
            ['email' => 'candidate@recruivo.work'],
            [
                'name' => 'John Doe',
                'password' => $defaultPassword,
                'is_recruiter' => false,
                'is_demo' => true,
                'location' => 'San Francisco, CA',
                'phone' => '+1 (415) 555-0192',
                'email_verified_at' => now()->subMonths(3),
                'profile_summary' => 'Senior Full-Stack Software Engineer with 7+ years of experience building modern web applications, scalable backend APIs, and distributed cloud services. Passionate about clean code, developer tooling, and user-centric product engineering.',
            ]
        );
        $demoCandidate->syncRoles(['Candidate']);

        CandidateProfile::updateOrCreate(
            ['user_id' => $demoCandidate->id],
            [
                'headline' => 'Senior Full-Stack Software Engineer',
                'skills' => 'PHP, Laravel, TypeScript, Vue.js, MySQL, Redis, Docker, Tailwind CSS, REST APIs, CI/CD, AWS',
                'experience' => '7+ years leading full-stack web application development, designing resilient database architectures, and delivering clean, maintainable Vue and Laravel features.',
                'education' => 'B.S. in Computer Science — University of California, Berkeley',
                'languages' => 'English (Native), French (Professional)',
                'languages_data' => [
                    ['language' => 'English', 'proficiency' => 'Native'],
                    ['language' => 'French', 'proficiency' => 'Fluent'],
                ],
                'profile_links' => [
                    ['platform' => 'GitHub', 'url' => 'https://github.com/johndoe'],
                    ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/johndoe-dev'],
                    ['platform' => 'Portfolio', 'url' => 'https://johndoe.dev'],
                ],
                'experiences' => [
                    [
                        'title' => 'Senior Full-Stack Engineer',
                        'company' => 'CloudScale Systems',
                        'location' => 'San Francisco, CA',
                        'start_date' => '2022-01',
                        'end_date' => null,
                        'is_current' => true,
                        'description' => 'Architected microservices, migrated legacy monolith endpoints to modern Inertia/Vue interfaces, and reduced query latency by 45%.',
                    ],
                    [
                        'title' => 'Full-Stack Developer',
                        'company' => 'WebForge Solutions',
                        'location' => 'San Francisco, CA',
                        'start_date' => '2019-06',
                        'end_date' => '2021-12',
                        'is_current' => false,
                        'description' => 'Built customer-facing portals, payment gateway integrations, and real-time dashboard analytics using Laravel, Vue.js, and Redis.',
                    ],
                ],
                'educations' => [
                    [
                        'institution' => 'University of California, Berkeley',
                        'degree' => 'Bachelor of Science',
                        'field_of_study' => 'Computer Science',
                        'start_date' => '2015-09',
                        'end_date' => '2019-05',
                        'description' => 'Graduated with honors. Focused on Distributed Systems, Algorithms, and Database Management.',
                    ],
                ],
                'preferred_categories' => [
                    'Full-Stack Development',
                    'Software Development',
                    'Web Development',
                    'Cloud Computing',
                ],
                'resume_path' => 'resumes/demo-candidate-resume.pdf',
                'linkedin_url' => 'https://linkedin.com/in/johndoe-dev',
                'github_url' => 'https://github.com/johndoe',
                'portfolio_url' => 'https://johndoe.dev',
                'website_url' => 'https://johndoe.dev',
            ]
        );
        $this->command->info('Demo candidate ready: candidate@recruivo.work / password');

        // 5. Candidate pool with realistic diverse profiles
        $candidateRoster = [
            ['name' => 'Maya Lin', 'email' => 'maya.lin@example.com', 'location' => 'San Francisco, United States', 'track' => 'frontend', 'level' => 'senior'],
            ['name' => 'David Rossi', 'email' => 'david.rossi@example.com', 'location' => 'Berlin, Germany', 'track' => 'backend', 'level' => 'senior'],
            ['name' => 'Amira El-Amrani', 'email' => 'amira.amrani@example.com', 'location' => 'Casablanca, Morocco', 'track' => 'fullstack', 'level' => 'lead'],
            ['name' => 'Liam O’Connor', 'email' => 'liam.oconnor@example.com', 'location' => 'Dublin, Ireland', 'track' => 'cloud', 'level' => 'senior'],
            ['name' => 'Fatima Zahra Mansouri', 'email' => 'fz.mansouri@example.com', 'location' => 'Rabat, Morocco', 'track' => 'data', 'level' => 'senior'],
            ['name' => 'Alexander Schmidt', 'email' => 'alex.schmidt@example.com', 'location' => 'Zurich, Switzerland', 'track' => 'security', 'level' => 'senior'],
            ['name' => 'Chloe Martin', 'email' => 'chloe.martin@example.com', 'location' => 'Paris, France', 'track' => 'frontend', 'level' => 'mid'],
            ['name' => 'Kenji Takahashi', 'email' => 'kenji.t@example.com', 'location' => 'Tokyo, Japan', 'track' => 'backend', 'level' => 'senior'],
            ['name' => 'Sofia Rossi', 'email' => 'sofia.rossi@example.com', 'location' => 'London, United Kingdom', 'track' => 'devops', 'level' => 'senior'],
            ['name' => 'Ethan Walker', 'email' => 'ethan.walker@example.com', 'location' => 'New York, United States', 'track' => 'fullstack', 'level' => 'mid'],
            ['name' => 'Amina Bennani', 'email' => 'amina.bennani@example.com', 'location' => 'Casablanca, Morocco', 'track' => 'backend', 'level' => 'junior'],
            ['name' => 'Lucas Bernard', 'email' => 'lucas.bernard@example.com', 'location' => 'Montreal, Canada', 'track' => 'ai', 'level' => 'senior'],
            ['name' => 'Hannah Fischer', 'email' => 'hannah.fischer@example.com', 'location' => 'Berlin, Germany', 'track' => 'cloud', 'level' => 'mid'],
            ['name' => 'Omar Farooq', 'email' => 'omar.farooq@example.com', 'location' => 'Dubai, United Arab Emirates', 'track' => 'security', 'level' => 'lead'],
            ['name' => 'Elena Popova', 'email' => 'elena.popova@example.com', 'location' => 'Amsterdam, Netherlands', 'track' => 'devops', 'level' => 'senior'],
            ['name' => 'Marcus Chen', 'email' => 'marcus.chen@example.com', 'location' => 'Toronto, Canada', 'track' => 'data', 'level' => 'senior'],
            ['name' => 'Zainab Qasim', 'email' => 'zainab.qasim@example.com', 'location' => 'Ramallah, Palestine', 'track' => 'frontend', 'level' => 'senior'],
            ['name' => 'Nils Johansson', 'email' => 'nils.j@example.com', 'location' => 'Stockholm, Sweden', 'track' => 'networking', 'level' => 'senior'],
            ['name' => 'Youssef Alaoui', 'email' => 'youssef.alaoui@example.com', 'location' => 'Casablanca, Morocco', 'track' => 'fullstack', 'level' => 'senior'],
            ['name' => 'Ingrid Lindqvist', 'email' => 'ingrid.l@example.com', 'location' => 'Helsinki, Finland', 'track' => 'iot', 'level' => 'mid'],
            ['name' => 'Mateo Silva', 'email' => 'mateo.silva@example.com', 'location' => 'Lisbon, Portugal', 'track' => 'frontend', 'level' => 'mid'],
            ['name' => 'Sara Al-Hassan', 'email' => 'sara.hassan@example.com', 'location' => 'London, United Kingdom', 'track' => 'ai', 'level' => 'senior'],
            ['name' => 'Julian Brandt', 'email' => 'julian.brandt@example.com', 'location' => 'Munich, Germany', 'track' => 'backend', 'level' => 'lead'],
            ['name' => 'Lea Moreau', 'email' => 'lea.moreau@example.com', 'location' => 'Paris, France', 'track' => 'fullstack', 'level' => 'junior'],
            ['name' => 'Oliver Smith', 'email' => 'oliver.smith@example.com', 'location' => 'Sydney, Australia', 'track' => 'cloud', 'level' => 'senior'],
            ['name' => 'Hassan Berrada', 'email' => 'hassan.berrada@example.com', 'location' => 'Rabat, Morocco', 'track' => 'devops', 'level' => 'senior'],
            ['name' => 'Emma Davies', 'email' => 'emma.davies@example.com', 'location' => 'London, United Kingdom', 'track' => 'data', 'level' => 'mid'],
            ['name' => 'Carlos Gomez', 'email' => 'carlos.gomez@example.com', 'location' => 'Barcelona, Spain', 'track' => 'backend', 'level' => 'senior'],
            ['name' => 'Freja Nielsen', 'email' => 'freja.n@example.com', 'location' => 'Stockholm, Sweden', 'track' => 'security', 'level' => 'senior'],
            ['name' => 'Tariq Al-Mansoor', 'email' => 'tariq.mansoor@example.com', 'location' => 'Dubai, United Arab Emirates', 'track' => 'cloud', 'level' => 'lead'],
            ['name' => 'Charlotte Dubois', 'email' => 'charlotte.d@example.com', 'location' => 'Geneva, Switzerland', 'track' => 'quantum', 'level' => 'senior'],
            ['name' => 'Adam Kowalski', 'email' => 'adam.k@example.com', 'location' => 'Warsaw, Poland', 'track' => 'backend', 'level' => 'mid'],
            ['name' => 'Salma Tazi', 'email' => 'salma.tazi@example.com', 'location' => 'Casablanca, Morocco', 'track' => 'frontend', 'level' => 'senior'],
            ['name' => 'Lars Lindgren', 'email' => 'lars.l@example.com', 'location' => 'Stockholm, Sweden', 'track' => 'devops', 'level' => 'mid'],
            ['name' => 'Valerie Roy', 'email' => 'valerie.roy@example.com', 'location' => 'Montreal, Canada', 'track' => 'interactive', 'level' => 'senior'],
            ['name' => 'Daniel Kim', 'email' => 'daniel.kim@example.com', 'location' => 'Seoul, South Korea', 'track' => 'ai', 'level' => 'senior'],
            ['name' => 'Nour El-Khatib', 'email' => 'nour.khatib@example.com', 'location' => 'Ramallah, Palestine', 'track' => 'fullstack', 'level' => 'senior'],
            ['name' => 'Gabriel Santos', 'email' => 'gabriel.santos@example.com', 'location' => 'Sao Paulo, Brazil', 'track' => 'data', 'level' => 'mid'],
            ['name' => 'Helena Berg', 'email' => 'helena.berg@example.com', 'location' => 'Berlin, Germany', 'track' => 'frontend', 'level' => 'lead'],
            ['name' => 'Bilal Meziane', 'email' => 'bilal.meziane@example.com', 'location' => 'Casablanca, Morocco', 'track' => 'backend', 'level' => 'senior'],
            ['name' => 'Zoe Taylor', 'email' => 'zoe.taylor@example.com', 'location' => 'Dublin, Ireland', 'track' => 'cloud', 'level' => 'junior'],
            ['name' => 'Simon Vogt', 'email' => 'simon.vogt@example.com', 'location' => 'Zurich, Switzerland', 'track' => 'security', 'level' => 'mid'],
            ['name' => 'Meryem Chraibi', 'email' => 'meryem.chraibi@example.com', 'location' => 'Rabat, Morocco', 'track' => 'frontend', 'level' => 'mid'],
            ['name' => 'Paul Lefebvre', 'email' => 'paul.lefebvre@example.com', 'location' => 'Paris, France', 'track' => 'fullstack', 'level' => 'senior'],
            ['name' => 'Aaron Vance', 'email' => 'aaron.vance@example.com', 'location' => 'New York, United States', 'track' => 'devops', 'level' => 'senior'],
        ];

        $tracksData = [
            'backend' => [
                'headline' => 'Senior Backend Software Engineer',
                'skills' => 'PHP, Laravel, PostgreSQL, Redis, REST APIs, Docker, GraphQL',
                'preferred' => ['Software Development', 'Web Development'],
            ],
            'frontend' => [
                'headline' => 'Frontend Product Engineer',
                'skills' => 'TypeScript, Vue.js, React, Tailwind CSS, Vite, Jest, Accessibility',
                'preferred' => ['Web Development', 'Interactive Software'],
            ],
            'fullstack' => [
                'headline' => 'Full-Stack Software Engineer',
                'skills' => 'PHP, Laravel, TypeScript, Vue.js, MySQL, Redis, Tailwind CSS, Docker',
                'preferred' => ['Full-Stack Development', 'Software Development'],
            ],
            'cloud' => [
                'headline' => 'Cloud Solutions Architect',
                'skills' => 'AWS, Terraform, Kubernetes, Linux, CI/CD, Observability, Python',
                'preferred' => ['Cloud Computing', 'DevOps'],
            ],
            'devops' => [
                'headline' => 'DevOps & Site Reliability Engineer',
                'skills' => 'Docker, Kubernetes, GitHub Actions, Prometheus, Grafana, Ansible, Terraform',
                'preferred' => ['DevOps', 'Cloud Computing'],
            ],
            'data' => [
                'headline' => 'Data Platform & Analytics Engineer',
                'skills' => 'Python, SQL, PostgreSQL, dbt, Apache Spark, Airflow, Data Modeling',
                'preferred' => ['Data Analytics', 'Software Development'],
            ],
            'security' => [
                'headline' => 'Information Security & Threat Analyst',
                'skills' => 'Incident Response, SIEM, Threat Modeling, Network Security, Python, OWASP',
                'preferred' => ['Cybersecurity', 'Information Security'],
            ],
            'ai' => [
                'headline' => 'Machine Learning & AI Engineer',
                'skills' => 'Python, PyTorch, Scikit-Learn, MLOps, NLP, FastAPI, Docker',
                'preferred' => ['Artificial Intelligence', 'Data Analytics'],
            ],
            'networking' => [
                'headline' => 'Network & Infrastructure Engineer',
                'skills' => 'BGP, OSPF, VPNs, Cisco, Juniper, Network Automation, Python',
                'preferred' => ['Networking', 'Hardware Systems'],
            ],
            'iot' => [
                'headline' => 'IoT & Embedded Systems Engineer',
                'skills' => 'C++, Python, MQTT, Embedded Linux, RTOS, BLE, Sensor Networks',
                'preferred' => ['IoT Systems', 'Hardware Systems'],
            ],
            'quantum' => [
                'headline' => 'Quantum Computing Researcher & Engineer',
                'skills' => 'Python, Qiskit, Quantum Algorithms, Linear Algebra, Julia',
                'preferred' => ['Quantum Computing', 'Artificial Intelligence'],
            ],
            'interactive' => [
                'headline' => 'Interactive Software & Media Developer',
                'skills' => 'TypeScript, WebGL, Three.js, Canvas, Vue.js, UI Animation',
                'preferred' => ['Interactive Software', 'Web Development'],
            ],
        ];

        foreach ($candidateRoster as $index => $c) {
            $trackInfo = $tracksData[$c['track']] ?? $tracksData['fullstack'];
            $createdAt = now()->subDays(rand(5, 80));

            $user = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'password' => $defaultPassword,
                    'is_recruiter' => false,
                    'is_demo' => false,
                    'location' => $c['location'],
                    'phone' => '+1 555 '.sprintf('%04d', $index + 1000),
                    'email_verified_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'profile_summary' => "{$trackInfo['headline']} with experience delivering reliable software and collaborating effectively with cross-functional teams.",
                ]
            );
            $user->syncRoles(['Candidate']);

            // Vary completeness: some candidates have full structured details, some partial
            $hasExperiences = ($index % 6 !== 5);
            $hasResume = ($index % 5 !== 4);

            $experiences = $hasExperiences ? [
                [
                    'title' => $trackInfo['headline'],
                    'company' => 'Tech Systems Inc',
                    'location' => $c['location'],
                    'start_date' => '2021-03',
                    'end_date' => null,
                    'is_current' => true,
                    'description' => 'Led core feature development and automated testing for customer-facing systems.',
                ],
            ] : [];

            CandidateProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'headline' => $trackInfo['headline'],
                    'skills' => $trackInfo['skills'],
                    'experience' => "Professional experience as a {$trackInfo['headline']}. Built production systems and improved team velocity.",
                    'education' => 'Bachelor of Science in Computer Science or related technical discipline.',
                    'languages' => 'English, French',
                    'languages_data' => [
                        ['language' => 'English', 'proficiency' => 'Fluent'],
                        ['language' => 'French', 'proficiency' => 'Intermediate'],
                    ],
                    'profile_links' => [
                        ['platform' => 'GitHub', 'url' => 'https://github.com/'.strtolower(str_replace(' ', '', $c['name']))],
                        ['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/'.strtolower(str_replace(' ', '-', $c['name']))],
                    ],
                    'experiences' => $experiences,
                    'educations' => [
                        [
                            'institution' => 'Technical University',
                            'degree' => 'Bachelor of Science',
                            'field_of_study' => 'Computer Science',
                            'start_date' => '2016-09',
                            'end_date' => '2020-06',
                            'description' => 'Core computing fundamentals, software engineering, and database systems.',
                        ],
                    ],
                    'preferred_categories' => $trackInfo['preferred'],
                    'resume_path' => $hasResume ? 'resumes/demo-candidate-resume.pdf' : null,
                    'linkedin_url' => 'https://linkedin.com/in/'.strtolower(str_replace(' ', '-', $c['name'])),
                    'github_url' => 'https://github.com/'.strtolower(str_replace(' ', '', $c['name'])),
                ]
            );
        }

        $totalUsers = User::count();
        $this->command->info("Total users in database: {$totalUsers}");
    }

    private function seedRecruiterTemplates(User $recruiter): void
    {
        if ($recruiter->noteTemplates()->exists()) {
            return;
        }

        $templates = [
            [
                'name' => 'Initial Phone Screen',
                'body' => "Candidate Screen Notes:\n- Relevant Experience: \n- Communication & Clarity: \n- Motivation & Role Fit: \n- Salary Expectations: \n- Notice Period / Availability: \n- Recommendation: [Advance / Hold / Reject]",
            ],
            [
                'name' => 'Technical Assessment',
                'body' => "Technical Evaluation:\n- Architecture & System Design: \n- Problem Solving & Clean Code: \n- Testing & Reliability Habits: \n- Areas of Strength: \n- Growth Opportunities: \n- Final Score: [Strong Yes / Yes / Weak / No]",
            ],
            [
                'name' => 'Culture & Collaboration',
                'body' => "Values & Team Alignment:\n- Cross-functional Communication: \n- Handling Feedback & Disagreement: \n- Ownership & Autonomy: \n- Team Fit Assessment: ",
            ],
            [
                'name' => 'Offer Discussion',
                'body' => "Offer Details:\n- Base Salary: \n- Benefits & Equity: \n- Target Start Date: \n- Competing Offers / Considerations: \n- Next Steps: ",
            ],
        ];

        foreach ($templates as $template) {
            $recruiter->noteTemplates()->create($template);
        }
    }

    private function ensureSampleResumes(): void
    {
        $resumeDir = storage_path('app/private/resumes');
        if (! is_dir($resumeDir)) {
            mkdir($resumeDir, 0755, true);
        }

        $samplePdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n";

        Storage::disk('private')->put('resumes/demo-candidate-resume.pdf', $samplePdf);
    }
}
