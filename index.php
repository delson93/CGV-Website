<?php
require_once __DIR__ . '/includes/functions.php';
$settings = getSiteSettings() + homepageDefaults();
$heroVideoId = backgroundYoutubeId($settings['hero_video_url']);
$about = getAboutSection();
$offerings = getOfferings(true);
$gallery = getGalleryItems();
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function webLink($url): string { return filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true) ? $url : '#location'; }
$wa = buildWhatsAppUrl($settings['whatsapp_number'] ?? '', $settings['whatsapp_default_msg'] ?? 'Hello, I would like to enquire about availability at Charles Gala Venue.');
$call = buildCallUrl($settings['phone_primary'] ?? '');
$poolImage = 'assets/images/resort/pool-building.jpg';
foreach ($offerings as $item) { if (($item['category'] ?? '') === 'pool') { $poolImage = publicImage($item['image_path']); break; } }
?>
<!doctype html><html lang="en"><head>
<?php include __DIR__ . '/includes/seo_head.php'; ?>
<link rel="stylesheet" href="assets/css/modern.css?v=2">
<script src="assets/js/modern.js" defer></script>
<?php if ($heroVideoId): ?><script src="assets/js/background-video.js?v=1" defer></script><?php endif; ?>
</head><body>
<a class="skip" href="#main">Skip to content</a>
<header class="header"><div class="wrap nav-row">
<a class="brand" href="#home" aria-label="<?= esc($settings['site_name']) ?> home"><img src="assets/images/logo.png" width="44" height="44" alt=""><span><?= esc($settings['site_name']) ?><small>KULUR · MANGALORE</small></span></a>
<button class="menu" aria-expanded="false" aria-controls="navigation" type="button">Menu <span aria-hidden="true">☰</span></button>
<nav id="navigation" aria-label="Main navigation"><a href="#spaces">Our spaces</a><a href="#pool">The pool</a><a href="#gallery">Gallery</a><a href="#location">Find us</a></nav>
<a class="button dark nav-book" href="<?= esc($wa) ?>" target="_blank" rel="noopener">Plan your visit <span aria-hidden="true">↗</span></a>
</div></header>
<main id="main">
<section id="home" class="hero wrap"><div class="hero-copy"><p class="eyebrow"><?= esc($settings['hero_eyebrow']) ?></p><h1><?= esc($settings['hero_heading']) ?></h1><p class="intro"><?= esc($settings['hero_description']) ?></p><div class="actions"><a class="button dark" href="<?= esc($wa) ?>" target="_blank" rel="noopener">Check availability ↗</a><a class="text-link" href="#spaces">Explore the venue ↓</a></div><div class="hero-foot"><span>CELEBRATE</span><span>SWIM</span><span>STAY</span></div></div>
<div class="hero-visual"<?= $heroVideoId ? ' data-video-id="' . esc($heroVideoId) . '"' : '' ?>><img src="<?= esc(publicImage($settings['hero_image_path'])) ?>" alt="Palm-lined lawn at Charles Gala Venue in Kulur, Mangalore" width="1200" height="900" fetchpriority="high"><?php if ($heroVideoId): ?><div class="hero-video" aria-hidden="true"><div id="hero-player"></div></div><button class="video-toggle" type="button" hidden>Pause video</button><?php endif; ?><div class="photo-label"><span>YOUR NEXT GET-TOGETHER</span><strong>A little closer to nature.</strong></div><a class="round-link" href="#gallery" aria-label="Explore venue photographs">↗</a></div></section>
<section id="spaces" class="section wrap"><div class="section-top"><div><p class="eyebrow">MAKE IT YOUR OCCASION</p><h2>Good company.<br>A great setting.</h2></div><p>From a celebration with everyone you love to a relaxed day by the pool, find a space that suits your plans.</p></div>
<div class="spaces"><?php foreach ($offerings as $i => $item): ?><article class="space" id="<?= esc($item['slug']) ?>"><div class="space-image"><img src="<?= esc(publicImage($item['image_path'])) ?>" alt="<?= esc($item['title']) ?> at Charles Gala Venue" width="800" height="600" loading="lazy" decoding="async"><span><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span></div><div class="space-content"><p class="eyebrow"><?= esc($item['badge']) ?></p><h3><?= esc($item['title']) ?></h3><p><?= esc($item['short_desc']) ?></p><?php if (!empty($item['capacity_label'])): ?><p class="capacity"><?= esc($item['capacity_label']) ?></p><?php endif; ?><a class="text-link" href="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'], $item['whatsapp_custom_msg'] ?: 'Hello, I would like to enquire about ' . $item['title'] . '.')) ?>" target="_blank" rel="noopener">Enquire about this space ↗</a></div></article><?php endforeach; ?></div></section>
<section id="pool" class="pool-section"><div class="wrap pool-grid"><div class="pool-photo"><img src="<?= esc($poolImage) ?>" alt="Swimming pool at Charles Gala Venue" width="900" height="1000" loading="lazy" decoding="async"></div><div class="pool-copy"><p class="eyebrow">SLOW DOWN. DIVE IN.</p><h2><?= esc($settings['pool_heading']) ?></h2><p><?= esc($settings['pool_description']) ?></p><a class="button light" href="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'], 'Hello, please share swimming pool timings, rates and availability.')) ?>" target="_blank" rel="noopener">Plan a pool day ↗</a><span class="pool-word" aria-hidden="true">Unwind.</span></div></div></section>
<section id="about" class="section wrap about"><p class="eyebrow">WELCOME TO CHARLES GALA</p><div><h2><?= esc($about['headline'] ?? 'Your people. Your occasion. Our place.') ?></h2><p><?= esc($about['subheadline'] ?? '') ?></p><div class="about-copy"><?= $about['content_html'] ?? '' ?></div></div></section>
<section id="gallery" class="gallery-section section"><div class="wrap"><div class="section-top"><div><p class="eyebrow">TAKE A LOOK AROUND</p><h2>Picture your day here.</h2></div><p>Explore the lawn, pool and celebration spaces. Select a photograph for a closer look.</p></div><div class="gallery">
<?php foreach ($gallery as $g): ?>
<?php if (($g['media_type'] ?? '') === 'youtube' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $g['youtube_id'] ?? '')): ?><a class="gallery-item video-link" href="https://www.youtube.com/watch?v=<?= esc($g['youtube_id']) ?>" target="_blank" rel="noopener"><span>Watch venue video ↗</span><strong><?= esc($g['title']) ?></strong></a>
<?php else: ?><a class="gallery-item" href="<?= esc(publicImage($g['image_path'] ?? '')) ?>" data-photo><img src="<?= esc(publicImage($g['image_path'] ?? '')) ?>" alt="<?= esc($g['title']) ?>" width="800" height="600" loading="lazy" decoding="async"><span><?= esc($g['title']) ?> ↗</span></a><?php endif; ?>
<?php endforeach; ?>
</div></div></section>
<section id="location" class="section wrap contact"><div><p class="eyebrow">LET'S MAKE PLANS</p><h2>Your next occasion<br>starts here.</h2><p>Tell us your preferred date, occasion and number of guests. Our team will help you with availability and arrangements.</p><div class="actions"><a class="button dark" href="<?= esc($wa) ?>" target="_blank" rel="noopener">Message on WhatsApp ↗</a><a class="button outline" href="<?= esc($call) ?>">Call the venue</a></div></div><div class="contact-details"><h3>Visit Charles Gala Venue</h3><address><?= esc($settings['address']) ?></address><a href="mailto:<?= esc($settings['email']) ?>"><?= esc($settings['email']) ?></a><a class="text-link" href="<?= esc(webLink($settings['google_maps_url'] ?? '')) ?>" target="_blank" rel="noopener">Get directions ↗</a><p>Contact us to arrange a visit before you travel.</p></div></section>
</main><footer><div class="wrap footer-row"><a class="brand" href="#home"><?= esc($settings['site_name']) ?></a><nav aria-label="Social links"><?php foreach (['instagram_url'=>'Instagram','facebook_url'=>'Facebook','youtube_url'=>'YouTube'] as $key=>$label): if (!empty($settings[$key])): ?><a href="<?= esc(webLink($settings[$key])) ?>" target="_blank" rel="noopener"><?= $label ?> ↗</a><?php endif; endforeach; ?></nav></div><div class="wrap credits"><span>© <?= date('Y') ?> <?= esc($settings['site_name']) ?></span><span>Designed and Developed by <a href="https://codeixe.com/">CodeIXE AI Solutions</a></span></div></footer>
<dialog id="viewer"><button type="button" aria-label="Close photograph">Close ×</button><img alt=""><p></p></dialog>
</body></html>

