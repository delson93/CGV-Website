<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/xml; charset=utf-8');
$settings = getSiteSettings();
$base = rtrim($settings['seo_canonical_url'] ?: 'https://charlesgala.in', '/');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= htmlspecialchars($base) ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
