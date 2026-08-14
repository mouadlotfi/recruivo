<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class JobCardBadgeTest extends TestCase
{
    public function test_job_cards_color_code_work_mode_and_department_badges(): void
    {
        $contents = File::get(resource_path('views/components/job-card.blade.php'));

        // Work-mode badges are color-coded by remote type (green hybrid, orange onsite, purple remote).
        $this->assertStringContainsString("'hybrid' => 'bg-green-100", $contents);
        $this->assertStringContainsString("'onsite' => 'bg-orange-100", $contents);
        $this->assertStringContainsString("'remote' => 'bg-purple-100", $contents);

        // Department badge is blue.
        $this->assertStringContainsString('bg-blue-100', $contents);

        // No leftover monochrome badges.
        $this->assertStringNotContainsString('bg-amber-100 px-2 py-0.5 font-medium text-amber-700', $contents);
        $this->assertStringNotContainsString('bg-stone-100 px-2 py-0.5 font-medium text-stone-700', $contents);
    }
}
