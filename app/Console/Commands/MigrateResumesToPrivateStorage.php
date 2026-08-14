<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\CandidateProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateResumesToPrivateStorage extends Command
{
    protected $signature = 'resumes:migrate-private';

    protected $description = 'Move candidate resumes from the public disk to private storage';

    public function handle(): int
    {
        $paths = CandidateProfile::query()->pluck('resume_path')
            ->merge(Application::query()->pluck('resume_path'))
            ->filter()
            ->unique()
            ->values();

        $migrated = 0;
        $missing = 0;

        foreach ($paths as $path) {
            if (Storage::disk('private')->exists($path)) {
                Storage::disk('public')->delete($path);
                continue;
            }

            if (!Storage::disk('public')->exists($path)) {
                $this->warn("Resume not found: {$path}");
                $missing++;
                continue;
            }

            $stream = Storage::disk('public')->readStream($path);

            if ($stream === false || !Storage::disk('private')->writeStream($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                $this->error("Could not migrate resume: {$path}");
                return self::FAILURE;
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            Storage::disk('public')->delete($path);
            $migrated++;
        }

        $this->info("Migrated {$migrated} resume(s) to private storage; {$missing} referenced file(s) were missing.");

        return self::SUCCESS;
    }
}
