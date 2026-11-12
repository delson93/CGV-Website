<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/seo.php';

if (!isset($bookingKey, bookingRoutes()[$bookingKey])) {
    http_response_code(404);
    exit;
}
$settings = getSiteSettings() + publicCopyDefaults();
$page = bookingContent($bookingKey, $settings);
$home = seoCanonical($settings);
$url = $home . bookingRoutes()[$bookingKey];
$site = $settings['site_name'];
$title = trim($page['seo_title'] ?? '') ?: $page['title'] . ' ' . $settings['copy_booking_details_label'] . ' | ' . $site;
$offers = [];
$summary = [];
foreach ($page['packages'] as $package) {
    $summary[] = $package['name'] . ' ₹' . number_format($package['price']);
    $offers[] = [
        '@type' => 'Offer',
        'name' => $package['name'],
        'price' => $package['price'],
        'priceCurrency' => 'INR',
        'url' => $url,
        'description' => implode(' ', $package['facilities']),
    ];
}
$description = trim($page['seo_description'] ?? '') ?: implode('. ', $summary) . '. ' . $page['notes'];
$image = '';
$message = $settings['whatsapp_default_msg'] ?? '';
foreach (getOfferings(true) as $item) {
    if ($item['slug'] === $bookingKey) {
        $image = publicImage($item['image_path']);
        $message = trim($item['whatsapp_custom_msg'] ?? '') ?: $message;
        break;
    }
}
$showImage = $image !== '' && $image !== 'assets/images/placeholder.svg';
$socialImage = $showImage ? $image : 'assets/images/logo.png';
if (!str_starts_with($socialImage, 'https://')) $socialImage = $home . $socialImage;
$schema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type'=>'WebPage', '@id'=>$url, 'url'=>$url, 'name'=>$title, 'description'=>$description, 'inLanguage'=>'en-IN',
         'isPartOf'=>['@id'=>$home.'#website'], 'mainEntity'=>['@id'=>$url.'#service'], 'breadcrumb'=>['@id'=>$url.'#breadcrumbs']],
        ['@type'=>'Service', '@id'=>$url.'#service', 'name'=>$page['title'], 'description'=>$page['intro'],
         'provider'=>['@type'=>'Organization', 'name'=>$site, 'url'=>$home], 'offers'=>$offers],
        ['@type'=>'BreadcrumbList', '@id'=>$url.'#breadcrumbs', 'itemListElement'=>[
            ['@type'=>'ListItem', 'position'=>1, 'name'=>$site, 'item'=>$home],
            ['@type'=>'ListItem', 'position'=>2, 'name'=>$page['title'], 'item'=>$url],
        ]],
    ],
];
function bookingEscape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= bookingEscape($title) ?></title>
    <meta name="description" content="<?= bookingEscape($description) ?>">
    <link rel="canonical" href="<?= bookingEscape($url) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= bookingEscape($site) ?>">
    <meta property="og:title" content="<?= bookingEscape($title) ?>">
    <meta property="og:description" content="<?= bookingEscape($description) ?>">
    <meta property="og:url" content="<?= bookingEscape($url) ?>">
    <meta property="og:image" content="<?= bookingEscape($socialImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/booking.css">
    <script type="application/ld+json"><?= json_encode($schema, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<header class="mast"><a href="./"><img src="assets/images/logo.png" width="44" height="44" alt=""><?= bookingEscape($site) ?></a></header>
<main>
    <nav class="crumb" aria-label="Breadcrumb"><a href="./#spaces"><?= bookingEscape($settings['copy_nav_spaces']) ?></a> / <?= bookingEscape($page['title']) ?></nav>
    <section class="intro">
        <div><p class="label"><?= bookingEscape($settings['copy_booking_details_label']) ?></p>
        <h1><?= bookingEscape($page['title']) ?></h1><p><?= bookingEscape($page['intro']) ?></p></div>
        <?php if ($showImage): ?><img src="<?= bookingEscape($image) ?>" alt="<?= bookingEscape($page['title']) ?>" width="680" height="410"><?php endif; ?>
    </section>
    <div class="packages">
        <?php foreach ($page['packages'] as $package): ?>
        <article>
            <h2><?= bookingEscape($package['name']) ?></h2>
            <p class="price">₹<?= number_format($package['price']) ?></p>
            <ul><?php foreach ($package['facilities'] as $facility): ?><li><?= bookingEscape($facility) ?></li><?php endforeach; ?></ul>
        </article>
        <?php endforeach; ?>
    </div>
    <section class="notes"><h2><?= bookingEscape($settings['copy_booking_notes_label']) ?></h2><p><?= nl2br(bookingEscape($page['notes'])) ?></p></section>
    <div class="actions">
        <a class="cta" href="<?= bookingEscape(buildWhatsAppUrl($settings['whatsapp_number'], $message)) ?>"><?= bookingEscape($settings['copy_hero_cta']) ?></a>
        <a href="./#spaces">← <?= bookingEscape($settings['copy_nav_spaces']) ?></a>
    </div>
</main>
<footer><?= bookingEscape($site) ?> · <?= bookingEscape($settings['address']) ?></footer>
</body></html>
