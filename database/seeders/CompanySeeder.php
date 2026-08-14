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
        ['name' => 'Aetheris Dynamics', 'tagline' => 'Cloud Computing & Infrastructure', 'logo_path' => 'company-logos/aetheris-dynamics.png'],
        ['name' => 'BitForge Software', 'tagline' => 'Custom Software Development', 'logo_path' => 'company-logos/bitforge-software.png'],
        ['name' => 'CipherWave Security', 'tagline' => 'Cybersecurity & Encryption', 'logo_path' => 'company-logos/cipherwave-security.png'],
        ['name' => 'DataVortex Systems', 'tagline' => 'Big Data Analytics', 'logo_path' => 'company-logos/datavortex-systems.png'],
        ['name' => 'EchoLogic AI', 'tagline' => 'Artificial Intelligence & Machine Learning', 'logo_path' => 'company-logos/echologic-ai.png'],
        ['name' => 'FluxCore Technologies', 'tagline' => 'IT Infrastructure & DevOps', 'logo_path' => 'company-logos/fluxcore-technologies.png'],
        ['name' => 'GigaByte Foundry', 'tagline' => 'Enterprise Hardware & Systems', 'logo_path' => 'company-logos/gigabyte-foundry.png'],
        ['name' => 'Hyperion Networks', 'tagline' => 'Telecommunications & Networking', 'logo_path' => 'company-logos/hyperion-networks.png'],
        ['name' => 'IonSphere Labs', 'tagline' => 'Quantum Computing Research', 'logo_path' => 'company-logos/ionsphere-labs.png'],
        ['name' => 'Krypton Solutions', 'tagline' => 'Information Security', 'logo_path' => 'company-logos/krypton-solutions.png'],
        ['name' => 'Lumina Software House', 'tagline' => 'UI/UX & Web Development', 'logo_path' => 'company-logos/lumina-software-house.png'],
        ['name' => 'NexusNode Tech', 'tagline' => 'IoT & Smart Systems', 'logo_path' => 'company-logos/nexusnode-tech.png'],
        ['name' => 'OmniStack Engineering', 'tagline' => 'Full-Stack Development Platforms', 'logo_path' => 'company-logos/omnistack-engineering.png'],
        ['name' => 'PixelCraft Digital', 'tagline' => 'Interactive Media & Software', 'logo_path' => 'company-logos/pixelcraft-digital.png'],
        ['name' => 'QuantumLeap IT', 'tagline' => 'Next-Gen IT Consulting', 'logo_path' => 'company-logos/quantumleap-it.png'],
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
                ?? new Company();

            $company->fill([
                'name' => $data['name'],
                'slug' => $slug,
                'tagline' => $data['tagline'],
                'logo_path' => $data['logo_path'],
                'linkedin_url' => 'https://www.linkedin.com/in/mouad-lotfi/',
                'website_url' => 'https://mouadlotfi.com/',
            ]);
            // The logo URL uses updated_at as its cache version. Force a new
            // version when seeded logo files are replaced.
            $company->updated_at = now();
            $company->save();
        }

        $this->command->info(count(self::COMPANIES) . ' companies synced successfully!');
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
