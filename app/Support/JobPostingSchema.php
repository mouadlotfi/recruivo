<?php

namespace App\Support;

use App\Models\Job;

/**
 * The schema.org JobPosting for a job detail page.
 *
 * This is what puts a posting into Google's jobs experience, which is where
 * candidates search - without it a job page is an ordinary page. It is also the
 * only structured data on the site, so it is worth being pedantic about: a field
 * emitted with a guessed value is worse than a field left out, because the guess
 * is republished as a fact about someone's vacancy. Two fields are deliberately
 * absent for that reason, and both say so where they would have gone.
 *
 * Only the fields this application actually holds are emitted.
 */
final class JobPostingSchema
{
    /**
     * @return array<string, mixed>
     */
    public function build(Job $job, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => (string) $job->title,
            // Google treats the description as HTML. This is the same escaped
            // formatter the page renders with, so nothing user-controlled
            // reaches the crawler unescaped.
            'description' => JobDescriptionFormatter::format((string) $job->description),
            'datePosted' => $job->published_at?->toIso8601String(),
            'url' => localized_route('jobs.show', ['job' => $job->id], $locale),
            // Applications happen on this site rather than on an ATS, which is
            // exactly what Google means by direct apply.
            'directApply' => true,
            'identifier' => array_filter([
                '@type' => 'PropertyValue',
                'name' => $job->company?->name,
                'value' => (string) $job->id,
            ], fn ($value) => filled($value)),
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => $job->company?->name,
                'sameAs' => $job->company?->website_url,
                'logo' => $job->company?->logo_url,
            ], fn ($value) => filled($value)),
        ];

        // Google wants an expiry when the posting has one. A job closes at the
        // end of its closing day, not at midnight on it - the same reading the
        // Job model uses to decide whether a posting is still public.
        if ($job->closes_at !== null) {
            $schema['validThrough'] = $job->closes_at->copy()->endOfDay()->toIso8601String();
        }

        if ($job->remote_type === 'remote') {
            // TELECOMMUTE is how Google recognises a remote role. Its partner
            // field, applicantLocationRequirements, is not emitted: the
            // application has no column for the regions a role is open to, and
            // inventing one would state a hiring restriction the employer never
            // set. Google may warn about the omission; a wrong answer is worse.
            $schema['jobLocationType'] = 'TELECOMMUTE';
        }

        if (filled($job->location)) {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    // location is a single free-text line ("Dublin, Ireland"),
                    // so it can only be offered as a locality. Splitting it into
                    // street/region/postal would invent structure it never had.
                    'addressLocality' => $job->location,
                ],
            ];
        }

        // No baseSalary. jobs.salary_min and salary_max are bare integers: the
        // schema holds no currency and no period, so any MonetaryAmount would
        // assert a currency and a timescale the employer never chose - and
        // Google republishes salary as fact. Silence is the honest form.

        return $schema;
    }
}
