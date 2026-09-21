<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Property;
use App\Core\Request;
use App\Core\Response;

/**
 * SEO surfaces: robots.txt, sitemap index and sitemap parts. Only published,
 * indexable records are emitted.
 */
final class SeoController extends Controller
{
    public function robots(Request $request): Response
    {
        $lines = ['User-agent: *'];

        foreach ((array) config('seo.robots.disallow', []) as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        return Response::text(implode(PHP_EOL, $lines) . PHP_EOL)
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemapIndex(Request $request): Response
    {
        $today = gmdate('Y-m-d');
        $urls = [
            ['loc' => url('/sitemap-properties.xml'), 'lastmod' => $today],
            ['loc' => url('/sitemap-destinations.xml'), 'lastmod' => $today],
            ['loc' => url('/sitemap-pages.xml'), 'lastmod' => $today],
        ];

        return Response::xml($this->sitemapIndexXml($urls));
    }

    public function sitemapProperties(Request $request): Response
    {
        $rows = DatabaseSitemap::propertyUrls();
        return Response::xml($this->urlSetXml($rows));
    }

    public function sitemapDestinations(Request $request): Response
    {
        $rows = [];
        foreach (Property::regionsWithCounts(200) as $region) {
            $rows[] = [
                'loc' => url('/stays/' . slugify((string) $region['region'])),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }
        return Response::xml($this->urlSetXml($rows));
    }

    public function sitemapPages(Request $request): Response
    {
        $rows = [
            ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => url('/stays'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => url('/about'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => url('/contact'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => url('/help'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ['loc' => url('/terms'), 'changefreq' => 'yearly', 'priority' => '0.2'],
            ['loc' => url('/privacy'), 'changefreq' => 'yearly', 'priority' => '0.2'],
        ];

        return Response::xml($this->urlSetXml($rows));
    }

    /**
     * @param array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string}> $urls
     */
    private function sitemapIndexXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <sitemap>\n    <loc>" . htmlspecialchars($url['loc'], ENT_QUOTES) . "</loc>\n"
                . (isset($url['lastmod']) ? '    <lastmod>' . $url['lastmod'] . "</lastmod>\n" : '')
                . "  </sitemap>\n";
        }

        return $xml . '</sitemapindex>';
    }

    /**
     * @param array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string}> $rows
     */
    private function urlSetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($rows as $row) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($row['loc'], ENT_QUOTES) . "</loc>\n";
            if (isset($row['lastmod'])) {
                $xml .= '    <lastmod>' . $row['lastmod'] . "</lastmod>\n";
            }
            if (isset($row['changefreq'])) {
                $xml .= '    <changefreq>' . $row['changefreq'] . "</changefreq>\n";
            }
            if (isset($row['priority'])) {
                $xml .= '    <priority>' . $row['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        return $xml . '</urlset>';
    }
}
