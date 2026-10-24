<?php
/**
 * Dynamic Top-Notch SEO Head & Schema.org JSON-LD Generator
 * Optimized for: best stay, open ground hall, pool for anyone, pool booking, Kulur Mangalore 575013
 * Note: Raw WhatsApp/phone digits are kept out of visible page copy per privacy policy.
 */
$settings = $settings ?? getSiteSettings();
$offerings = $offerings ?? getOfferings(true);

$jsonLd = [
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => ["EventVenue", "Resort", "LodgingBusiness", "LocalBusiness"],
            "@id" => rtrim($settings['seo_canonical_url'], '/') . "/#venue",
            "name" => $settings['site_name'],
            "description" => $settings['seo_meta_description'],
            "url" => $settings['seo_canonical_url'],
            "email" => $settings['email'],
            "priceRange" => "₹₹",
            "maximumAttendeeCapacity" => 1500,
            "publicAccess" => true,
            "image" => [
                rtrim($settings['seo_canonical_url'], '/') . "/assets/images/logo.png",
                rtrim($settings['seo_canonical_url'], '/') . "/uploads/about/hero.jpg"
            ],
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "Kulur, Melkoppala",
                "addressLocality" => "Mangaluru",
                "addressRegion" => "Karnataka",
                "postalCode" => "575013",
                "addressCountry" => "IN"
            ],
            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => (float)$settings['latitude'],
                "longitude" => (float)$settings['longitude']
            ],
            "hasMap" => $settings['google_maps_url'],
            "sameAs" => array_values(array_filter([
                $settings['instagram_url'],
                $settings['youtube_url'],
                $settings['facebook_url'],
                $settings['google_maps_url']
            ])),
            "amenityFeature" => [
                ["@type" => "LocationFeatureSpecification", "name" => "Open Ground Hall (1500 Persons Capacity)", "value" => true],
                ["@type" => "LocationFeatureSpecification", "name" => "AC Mini Hall (250 Persons Capacity)", "value" => true],
                ["@type" => "LocationFeatureSpecification", "name" => "Swimming Pool Open for Anyone (Public & Private Pool Booking)", "value" => true],
                ["@type" => "LocationFeatureSpecification", "name" => "Best Stay Luxury Guest Rooms & Suites", "value" => true],
                ["@type" => "LocationFeatureSpecification", "name" => "In-House Event Decorations and Lightings", "value" => true]
            ]
        ]
    ]
];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($settings['seo_meta_title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($settings['seo_meta_description']) ?>">
<meta name="keywords" content="<?= htmlspecialchars($settings['seo_keywords']) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<link rel="canonical" href="<?= htmlspecialchars($settings['seo_canonical_url']) ?>">

<!-- Geo & Local SEO Meta Tags for Kulur, Mangalore 575013 -->
<meta name="geo.region" content="IN-KA">
<meta name="geo.placename" content="Kulur, Melkoppala, Mangaluru, Karnataka 575013">
<meta name="geo.position" content="<?= htmlspecialchars($settings['latitude']) ?>;<?= htmlspecialchars($settings['longitude']) ?>">
<meta name="ICBM" content="<?= htmlspecialchars($settings['latitude']) ?>, <?= htmlspecialchars($settings['longitude']) ?>">

<!-- Open Graph / Facebook / WhatsApp Rich Preview -->
<meta property="og:type" content="website">
<meta property="og:locale" content="en_IN">
<meta property="og:site_name" content="<?= htmlspecialchars($settings['site_name']) ?>">
<meta property="og:title" content="<?= htmlspecialchars($settings['seo_meta_title']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($settings['seo_meta_description']) ?>">
<meta property="og:url" content="<?= htmlspecialchars($settings['seo_canonical_url']) ?>">
<meta property="og:image" content="<?= htmlspecialchars(rtrim($settings['seo_canonical_url'], '/') . '/uploads/about/hero.jpg') ?>">

<link rel="icon" type="image/png" href="assets/images/logo.png">

<script type="application/ld+json">
<?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
