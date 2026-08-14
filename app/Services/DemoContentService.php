<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;

class DemoContentService
{
    private const COMPANY_DETAILS = [
        'aetheris-dynamics' => ['location' => 'Dublin, Ireland', 'mission' => 'Make cloud infrastructure easier to operate, safer to change, and more efficient at scale.', 'culture' => 'Engineers work in small product teams, document decisions, and share ownership of reliability from design through production.'],
        'bitforge-software' => ['location' => 'Berlin, Germany', 'mission' => 'Build dependable business software that removes repetitive work and helps teams make better decisions.', 'culture' => 'The team values practical design, thoughtful code review, direct customer feedback, and steady delivery over unnecessary complexity.'],
        'cipherwave-security' => ['location' => 'Ramallah, Palestine', 'mission' => 'Protect organizations with security systems that are understandable, measurable, and resilient under real-world pressure.', 'culture' => 'Security researchers and engineers collaborate openly, test assumptions, and treat clear incident learning as part of the product.'],
        'datavortex-systems' => ['location' => 'London, United Kingdom', 'mission' => 'Turn complex operational data into trusted information that teams can use every day.', 'culture' => 'Data quality comes first. Teams pair closely with analysts and customers, publish clear ownership, and improve pipelines incrementally.'],
        'echologic-ai' => ['location' => 'Montreal, Canada', 'mission' => 'Create useful AI systems that are accurate, accountable, and grounded in the needs of the people using them.', 'culture' => 'Researchers, designers, and engineers review model behavior together and favor transparent evaluation over impressive demos.'],
        'fluxcore-technologies' => ['location' => 'Amsterdam, Netherlands', 'mission' => 'Help software teams deploy confidently with reliable platforms, clear observability, and sensible automation.', 'culture' => 'Platform engineers build paved roads rather than gates. The team values calm incident response, strong documentation, and continuous improvement.'],
        'gigabyte-foundry' => ['location' => 'Taipei, Taiwan', 'mission' => 'Design enterprise hardware systems that deliver predictable performance and remain serviceable throughout their lifecycle.', 'culture' => 'Hardware and software specialists test together, share lab results early, and make reliability a design requirement rather than a final check.'],
        'hyperion-networks' => ['location' => 'Stockholm, Sweden', 'mission' => 'Connect people and services through secure, high-capacity networks that remain dependable as demand grows.', 'culture' => 'Network teams use automation, peer review, and blameless incident analysis to improve both service quality and engineering practice.'],
        'ionsphere-labs' => ['location' => 'Geneva, Switzerland', 'mission' => 'Move quantum computing from research experiments toward practical tools for science and industry.', 'culture' => 'Physicists and software engineers work side by side, explain ideas clearly, and publish reproducible results before scaling an approach.'],
        'krypton-solutions' => ['location' => 'Zurich, Switzerland', 'mission' => 'Give organizations practical security controls that protect critical information without slowing down legitimate work.', 'culture' => 'Consultants and engineers combine technical depth with clear communication, measurable risk reduction, and respect for customer constraints.'],
        'lumina-software-house' => ['location' => 'Paris, France', 'mission' => 'Create accessible digital products that feel clear, fast, and useful from the first interaction.', 'culture' => 'Design and engineering work as one team, test with real users, and treat accessibility and performance as core product requirements.'],
        'nexusnode-tech' => ['location' => 'Helsinki, Finland', 'mission' => 'Build connected systems that help physical operations become safer, more observable, and easier to manage.', 'culture' => 'Embedded, cloud, and product teams prototype together and validate devices in realistic conditions before wider deployment.'],
        'omnistack-engineering' => ['location' => 'New York, United States', 'mission' => 'Provide development platforms that let product teams ship complete, maintainable applications without unnecessary friction.', 'culture' => 'Teams own outcomes end to end, keep interfaces simple, and invest in tooling that makes the safe path the easy path.'],
        'pixelcraft-digital' => ['location' => 'Los Angeles, United States', 'mission' => 'Blend software, design, and storytelling to create interactive experiences people remember and enjoy using.', 'culture' => 'Creative technologists prototype quickly, critique constructively, and balance visual ambition with inclusive, performant implementation.'],
        'quantumleap-it' => ['location' => 'Casablanca, Morocco', 'mission' => 'Help growing organizations modernize their technology through practical strategy and hands-on delivery.', 'culture' => 'Consultants stay close to client teams, explain tradeoffs plainly, and leave behind systems and knowledge that clients can own confidently.'],
    ];

    private const PROFILE_TRACKS = [
        ['headline' => 'Backend Software Engineer', 'skills' => 'PHP, Laravel, PostgreSQL, Redis, REST APIs, Docker', 'education' => 'Bachelor’s degree in Computer Science', 'experience' => 'Built and maintained web services, improved database performance, and introduced automated tests for critical customer workflows. Worked closely with product and support teams to diagnose production issues and deliver reliable fixes.'],
        ['headline' => 'Frontend Product Engineer', 'skills' => 'JavaScript, TypeScript, Vue.js, Tailwind CSS, Accessibility, Testing', 'education' => 'Bachelor’s degree in Software Engineering', 'experience' => 'Delivered responsive product interfaces, built reusable design-system components, and improved accessibility across desktop and mobile experiences. Partnered with designers to turn prototypes into maintainable production features.'],
        ['headline' => 'Cloud Platform Engineer', 'skills' => 'AWS, Kubernetes, Terraform, Linux, CI/CD, Observability', 'education' => 'Bachelor’s degree in Information Systems', 'experience' => 'Operated cloud platforms, automated deployment pipelines, and improved service monitoring and incident response. Helped application teams adopt safer release patterns and reduce recurring operational work.'],
        ['headline' => 'Data Analytics Engineer', 'skills' => 'SQL, Python, dbt, Data Modeling, Power BI, ETL', 'education' => 'Master’s degree in Data Analytics', 'experience' => 'Designed trusted reporting datasets, improved pipeline quality checks, and worked with business teams to define useful metrics. Documented data ownership and reduced time spent reconciling conflicting reports.'],
        ['headline' => 'Cybersecurity Analyst', 'skills' => 'Threat Analysis, SIEM, Incident Response, Network Security, Python, Risk Assessment', 'education' => 'Bachelor’s degree in Cybersecurity', 'experience' => 'Monitored security events, investigated suspicious activity, and improved incident playbooks. Supported risk reviews and translated technical findings into clear remediation steps for engineering teams.'],
    ];

    private const ARTICLE_TOPICS = [
        ['en' => 'How to Write a Job Description Candidates Can Trust', 'fr' => 'Rédiger une offre d’emploi claire et crédible', 'ar' => 'كيفية كتابة وصف وظيفي واضح وموثوق'],
        ['en' => 'A Practical Guide to Preparing for Technical Interviews', 'fr' => 'Guide pratique pour préparer un entretien technique', 'ar' => 'دليل عملي للاستعداد للمقابلات التقنية'],
        ['en' => 'What Good Candidate Communication Looks Like', 'fr' => 'Les bases d’une bonne communication avec les candidats', 'ar' => 'أسس التواصل الجيد مع المرشحين'],
        ['en' => 'Building a Hiring Process That Respects Everyone’s Time', 'fr' => 'Construire un processus de recrutement respectueux du temps de chacun', 'ar' => 'بناء عملية توظيف تحترم وقت الجميع'],
        ['en' => 'How Candidates Can Evaluate Company Culture', 'fr' => 'Comment évaluer la culture d’une entreprise', 'ar' => 'كيف يقيّم المرشح ثقافة الشركة'],
    ];

    public function companyDetails(Company $company): array
    {
        $details = self::COMPANY_DETAILS[$company->slug] ?? [
            'location' => 'Casablanca, Morocco',
            'mission' => 'Build useful technology that helps customers work more effectively and make confident decisions.',
            'culture' => 'The team values clear communication, dependable delivery, continuous learning, and shared ownership of customer outcomes.',
        ];

        return [
            ...$details,
            'tagline' => match ($company->slug) {
                'aetheris-dynamics' => 'Cloud systems built for dependable growth',
                'bitforge-software' => 'Business software without unnecessary complexity',
                'cipherwave-security' => 'Security teams can understand and trust',
                'datavortex-systems' => 'Trusted data for everyday decisions',
                'echologic-ai' => 'Useful AI with accountable outcomes',
                'fluxcore-technologies' => 'A safer path from code to production',
                'gigabyte-foundry' => 'Enterprise hardware engineered to last',
                'hyperion-networks' => 'Secure connectivity at growing scale',
                'ionsphere-labs' => 'Practical tools for quantum discovery',
                'krypton-solutions' => 'Security controls that support real work',
                'lumina-software-house' => 'Accessible products people enjoy using',
                'nexusnode-tech' => 'Connected systems for safer operations',
                'omnistack-engineering' => 'Development platforms that stay maintainable',
                'pixelcraft-digital' => 'Interactive experiences built with purpose',
                'quantumleap-it' => 'Practical technology change, delivered together',
                default => 'Practical technology for ambitious teams',
            },
        ];
    }

    public function jobDescription(Job $job): string
    {
        $company = $job->company?->name ?? 'Our team';
        $workType = match ($job->remote_type) {
            'remote' => 'remote',
            'hybrid' => 'hybrid',
            default => 'on-site',
        };

        return "{$company} is hiring a {$job->title} to join its {$job->category} team in {$job->location}. This is a {$workType} role for someone who enjoys solving practical problems and improving systems that people rely on.\n\nYou will own meaningful work from planning through delivery, collaborate with product and engineering partners, review changes, and help keep technical decisions clear and maintainable. You will also contribute to testing, documentation, and the steady improvement of team workflows.\n\nWe are looking for relevant hands-on experience, sound technical judgment, clear communication, and a willingness to learn. You do not need to match every tool listed in the role; we value evidence that you can understand a problem, make sensible tradeoffs, and deliver dependable results.";
    }

    public function userDetails(User $user): array
    {
        if ($user->isRecruiter()) {
            return [
                'location' => $user->company?->location ?? 'Casablanca, Morocco',
                'profile_summary' => 'Talent professional focused on clear role expectations, respectful candidate communication, and an efficient interview process.',
            ];
        }

        $track = self::PROFILE_TRACKS[($user->id - 1) % count(self::PROFILE_TRACKS)];
        $locations = ['Casablanca, Morocco', 'Rabat, Morocco', 'Paris, France', 'Dublin, Ireland', 'Berlin, Germany'];

        return [
            'location' => $locations[($user->id - 1) % count($locations)],
            'profile_summary' => "{$track['headline']} with experience delivering reliable software and working closely with cross-functional teams. Values clear communication, maintainable systems, and measurable product outcomes.",
            'profile' => $track,
        ];
    }

    public function applicationDetails(Application $application): array
    {
        $title = $application->job?->title ?? 'this role';
        $company = $application->job?->company?->name ?? 'your team';

        $details = [
            'cover_letter' => "I am interested in the {$title} position at {$company}. My experience aligns with the role’s focus, and I would welcome the opportunity to discuss how I can contribute to the team. Thank you for reviewing my application.",
        ];

        if ($application->notes !== null) {
            $details['notes'] = match ($application->status?->value ?? $application->status) {
                'accepted' => 'Strong match for the role. The candidate demonstrated relevant experience and communicated their approach clearly.',
                'rejected' => 'The application was reviewed carefully, but another candidate’s experience was a closer match for the current requirements.',
                default => 'Application received and queued for review by the hiring team.',
            };
        }

        return $details;
    }

    public function article(int $index): array
    {
        $topic = self::ARTICLE_TOPICS[$index % count(self::ARTICLE_TOPICS)];
        $edition = intdiv($index, count(self::ARTICLE_TOPICS)) + 1;
        $suffix = $edition > 1 ? " — Edition {$edition}" : '';

        return [
            'title' => ['en' => $topic['en'].$suffix, 'fr' => $topic['fr'].$suffix, 'ar' => $topic['ar'].$suffix],
            'content' => [
                'en' => "A good hiring process gives people enough information to make a thoughtful decision. Start with the real outcome of the role, explain how the team works, and describe the interview steps before asking for a candidate’s time.\n\nClear expectations reduce avoidable back-and-forth. Use direct language, distinguish required experience from skills that can be learned, and give candidates a realistic view of ownership, support, and working conditions.\n\nThe final check is simple: a candidate should understand the job, know what happens next, and feel that their time and questions will be treated with respect.",
                'fr' => "Un bon processus de recrutement donne aux candidats assez d’informations pour prendre une décision réfléchie. Commencez par le résultat attendu du poste, expliquez le fonctionnement de l’équipe et présentez les étapes de l’entretien avant de solliciter leur temps.\n\nDes attentes claires réduisent les échanges inutiles. Utilisez un langage direct, distinguez l’expérience indispensable des compétences qui peuvent être apprises et décrivez honnêtement les responsabilités et les conditions de travail.\n\nLe contrôle final est simple : le candidat doit comprendre le poste, savoir quelle est la prochaine étape et sentir que son temps sera respecté.",
                'ar' => "تمنح عملية التوظيف الجيدة المرشحين معلومات كافية لاتخاذ قرار مدروس. ابدأ بالنتيجة الفعلية المطلوبة من الدور، واشرح طريقة عمل الفريق، ووضّح مراحل المقابلة قبل طلب وقت المرشح.\n\nتقلل التوقعات الواضحة من المراسلات غير الضرورية. استخدم لغة مباشرة، وميّز بين الخبرة المطلوبة والمهارات التي يمكن تعلمها، وقدم صورة واقعية عن المسؤوليات وظروف العمل.\n\nالمراجعة الأخيرة بسيطة: يجب أن يفهم المرشح الوظيفة، ويعرف الخطوة التالية، ويشعر بأن وقته وأسئلته محل احترام.",
            ],
        ];
    }
}
