<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Models\Post;
use DOMDocument;
use DOMElement;
use Illuminate\Http\Response;

/**
 * The crawler-facing endpoints: robots.txt and the sitemap.
 *
 * Both are generated rather than committed as static files, because the same
 * image serves every environment and only production should be indexed, and
 * because a sitemap committed by hand drifts from the content the moment anyone
 * posts a job.
 */
class SeoController extends Controller
{
    private const SITEMAP_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /**
     * Public pages that always exist, named as routes.
     *
     * @var array<int, string>
     */
    private const STATIC_ROUTES = ['home', 'jobs.index', 'companies.index', 'posts.index'];

    /**
     * Areas that need a session. They are not secret, but there is nothing in
     * them a crawler could index and they are the expensive pages to render.
     *
     * @var array<int, string>
     */
    private const PRIVATE_PATHS = ['admin', 'candidate', 'recruiter', 'profile'];

    public function robots(): Response
    {
        // A demo is not the product. Indexing it would put a second copy of the
        // same listings in front of candidates, advertising vacancies nobody is
        // hiring for, and compete with the real site for its own content.
        if ($this->isDemo()) {
            return $this->plain("User-agent: *\nDisallow: /\n");
        }

        $lines = ['User-agent: *', 'Disallow: /api/'];

        foreach ($this->locales() as $locale) {
            foreach (self::PRIVATE_PATHS as $path) {
                $lines[] = "Disallow: /{$locale}/{$path}";
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.url('/sitemap.xml');

        return $this->plain(implode("\n", $lines)."\n");
    }

    public function sitemap(): Response
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $urlset = $document->createElement('urlset');
        $urlset->setAttribute('xmlns', self::SITEMAP_NAMESPACE);
        $document->appendChild($urlset);

        foreach ($this->sitemapUrls() as $url) {
            $element = $document->createElement('url');
            $element->appendChild($this->element($document, 'loc', $url['loc']));

            if ($url['lastmod'] !== null) {
                $element->appendChild($this->element($document, 'lastmod', $url['lastmod']));
            }

            $urlset->appendChild($element);
        }

        return response($document->saveXML(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Every public URL, one entry per locale.
     *
     * No xhtml:link alternates: the root shell already emits hreflang for every
     * page, and repeating the mapping here would give it two sources to drift
     * apart. lastmod is the row's updated_at, which is the closest thing the
     * schema has to a modification time.
     *
     * cursor() rather than get(): a crawler rebuilds this on every visit, and
     * streaming keeps memory flat as the board grows. A sitemap index is the
     * next step if the URL count ever approaches the 50,000 limit.
     *
     * @return iterable<int, array{loc: string, lastmod: ?string}>
     */
    private function sitemapUrls(): iterable
    {
        $locales = $this->locales();

        foreach ($locales as $locale) {
            foreach (self::STATIC_ROUTES as $route) {
                yield ['loc' => localized_route($route, [], $locale), 'lastmod' => null];
            }
        }

        foreach (Job::published()->select(['id', 'updated_at'])->cursor() as $job) {
            foreach ($locales as $locale) {
                yield [
                    'loc' => localized_route('jobs.show', ['job' => $job->id], $locale),
                    'lastmod' => $job->updated_at?->toAtomString(),
                ];
            }
        }

        foreach (Company::select(['slug', 'updated_at'])->cursor() as $company) {
            foreach ($locales as $locale) {
                yield [
                    'loc' => localized_route('companies.show', ['slug' => $company->slug], $locale),
                    'lastmod' => $company->updated_at?->toAtomString(),
                ];
            }
        }

        foreach (Post::published()->select(['id', 'slug', 'updated_at'])->cursor() as $post) {
            foreach ($locales as $locale) {
                // A post's slug is translated per locale, and a post is very
                // often written in one language only. A locale with no slug has
                // no page, so there is nothing to list for it.
                $slug = $post->getTranslation('slug', $locale, false);

                if ($slug === null) {
                    continue;
                }

                yield [
                    'loc' => localized_route('posts.show', ['slug' => $slug], $locale),
                    'lastmod' => $post->updated_at?->toAtomString(),
                ];
            }
        }
    }

    /**
     * An element whose text is escaped. createElement() takes its value raw and
     * would emit a bare "&" for any title containing one, producing XML that
     * parsers reject.
     */
    private function element(DOMDocument $document, string $name, string $value): DOMElement
    {
        $element = $document->createElement($name);
        $element->appendChild($document->createTextNode($value));

        return $element;
    }

    /**
     * @return array<int, string>
     */
    private function locales(): array
    {
        return collect(config('locales.available', []))
            ->filter(fn (array $locale): bool => $locale['enabled'] ?? true)
            ->keys()
            ->all();
    }

    private function isDemo(): bool
    {
        return app()->environment('demo') || (bool) config('app.is_demo');
    }

    private function plain(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
