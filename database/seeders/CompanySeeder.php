<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CompanySeeder extends Seeder
{
    private const COMPANIES = [
        [
            'name' => 'Aetheris Dynamics',
            'tagline' => 'Cloud systems built for dependable growth',
            'logo_path' => 'company-logos/aetheris-dynamics.png',
            'location' => 'Dublin, Ireland',
            'website_url' => 'https://aetheris-dynamics.example',
            'linkedin_url' => 'https://www.linkedin.com/company/aetheris-dynamics',
            'email' => 'careers@aetheris-dynamics.example',
            'size' => '500+',
            'founded_year' => 2014,
            'mission' => 'Make cloud infrastructure easier to operate, safer to change, and more efficient at scale.',
            'culture' => 'Engineers work in small product teams, document decisions, and share ownership of reliability from design through production.',
        ],
        [
            'name' => 'BitForge Software',
            'tagline' => 'Business software without unnecessary complexity',
            'logo_path' => 'company-logos/bitforge-software.png',
            'location' => 'Berlin, Germany',
            'website_url' => 'https://bitforge-software.example',
            'linkedin_url' => 'https://www.linkedin.com/company/bitforge-software',
            'email' => 'careers@bitforge-software.example',
            'size' => '51-200',
            'founded_year' => 2017,
            'mission' => 'Build dependable business software that removes repetitive work and helps teams make better decisions.',
            'culture' => 'The team values practical design, thoughtful code review, direct customer feedback, and steady delivery over unnecessary complexity.',
        ],
        [
            'name' => 'CipherWave Security',
            'tagline' => 'Security teams can understand and trust',
            'logo_path' => 'company-logos/cipherwave-security.png',
            'location' => 'Ramallah, Palestine',
            'website_url' => 'https://cipherwave-security.example',
            'linkedin_url' => 'https://www.linkedin.com/company/cipherwave-security',
            'email' => 'careers@cipherwave-security.example',
            'size' => '201-500',
            'founded_year' => 2016,
            'mission' => 'Protect organizations with security systems that are understandable, measurable, and resilient under real-world pressure.',
            'culture' => 'Security researchers and engineers collaborate openly, test assumptions, and treat clear incident learning as part of the product.',
        ],
        [
            'name' => 'DataVortex Systems',
            'tagline' => 'Trusted data for everyday decisions',
            'logo_path' => 'company-logos/datavortex-systems.png',
            'location' => 'London, United Kingdom',
            'website_url' => 'https://datavortex-systems.example',
            'linkedin_url' => 'https://www.linkedin.com/company/datavortex-systems',
            'email' => 'careers@datavortex-systems.example',
            'size' => '201-500',
            'founded_year' => 2015,
            'mission' => 'Turn complex operational data into trusted information that teams can use every day.',
            'culture' => 'Data quality comes first. Teams pair closely with analysts and customers, publish clear ownership, and improve pipelines incrementally.',
        ],
        [
            'name' => 'EchoLogic AI',
            'tagline' => 'Useful AI with accountable outcomes',
            'logo_path' => 'company-logos/echologic-ai.png',
            'location' => 'Montreal, Canada',
            'website_url' => 'https://echologic-ai.example',
            'linkedin_url' => 'https://www.linkedin.com/company/echologic-ai',
            'email' => 'careers@echologic-ai.example',
            'size' => '51-200',
            'founded_year' => 2019,
            'mission' => 'Create useful AI systems that are accurate, accountable, and grounded in the needs of the people using them.',
            'culture' => 'Researchers, designers, and engineers review model behavior together and favor transparent evaluation over impressive demos.',
        ],
        [
            'name' => 'FluxCore Technologies',
            'tagline' => 'A safer path from code to production',
            'logo_path' => 'company-logos/fluxcore-technologies.png',
            'location' => 'Amsterdam, Netherlands',
            'website_url' => 'https://fluxcore-technologies.example',
            'linkedin_url' => 'https://www.linkedin.com/company/fluxcore-technologies',
            'email' => 'careers@fluxcore-technologies.example',
            'size' => '51-200',
            'founded_year' => 2018,
            'mission' => 'Help software teams deploy confidently with reliable platforms, clear observability, and sensible automation.',
            'culture' => 'Platform engineers build paved roads rather than gates. The team values calm incident response, strong documentation, and continuous improvement.',
        ],
        [
            'name' => 'GigaByte Foundry',
            'tagline' => 'Enterprise hardware engineered to last',
            'logo_path' => 'company-logos/gigabyte-foundry.png',
            'location' => 'Taipei, Taiwan',
            'website_url' => 'https://gigabyte-foundry.example',
            'linkedin_url' => 'https://www.linkedin.com/company/gigabyte-foundry',
            'email' => 'careers@gigabyte-foundry.example',
            'size' => '500+',
            'founded_year' => 2011,
            'mission' => 'Design enterprise hardware systems that deliver predictable performance and remain serviceable throughout their lifecycle.',
            'culture' => 'Hardware and software specialists test together, share lab results early, and make reliability a design requirement rather than a final check.',
        ],
        [
            'name' => 'Hyperion Networks',
            'tagline' => 'Secure connectivity at growing scale',
            'logo_path' => 'company-logos/hyperion-networks.png',
            'location' => 'Stockholm, Sweden',
            'website_url' => 'https://hyperion-networks.example',
            'linkedin_url' => 'https://www.linkedin.com/company/hyperion-networks',
            'email' => 'careers@hyperion-networks.example',
            'size' => '201-500',
            'founded_year' => 2013,
            'mission' => 'Connect people and services through secure, high-capacity networks that remain dependable as demand grows.',
            'culture' => 'Network teams use automation, peer review, and blameless incident analysis to improve both service quality and engineering practice.',
        ],
        [
            'name' => 'IonSphere Labs',
            'tagline' => 'Practical tools for quantum discovery',
            'logo_path' => 'company-logos/ionsphere-labs.png',
            'location' => 'Geneva, Switzerland',
            'website_url' => 'https://ionsphere-labs.example',
            'linkedin_url' => 'https://www.linkedin.com/company/ionsphere-labs',
            'email' => 'careers@ionsphere-labs.example',
            'size' => '11-50',
            'founded_year' => 2021,
            'mission' => 'Move quantum computing from research experiments toward practical tools for science and industry.',
            'culture' => 'Physicists and software engineers work side by side, explain ideas clearly, and publish reproducible results before scaling an approach.',
        ],
        [
            'name' => 'Krypton Solutions',
            'tagline' => 'Security controls that support real work',
            'logo_path' => 'company-logos/krypton-solutions.png',
            'location' => 'Zurich, Switzerland',
            'website_url' => 'https://krypton-solutions.example',
            'linkedin_url' => 'https://www.linkedin.com/company/krypton-solutions',
            'email' => 'careers@krypton-solutions.example',
            'size' => '51-200',
            'founded_year' => 2016,
            'mission' => 'Give organizations practical security controls that protect critical information without slowing down legitimate work.',
            'culture' => 'Consultants and engineers combine technical depth with clear communication, measurable risk reduction, and respect for customer constraints.',
        ],
        [
            'name' => 'Lumina Software House',
            'tagline' => 'Accessible products people enjoy using',
            'logo_path' => 'company-logos/lumina-software-house.png',
            'location' => 'Paris, France',
            'website_url' => 'https://lumina-software-house.example',
            'linkedin_url' => 'https://www.linkedin.com/company/lumina-software-house',
            'email' => 'careers@lumina-software-house.example',
            'size' => '11-50',
            'founded_year' => 2020,
            'mission' => 'Create accessible digital products that feel clear, fast, and useful from the first interaction.',
            'culture' => 'Design and engineering work as one team, test with real users, and treat accessibility and performance as core product requirements.',
        ],
        [
            'name' => 'NexusNode Tech',
            'tagline' => 'Connected systems for safer operations',
            'logo_path' => 'company-logos/nexusnode-tech.png',
            'location' => 'Helsinki, Finland',
            'website_url' => 'https://nexusnode-tech.example',
            'linkedin_url' => 'https://www.linkedin.com/company/nexusnode-tech',
            'email' => 'careers@nexusnode-tech.example',
            'size' => '11-50',
            'founded_year' => 2019,
            'mission' => 'Build connected systems that help physical operations become safer, more observable, and easier to manage.',
            'culture' => 'Embedded, cloud, and product teams prototype together and validate devices in realistic conditions before wider deployment.',
        ],
        [
            'name' => 'OmniStack Engineering',
            'tagline' => 'Development platforms that stay maintainable',
            'logo_path' => 'company-logos/omnistack-engineering.png',
            'location' => 'New York, United States',
            'website_url' => 'https://omnistack-engineering.example',
            'linkedin_url' => 'https://www.linkedin.com/company/omnistack-engineering',
            'email' => 'careers@omnistack-engineering.example',
            'size' => '201-500',
            'founded_year' => 2015,
            'mission' => 'Provide development platforms that let product teams ship complete, maintainable applications without unnecessary friction.',
            'culture' => 'Teams own outcomes end to end, keep interfaces simple, and invest in tooling that makes the safe path the easy path.',
        ],
        [
            'name' => 'PixelCraft Digital',
            'tagline' => 'Interactive experiences built with purpose',
            'logo_path' => 'company-logos/pixelcraft-digital.png',
            'location' => 'Los Angeles, United States',
            'website_url' => 'https://pixelcraft-digital.example',
            'linkedin_url' => 'https://www.linkedin.com/company/pixelcraft-digital',
            'email' => 'careers@pixelcraft-digital.example',
            'size' => '11-50',
            'founded_year' => 2018,
            'mission' => 'Blend software, design, and storytelling to create interactive experiences people remember and enjoy using.',
            'culture' => 'Creative technologists prototype quickly, critique constructively, and balance visual ambition with inclusive, performant implementation.',
        ],
        [
            'name' => 'QuantumLeap IT',
            'tagline' => 'Practical technology change, delivered together',
            'logo_path' => 'company-logos/quantumleap-it.png',
            'location' => 'Casablanca, Morocco',
            'website_url' => 'https://quantumleap-it.example',
            'linkedin_url' => 'https://www.linkedin.com/company/quantumleap-it',
            'email' => 'careers@quantumleap-it.example',
            'size' => '201-500',
            'founded_year' => 2014,
            'mission' => 'Help growing organizations modernize their technology through practical strategy and hands-on delivery.',
            'culture' => 'Consultants stay close to client teams, explain tradeoffs plainly, and leave behind systems and knowledge that clients can own confidently.',
        ],
    ];

    public function run(): void
    {
        $this->command->info('Syncing companies...');

        $this->syncCompanyLogos();

        $existing = Company::query()->orderBy('id')->get();

        foreach (self::COMPANIES as $index => $data) {
            $slug = Str::slug($data['name']);

            $company = Company::query()->where('slug', $slug)->first()
                ?? $existing->get($index)
                ?? new Company;

            $company->fill([
                'name' => $data['name'],
                'slug' => $slug,
                'tagline' => $data['tagline'],
                'logo_path' => $data['logo_path'],
                'location' => $data['location'],
                'website_url' => $data['website_url'],
                'linkedin_url' => $data['linkedin_url'],
                'email' => $data['email'],
                'size' => $data['size'],
                'founded_year' => $data['founded_year'],
                'mission' => $data['mission'],
                'culture' => $data['culture'],
            ]);
            // The logo URL uses updated_at as its cache version. Force a new
            // version when seeded logo files are replaced.
            $company->updated_at = now();
            $company->save();
        }

        $this->command->info(count(self::COMPANIES).' companies synced successfully!');
    }

    private function syncCompanyLogos(): void
    {
        foreach (self::COMPANIES as $company) {
            $logoPath = $company['logo_path'];
            $sourcePath = database_path('seeders/assets/'.$logoPath);

            if (! is_file($sourcePath)) {
                throw new RuntimeException("Missing seeded company logo: {$sourcePath}");
            }

            Storage::disk('public')->put($logoPath, file_get_contents($sourcePath));
        }
    }
}
