<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('jobs.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('companies.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ route('contact.show') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('faqs.index') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    @if ($aboutPagePublished)
    <url>
        <loc>{{ route('pages.about') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @endif
    @foreach ($companies as $company)
    <url>
        <loc>{{ route('companies.show', $company->slug) }}</loc>
        <lastmod>{{ $company->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    @foreach ($industrialParks as $industrialPark)
    <url>
        <loc>{{ route('industrial-parks.show', $industrialPark->slug) }}</loc>
        <lastmod>{{ $industrialPark->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
    @foreach ($jobs as $job)
    <url>
        <loc>{{ route('jobs.show', $job->slug) }}</loc>
        <lastmod>{{ $job->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach
</urlset>
