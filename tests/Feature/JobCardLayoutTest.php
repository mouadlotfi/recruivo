<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class JobCardLayoutTest extends TestCase
{
    public function test_jobs_grid_stretches_cards_to_equal_row_heights(): void
    {
        $view = File::get(resource_path('views/jobs/index.blade.php'));
        $card = File::get(resource_path('views/components/job-card.blade.php'));

        $this->assertStringNotContainsString('items-start gap-4', $view);
        $this->assertStringContainsString('h-full', $card);
    }

    public function test_job_card_does_not_overreserve_space_for_top_actions(): void
    {
        $view = File::get(resource_path('views/components/job-card.blade.php'));

        $this->assertStringContainsString("'pr-16'", $view);
        $this->assertStringNotContainsString("'pr-44'", $view);
    }
}
