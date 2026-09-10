<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The static legal documents.
 *
 * Copy lives in resources/lang/{locale}/legal.php as title + sections, so a
 * wording change never needs a code change and one page component renders either
 * document. Also passed as `meta` so the shell renders the shareable tags
 * server-side (see resources/views/inertia.blade.php).
 */
class LegalController extends Controller
{
    public function privacy(): Response
    {
        return $this->document('privacy');
    }

    public function terms(): Response
    {
        return $this->document('terms');
    }

    private function document(string $key): Response
    {
        /** @var array{title: string, summary: string, sections: array<int, array{heading: string, body: string}>} $document */
        $document = __('legal.'.$key);

        // Single source for the published address (see config/mail.php).
        $contactEmail = config('mail.contact_address');

        return Inertia::render('Legal/Show', [
            'document' => [
                'title' => $document['title'],
                'summary' => $document['summary'],
                'sections' => array_values($document['sections']),
                'updated' => __('legal.updated_date'),
            ],
            'labels' => [
                'updated' => __('legal.updated_label', ['date' => __('legal.updated_date')]),
                'back_home' => __('legal.back_home'),
                'contact' => __('common.contact'),
                'questions' => __('legal.questions', ['email' => $contactEmail]),
            ],
            'contact_email' => $contactEmail,
            'meta' => [
                'title' => $document['title'],
                'description' => $document['summary'],
            ],
        ]);
    }
}
