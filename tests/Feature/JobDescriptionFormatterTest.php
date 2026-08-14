<?php

namespace Tests\Feature;

use App\Support\JobDescriptionFormatter;
use Tests\TestCase;

class JobDescriptionFormatterTest extends TestCase
{
    public function test_it_turns_the_seed_format_into_structured_html(): void
    {
        $input = "We're looking for a Laravel Engineer.\r\n"
            . "Responsibilities\r\n"
            . "\r\n"
            . "    Build and maintain web applications using Laravel.\r\n"
            . "    Develop RESTful APIs.\r\n"
            . "\r\n"
            . "Requirements\r\n"
            . "\r\n"
            . "    Proven experience with PHP.\r\n";

        $html = JobDescriptionFormatter::format($input);

        $this->assertStringContainsString('<p>We&#039;re looking for a Laravel Engineer.</p>', $html);
        $this->assertStringContainsString('<h3>Responsibilities</h3>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Build and maintain web applications using Laravel.</li>', $html);
        $this->assertStringContainsString('<li>Develop RESTful APIs.</li>', $html);
        $this->assertStringContainsString('<h3>Requirements</h3>', $html);
        $this->assertStringContainsString('<li>Proven experience with PHP.</li>', $html);
        $this->assertStringNotContainsString('&lt;script&gt;', $html);
    }

    public function test_it_escapes_html_instead_of_injecting_it(): void
    {
        $html = JobDescriptionFormatter::format("Intro\r\n\r\n    <script>alert(1)</script> item\r\n");

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_recruiter_job_show_uses_the_formatter_and_has_the_edit_key(): void
    {
        $view = file_get_contents(resource_path('views/recruiter/jobs/show.blade.php'));
        $candidateView = file_get_contents(resource_path('views/jobs/show.blade.php'));
        $en = file_get_contents(resource_path('lang/en/recruiter.php'));

        $this->assertStringContainsString('JobDescriptionFormatter::format', $view);
        $this->assertStringContainsString('JobDescriptionFormatter::format', $candidateView);
        $this->assertStringContainsString("'edit_job' =>", $en);
        $this->assertStringNotContainsString('nl2br(e($job->description))', $candidateView);
    }
}
