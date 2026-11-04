<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
$settings = getSiteSettings() + homepageDefaults();
$about = getAboutSection();
$offerings = getOfferings(true);
$gallery = getGalleryItems();
$faqs = seoFaqs($settings);
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function webLink($url): string { return filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true) ? $url : '#location'; }
$wa = buildWhatsAppUrl($settings['whatsapp_number'] ?? '', $settings['whatsapp_default_msg'] ?? 'Hello, I would like to enquire about availability at Charles Gala Venue.');
$call = buildCallUrl($settings['phone_primary'] ?? '');
$poolImage = 'assets/images/resort/pool-building.jpg';
foreach ($offerings as $item) { if (($item['category'] ?? '') === 'pool') { $poolImage = publicImage($item['image_path']); break; } }
$videoId = backgroundYoutubeId($settings['hero_video_url'] ?? 'https://www.youtube.com/watch?v=zVg8op029Mg');
?>
<!doctype html><html lang="en-IN"><head>
<?php include __DIR__ . '/includes/seo_head.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/venue.css?v=2">
<script src="assets/js/venue.js?v=2" defer></script>
</head><body>
<a class="skip" href="#main">Skip to content</a><div class="progress" aria-hidden="true"></div>
<header><div class="wrap bar"><a class="brand" href="#home" aria-label="<?= esc($settings['site_name']) ?> home"><img src="assets/images/logo.png" width="42" height="42" alt=""><span><?= esc($settings['site_name']) ?><small>KULUR, MANGALORE</small></span></a>
<button class="menu" aria-expanded="false" aria-controls="navigation" type="button">Menu</button>
<nav id="navigation" aria-label="Main navigation"><a href="#spaces">Our spaces</a><a href="#pool">The pool</a><a href="#gallery">Gallery</a><a href="#location">Find us</a><a class="btn solid" href="<?= esc($wa) ?>" target="_blank" rel="noopener">Plan your visit</a></nav></div></header>
<main id="main"><section id="home" class="hero">
<figure class="photo"><img src="<?= esc(publicImage($settings['hero_image_path'])) ?>" alt="Palm-lined lawn at Charles Gala Venue in Kulur, Mangalore" width="1600" height="1000" fetchpriority="high"><div class="hero-video" data-video="<?= esc($videoId) ?>" aria-hidden="true"></div></figure>
<div class="wrap"><p class="eyebrow"><?= esc($settings['hero_eyebrow']) ?></p><h1><?php $words = preg_split('/\s+/', trim($settings['hero_heading'])); foreach ($words as $i=>$word): ?><span class="w"><span style="--i:<?= $i ?>"<?= $i === count($words)-1 ? ' class="gold-text"' : '' ?>><?= esc($word) ?></span></span> <?php endforeach; ?></h1><p><?= esc($settings['hero_description']) ?></p><div class="cta"><a class="btn solid" href="<?= esc($wa) ?>" target="_blank" rel="noopener">Check availability</a><a class="btn ghost" href="#spaces">Explore the venue</a></div><div class="tags"><span>Celebrate</span><span>Swim</span><span>Stay</span></div></div>
<button type="button" class="motion-toggle" hidden aria-pressed="false">Pause motion</button></section>
<div class="band" aria-hidden="true"><div class="track"><?php for($n=0;$n<2;$n++): ?><span>Celebrate ✦ Swim ✦ Stay ✦ <?php foreach($offerings as $item): ?><?= esc($item['title']) ?> ✦ <?php endforeach; ?></span><?php endfor; ?></div></div>
<section id="spaces"><div class="wrap"><div class="head" data-reveal><div class="rule"></div><h2>Good company.<br>A great setting.</h2><p>From a celebration with everyone you love to a relaxed day by the pool, find a space that suits your plans.</p></div>
<?php foreach ($offerings as $item): ?><article class="space" id="<?= esc($item['slug']) ?>" data-reveal><figure class="photo"><img src="<?= esc(publicImage($item['image_path'])) ?>" alt="<?= esc($item['title']) ?> at Charles Gala Venue" width="800" height="1000" loading="lazy" decoding="async"></figure><div><p class="kind"><?= esc($item['badge']) ?></p><h3><?= esc($item['title']) ?></h3><p><?= esc($item['short_desc']) ?></p><?php if (!empty($item['capacity_label'])): ?><span class="cap"><?= esc($item['capacity_label']) ?></span><br><?php endif; ?><a class="link" href="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'], $item['whatsapp_custom_msg'] ?: 'Hello, I would like to enquire about ' . $item['title'] . '.')) ?>" target="_blank" rel="noopener">Enquire about this space ↗</a></div></article><?php endforeach; ?></div></section>
<section id="pool" class="pool"><figure class="photo"><img src="<?= esc($poolImage) ?>" alt="Swimming pool at Charles Gala Venue" width="1600" height="1000" loading="lazy" decoding="async"></figure><div class="wrap" data-reveal><h2><?= esc($settings['pool_heading']) ?></h2><p><?= esc($settings['pool_description']) ?></p><a class="btn solid" href="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'], 'Hello, please share swimming pool timings, rates and availability.')) ?>" target="_blank" rel="noopener">Plan a pool day</a></div></section>
<section id="about" class="welcome"><div class="wrap" data-reveal><div><div class="rule"></div><h2><?= esc($about['headline'] ?? 'Your people. Your occasion. Our place.') ?></h2></div><div><p><?= esc($about['subheadline'] ?? '') ?></p><div class="about-copy"><?= $about['content_html'] ?? '' ?></div></div></div></section>
<section id="gallery"><div class="wrap"><div class="head" data-reveal><div class="rule"></div><h2>Picture your day here.</h2><p>Explore the lawn, pool and celebration spaces. Select a photograph for a closer look.</p></div><div class="grid">
<?php foreach ($gallery as $i=>$g): ?>
<?php if (($g['media_type'] ?? '') === 'youtube' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $g['youtube_id'] ?? '')): ?><a class="video-link" href="https://www.youtube.com/watch?v=<?= esc($g['youtube_id']) ?>" target="_blank" rel="noopener"><span>Watch venue video ↗</span><strong><?= esc($g['title']) ?></strong></a>
<?php else: ?><a class="photo <?= $i%6===0 ? 'tall' : ($i%6===1 ? 'wide' : '') ?>" href="<?= esc(publicImage($g['image_path'] ?? '')) ?>" data-photo><img src="<?= esc(publicImage($g['image_path'] ?? '')) ?>" alt="<?= esc($g['title']) ?>" width="800" height="600" loading="lazy" decoding="async"><span class="caption"><?= esc($g['title']) ?> ↗</span></a><?php endif; endforeach; ?>
</div></div></section>
<?php if($faqs): ?><section id="faq" class="welcome"><div class="wrap faq" style="display:block"><div class="head"><div class="rule"></div><h2>Before your visit.</h2></div><?php foreach($faqs as $faq): ?><details><summary><?= esc($faq['question']) ?></summary><p><?= esc($faq['answer']) ?></p></details><?php endforeach; ?></div></section><?php endif; ?>
<section id="location" class="visit"><div class="wrap" data-reveal><h2>Your next occasion<br><span class="gold-text">starts here.</span></h2><p class="intro">Tell us your preferred date, occasion and number of guests. Our team will help you with availability and arrangements.</p><div class="cta"><a class="btn solid" href="<?= esc($wa) ?>" target="_blank" rel="noopener">Message on WhatsApp</a><a class="btn ghost" href="<?= esc($call) ?>">Call the venue</a></div><h3>Visit <?= esc($settings['site_name']) ?></h3><address><?= esc($settings['address']) ?></address><a class="email" href="mailto:<?= esc($settings['email']) ?>"><?= esc($settings['email']) ?></a><a class="link" href="<?= esc(webLink($settings['google_maps_url'] ?? '')) ?>" target="_blank" rel="noopener">Get directions ↗</a><p class="intro">Contact us to arrange a visit before you travel.</p></div></section>
</main><footer><div class="wrap"><a class="brand" href="#home"><?= esc($settings['site_name']) ?></a><nav aria-label="Social links"><?php foreach (['instagram_url'=>'Instagram','facebook_url'=>'Facebook','youtube_url'=>'YouTube'] as $key=>$label): if (!empty($settings[$key])): ?><a href="<?= esc(webLink($settings[$key])) ?>" target="_blank" rel="noopener"><?= $label ?> ↗</a><?php endif; endforeach; ?></nav></div><div class="wrap credits"><span>© <?= date('Y') ?> <?= esc($settings['site_name']) ?></span><span>Designed and Developed by <a href="https://codeixe.com/">CodeIXE AI Solutions</a></span></div></footer>
<dialog id="viewer"><button type="button" aria-label="Close photograph">Close ×</button><img alt=""><p></p></dialog>
</body></html>
