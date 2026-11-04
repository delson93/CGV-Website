<?php
/** Search and social metadata generated from the same content as the page. */
$settings = ($settings ?? getSiteSettings()) + homepageDefaults();
$offerings = $offerings ?? getOfferings(true);
require_once __DIR__ . '/seo.php';
$canonical = seoCanonical($settings);
$title = trim($settings['seo_meta_title'] ?? '') ?: $settings['site_name'] . ' | Event Venue & Pool in Mangalore';
$description = trim($settings['seo_meta_description'] ?? '') ?: 'Explore event spaces, swimming pool bookings and rooms at Charles Gala Venue in Kulur, Mangalore. Contact our team for availability and packages.';
$imagePath = publicImage($settings['hero_image_path']);
// A brand logo gives social previews a useful image while the background is a placeholder.
if ($imagePath === 'assets/images/placeholder.svg') $imagePath = 'assets/images/logo.png';
$imageUrl = str_starts_with($imagePath, 'https://') ? $imagePath : $canonical . $imagePath;
$venue = [
    '@type' => 'EventVenue', '@id' => $canonical . '#venue',
    'name' => $settings['site_name'], 'description' => $description,
    'url' => $canonical, 'image' => $imageUrl,
    'address' => $settings['address'] ?? '',
];
if (!empty($settings['email'])) $venue['email'] = $settings['email'];
if (!empty($settings['google_maps_url'])) $venue['hasMap'] = $settings['google_maps_url'];
if (is_numeric($settings['latitude'] ?? '') && is_numeric($settings['longitude'] ?? '') && abs((float)$settings['latitude']) <= 90 && abs((float)$settings['longitude']) <= 180) {
    $venue['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)$settings['latitude'], 'longitude' => (float)$settings['longitude']];
}
$venue['sameAs'] = array_values(array_filter([
    $settings['instagram_url'] ?? '', $settings['youtube_url'] ?? '', $settings['facebook_url'] ?? ''
], static fn($url) => filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'));
$jsonLd = ['@context' => 'https://schema.org', '@graph' => [
    $venue,
    ['@type' => 'WebSite', '@id' => $canonical . '#website', 'url' => $canonical, 'name' => $settings['site_name'], 'inLanguage' => 'en-IN'],
    ['@type' => 'WebPage', '@id' => $canonical . '#webpage', 'url' => $canonical, 'name' => $title, 'description' => $description,
        'isPartOf' => ['@id' => $canonical . '#website'], 'about' => ['@id' => $canonical . '#venue'], 'inLanguage' => 'en-IN'],
]];
$services = [];
foreach ($offerings as $item) {
    $services[] = ['@type'=>'Offer', 'itemOffered'=>['@type'=>'Service','name'=>$item['title'],'description'=>$item['short_desc'],'provider'=>['@id'=>$canonical.'#venue']]];
}
if ($services) $jsonLd['@graph'][0]['hasOfferCatalog'] = ['@type'=>'OfferCatalog','name'=>'Venue spaces and experiences','itemListElement'=>$services];
$faqEntries = [];
foreach (seoFaqs($settings) as $faq) $faqEntries[] = ['@type'=>'Question','name'=>$faq['question'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$faq['answer']]];
if ($faqEntries) $jsonLd['@graph'][] = ['@type'=>'FAQPage','@id'=>$canonical.'#faq','mainEntity'=>$faqEntries];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?></title>
<meta name="description" content="<?= htmlspecialchars($description) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#0c1713">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_IN">
<meta property="og:site_name" content="<?= htmlspecialchars($settings['site_name']) ?>">
<meta property="og:title" content="<?= htmlspecialchars($title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($description) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:image" content="<?= htmlspecialchars($imageUrl) ?>">
<meta property="og:image:alt" content="<?= htmlspecialchars($settings['site_name']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($imageUrl) ?>">
<link rel="icon" type="image/png" href="assets/images/logo.png">
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

