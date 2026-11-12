<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
$settings = getSiteSettings() + homepageDefaults() + publicCopyDefaults();
$about = getAboutSection();
$offerings = getOfferings(true);
$gallery = getGalleryItems();
$faqs = seoFaqs($settings);
function esc($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function webLink($url): string { return filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true) ? $url : '#location'; }
function slugify(string $value): string { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-') ?: 'venue'; }
function jsonList($json): array { $list = json_decode((string)$json, true); return is_array($list) ? array_values(array_filter($list, static fn($value) => is_string($value) && trim($value) !== '')) : []; }
$wa = buildWhatsAppUrl($settings['whatsapp_number'] ?? '', $settings['whatsapp_default_msg'] ?? 'Hello, I would like to enquire about availability at Charles Gala Venue.');
$call = buildCallUrl($settings['phone_primary'] ?? '');
$poolImage = 'assets/images/resort/pool-building.jpg';
foreach ($offerings as $item) { if (($item['category'] ?? '') === 'pool') { $poolImage = publicImage($item['image_path']); break; } }
$videoId = backgroundYoutubeId($settings['hero_video_url'] ?? 'https://www.youtube.com/watch?v=zVg8op029Mg');
$highlights = jsonList($about['highlights_json'] ?? '[]');
// Headline figures for the hero. Numbers are read from the capacity labels so the admin can change them.
$figures = [];
foreach ($offerings as $item) {
    if (preg_match('/\b(\d[\d,]*)\s*(?:persons?|guests?|people|seats?)\b/i', $item['capacity_label'] ?? '', $m)) $figures[] = ['value' => (int)str_replace(',', '', $m[1]), 'label' => $item['title']];
}
$figures = array_slice($figures, 0, 2);
$figures[] = ['value' => count($offerings), 'label' => $settings['copy_spaces_count_label']];
$categories = [];
foreach ($gallery as $g) { $cat = trim($g['category'] ?? '') ?: 'Venue'; $categories[slugify($cat)] = $cat; }
$photoCount = count(array_filter($gallery, static fn($g) => ($g['media_type'] ?? 'photo') !== 'youtube'));
$occasions = contentLines($settings['copy_occasion_options']);
$guestOptions = contentLines($settings['copy_guest_options']);
?>
<!doctype html><html lang="en-IN"><head>
<?php include __DIR__ . '/includes/seo_head.php'; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/venue.css?v=3.2">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
<script src="assets/js/venue.js?v=3.3" defer></script>
</head><body>
<script id="venue-copy" type="application/json"><?= json_encode(array_merge(array_intersect_key($settings, publicCopyDefaults()), ['site_name'=>$settings['site_name']]), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<div class="loader" aria-hidden="true"><div class="loader-inner"><img src="assets/images/logo.png" width="56" height="56" alt=""><span class="loader-name"><?= esc($settings['site_name']) ?></span><span class="loader-count">0</span><i class="loader-line"></i></div></div>
<div class="cursor" aria-hidden="true"><span class="cursor-dot"></span><span class="cursor-ring"><em></em></span></div>
<a class="skip" href="#main"><?= esc($settings['copy_skip_label']) ?></a><div class="progress" aria-hidden="true"></div><div class="grain" aria-hidden="true"></div>
<header><div class="wrap bar"><a class="brand" href="#home" aria-label="<?= esc($settings['site_name'].' '.$settings['copy_home_label']) ?>"><img src="assets/images/logo.png" width="42" height="42" alt=""><span><?= esc($settings['site_name']) ?><small><?= esc($settings['copy_brand_location']) ?></small></span></a>
<button class="menu" aria-expanded="false" aria-controls="navigation" type="button"><span class="menu-label"><?= esc($settings['copy_menu_open']) ?></span><span class="menu-icon"><i></i><i></i></span></button>
<nav id="navigation" aria-label="<?= esc($settings['copy_nav_aria']) ?>"><div class="nav-links"><a href="#spaces" data-i="01"><?= esc($settings['copy_nav_spaces']) ?></a><a href="#pool" data-i="02"><?= esc($settings['copy_nav_pool']) ?></a><a href="#about" data-i="03"><?= esc($settings['copy_nav_about']) ?></a><a href="#gallery" data-i="04"><?= esc($settings['copy_nav_gallery']) ?></a><a href="#location" data-i="05"><?= esc($settings['copy_nav_location']) ?></a></div><a class="btn solid" href="<?= esc($wa) ?>" target="_blank" rel="noopener" data-magnetic><?= esc($settings['copy_nav_cta']) ?></a><p class="nav-foot"><?= esc($settings['address']) ?></p></nav></div></header>
<main id="main">
<section id="home" class="hero">
<figure class="photo"><img src="<?= esc(publicImage($settings['hero_image_path'])) ?>" alt="<?= esc($settings['copy_hero_image_alt']) ?>" width="1600" height="1000" fetchpriority="high" data-par="hero"><div class="hero-video" data-video="<?= esc($videoId) ?>" aria-hidden="true"></div></figure>
<canvas class="dust" aria-hidden="true"></canvas>
<div class="wrap hero-grid"><div class="hero-copy"><p class="eyebrow"><i class="rule-sm"></i><?= esc($settings['hero_eyebrow']) ?></p>
<h1><?php $words = preg_split('/\s+/', trim($settings['hero_heading'])); foreach ($words as $i=>$word): ?><span class="w"><span style="--i:<?= $i ?>"<?= $i === count($words)-1 ? ' class="gold-text"' : '' ?>><?= esc($word) ?></span></span> <?php endforeach; ?></h1>
<p class="lead"><?= esc($settings['hero_description']) ?></p>
<div class="cta"><a class="btn solid" href="<?= esc($wa) ?>" target="_blank" rel="noopener" data-magnetic><?= esc($settings['copy_hero_cta']) ?></a><a class="btn ghost" href="#spaces" data-magnetic><?= esc($settings['copy_hero_secondary_cta']) ?></a></div></div>
<ul class="stats" aria-label="<?= esc($settings['copy_stats_label']) ?>"><?php foreach ($figures as $f): ?><li><strong><span data-count="<?= (int)$f['value'] ?>"><?= number_format((int)$f['value']) ?></span></strong><span><?= esc($f['label']) ?></span></li><?php endforeach; ?><li><strong class="word"><?= esc($settings['copy_pool_stat']) ?></strong><span><?= esc($settings['copy_pool_stat_note']) ?></span></li></ul></div>
<a class="scroll-cue" href="#spaces" aria-label="<?= esc($settings['copy_scroll_aria']) ?>"><span><?= esc($settings['copy_scroll_label']) ?></span><i></i></a>
<button type="button" class="motion-toggle" hidden aria-pressed="false"><?= esc($settings['copy_motion_pause']) ?></button></section>
<div class="band" aria-hidden="true"><div class="track"><?php for($n=0;$n<2;$n++): ?><span><?= esc($settings['copy_marquee_text']) ?> <b>✦</b> <?php foreach($offerings as $item): ?><?= esc($item['title']) ?> <b>✦</b> <?php endforeach; ?></span><?php endfor; ?></div></div>
<section id="spaces" class="spaces"><div class="wrap"><div class="head" data-reveal><div class="rule"></div><p class="kicker"><?= esc($settings['copy_spaces_kicker']) ?></p><h2 data-split><?= esc($settings['copy_spaces_heading']) ?></h2><p><?= esc($settings['copy_spaces_description']) ?></p></div>
<?php foreach ($offerings as $n=>$item): $features = jsonList($item['features_json'] ?? '[]'); ?>
<article class="space" id="<?= esc($item['slug']) ?>" data-reveal><figure class="photo arch"><img src="<?= esc(publicImage($item['image_path'])) ?>" alt="<?= esc($item['title'] . ' · ' . $settings['site_name']) ?>" width="800" height="1000" loading="lazy" decoding="async" data-par="photo"></figure>
<div class="space-copy"><span class="num"><?= str_pad((string)($n+1), 2, '0', STR_PAD_LEFT) ?></span><p class="kind"><?= esc($item['badge']) ?></p><h3><?= esc($item['title']) ?></h3><p><?= esc($item['short_desc']) ?></p>
<?php if ($features): ?><ul class="features"><?php foreach ($features as $feature): ?><li><?= esc($feature) ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php if (!empty($item['capacity_label'])): ?><span class="cap"><?= esc($item['capacity_label']) ?></span><?php endif; ?>
<?php if (isset(bookingRoutes()[$item['slug']])): ?>
<p><a class="link" href="<?= esc(bookingRoutes()[$item['slug']]) ?>" aria-label="<?= esc($settings['copy_space_details'].' · '.$item['title']) ?>"><?= esc($settings['copy_space_details']) ?> <i aria-hidden="true">→</i></a></p>
<?php endif; ?>
<a class="link" href="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'], $item['whatsapp_custom_msg'] ?: 'Hello, I would like to enquire about ' . $item['title'] . '.')) ?>" target="_blank" rel="noopener"><?= esc($settings['copy_space_cta']) ?><i>↗</i></a></div></article>
<?php endforeach; ?></div></section>
<section id="pool" class="pool"><figure class="photo"><img src="<?= esc($poolImage) ?>" alt="<?= esc($settings['copy_pool_image_alt']) ?>" width="1600" height="1000" loading="lazy" decoding="async" data-par="pool"></figure><div class="wrap" data-reveal><p class="kicker light"><?= esc($settings['copy_pool_kicker']) ?></p><h2 data-split><?= esc($settings['pool_heading']) ?></h2><p><?= esc($settings['pool_description']) ?></p><a class="btn solid" href="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'], $settings['copy_pool_message'])) ?>" target="_blank" rel="noopener" data-magnetic><?= esc($settings['copy_pool_cta']) ?></a></div></section>
<section id="about" class="welcome"><div class="wrap" data-reveal><div><div class="rule"></div><p class="kicker"><?= esc($settings['copy_about_kicker']) ?></p><h2 data-split><?= esc($about['headline'] ?? 'Your people. Your occasion. Our place.') ?></h2></div><div><p class="lead"><?= esc($about['subheadline'] ?? '') ?></p><div class="about-copy"><?= $about['content_html'] ?? '' ?></div>
<?php if ($highlights): ?><ul class="highlights"><?php foreach ($highlights as $h): ?><li><?= esc($h) ?></li><?php endforeach; ?></ul><?php endif; ?></div></div></section>
<section id="gallery" class="gallery"><div class="wrap head-row" data-reveal><div class="head"><div class="rule"></div><p class="kicker"><?= esc($settings['copy_gallery_kicker']) ?> · <?= (int)$photoCount ?> <?= esc($settings['copy_photo_count_label']) ?></p><h2 data-split><?= esc($settings['copy_gallery_heading']) ?></h2><p><?= esc($settings['copy_gallery_description']) ?></p></div>
<?php if (count($categories) > 1): ?><div class="filters" role="group" aria-label="<?= esc($settings['copy_gallery_filter_label']) ?>"><button type="button" class="on" data-filter="all" aria-pressed="true"><?= esc($settings['copy_gallery_all']) ?></button><?php foreach ($categories as $slug=>$label): ?><button type="button" data-filter="<?= esc($slug) ?>" aria-pressed="false"><?= esc($label) ?></button><?php endforeach; ?></div><?php endif; ?></div>
<div class="rail" tabindex="0" role="region" aria-label="<?= esc($settings['copy_gallery_region_label']) ?>"><div class="track-h">
<?php foreach ($gallery as $i=>$g): $cat = slugify(trim($g['category'] ?? '') ?: 'Venue'); ?>
<?php if (($g['media_type'] ?? '') === 'youtube' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $g['youtube_id'] ?? '')): ?><a class="video-link" href="https://www.youtube.com/watch?v=<?= esc($g['youtube_id']) ?>" target="_blank" rel="noopener" data-cat="<?= esc($cat) ?>"><i class="play"></i><span><?= esc($settings['copy_gallery_video_label']) ?></span><strong><?= esc($g['title']) ?></strong></a>
<?php else: ?><a class="photo <?= $i%4===0 ? 'tall' : ($i%4===1 ? 'wide' : '') ?>" href="<?= esc(publicImage($g['image_path'] ?? '')) ?>" data-photo data-cat="<?= esc($cat) ?>" data-caption="<?= esc($g['title']) ?>"><img src="<?= esc(publicImage($g['image_path'] ?? '')) ?>" alt="<?= esc($g['title']) ?>" width="800" height="600" loading="lazy" decoding="async"><span class="caption"><small><?= esc($g['category'] ?? 'Venue') ?></small><?= esc($g['title']) ?></span></a><?php endif; endforeach; ?>
</div></div></section>
<?php if($faqs): ?><section id="faq" class="faq-section"><div class="wrap faq" data-reveal><div class="head"><div class="rule"></div><p class="kicker"><?= esc($settings['copy_faq_kicker']) ?></p><h2 data-split><?= esc($settings['copy_faq_heading']) ?></h2></div><div class="faq-list"><?php foreach($faqs as $faq): ?><details><summary><?= esc($faq['question']) ?><i></i></summary><div class="faq-body"><p><?= esc($faq['answer']) ?></p></div></details><?php endforeach; ?></div></div></section><?php endif; ?>
<section id="location" class="visit"><div class="wrap" data-reveal><div class="visit-copy"><div class="rule"></div><p class="kicker"><?= esc($settings['copy_visit_kicker']) ?></p><h2 data-split><?= esc($settings['copy_visit_heading']) ?></h2><p class="intro"><?= esc($settings['copy_visit_description']) ?></p>
<div class="cta"><a class="btn solid" href="<?= esc($wa) ?>" target="_blank" rel="noopener" data-magnetic><?= esc($settings['copy_visit_whatsapp']) ?></a><a class="btn ghost" href="<?= esc($call) ?>" data-magnetic><?= esc($settings['copy_visit_call']) ?></a></div>
<dl class="details"><div><dt><?= esc($settings['copy_address_label']) ?></dt><dd><address><?= esc($settings['address']) ?></address><?php if (!empty($settings['plus_code'])): ?><span class="plus"><?= esc($settings['plus_code']) ?></span><?php endif; ?></dd></div><div><dt><?= esc($settings['copy_email_label']) ?></dt><dd><a class="email" href="mailto:<?= esc($settings['email']) ?>"><?= esc($settings['email']) ?></a></dd></div><div><dt><?= esc($settings['copy_directions_label']) ?></dt><dd><a class="link" href="<?= esc(webLink($settings['google_maps_url'] ?? '')) ?>" target="_blank" rel="noopener"><?= esc($settings['copy_directions_cta']) ?><i>↗</i></a></dd></div></dl></div>
<form class="booking" data-wa="<?= esc(buildWhatsAppUrl($settings['whatsapp_number'] ?? '', '')) ?>" aria-labelledby="booking-title"><p class="kicker" id="booking-title"><?= esc($settings['copy_booking_kicker']) ?></p><h3><?= esc($settings['copy_booking_heading']) ?></h3>
<fieldset><legend><?= esc($settings['copy_occasion_label']) ?></legend><div class="chips"><?php foreach ($occasions as $k=>$o): ?><label><input type="radio" name="occasion" value="<?= esc($o) ?>"<?= $k===0 ? ' checked' : '' ?>><span><?= esc($o) ?></span></label><?php endforeach; ?></div></fieldset>
<div class="row"><label><?= esc($settings['copy_guests_label']) ?><select name="guests"><?php foreach($guestOptions as $i=>$option): ?><option<?= $i===min(2,count($guestOptions)-1) ? ' selected' : '' ?>><?= esc($option) ?></option><?php endforeach; ?></select></label><label><?= esc($settings['copy_date_label']) ?><input type="date" name="date"></label></div>
<a class="btn solid wa-compose" href="<?= esc($wa) ?>" target="_blank" rel="noopener"><?= esc($settings['copy_booking_cta']) ?></a><p class="note"><?= esc($settings['copy_booking_note']) ?></p></form></div>
<?php
$mapUrl = trim($settings['google_maps_embed'] ?? '');
$mapParts = parse_url($mapUrl);
$mapHost = strtolower($mapParts['host'] ?? '');
$mapPath = $mapParts['path'] ?? '';
$validMap = filter_var($mapUrl, FILTER_VALIDATE_URL)
    && ($mapParts['scheme'] ?? '') === 'https'
    && !isset($mapParts['user']) && !isset($mapParts['pass'])
    && (($mapHost === 'maps.google.com' && ($mapPath === '' || $mapPath === '/' || str_starts_with($mapPath, '/maps')))
        || (in_array($mapHost, ['google.com', 'www.google.com'], true) && preg_match('~^/maps/embed(?:/|$)~', $mapPath)));
if ($validMap): ?>
<div class="wrap map"><iframe src="<?= esc($mapUrl) ?>" title="<?= esc($settings['copy_map_title'].' '.$settings['site_name']) ?>" width="1200" height="450" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe><p class="map-directions"><a class="link" href="<?= esc(webLink($settings['google_maps_url'] ?? '')) ?>" target="_blank" rel="noopener"><?= esc($settings['copy_map_directions_cta']) ?></a></p></div>
<?php endif; ?></section>
</main>
<footer><div class="wrap"><a class="brand" href="#home"><?= esc($settings['site_name']) ?></a><nav aria-label="<?= esc($settings['copy_social_label']) ?>"><?php foreach (['instagram_url'=>$settings['copy_instagram_label'],'facebook_url'=>$settings['copy_facebook_label'],'youtube_url'=>$settings['copy_youtube_label']] as $key=>$label): if (!empty($settings[$key])): ?><a href="<?= esc(webLink($settings[$key])) ?>" target="_blank" rel="noopener"><?= esc($label) ?> ↗</a><?php endif; endforeach; ?></nav></div><div class="wrap credits"><span>© <?= date('Y') ?> <?= esc($settings['site_name']) ?></span><span><?= esc($settings['copy_footer_credit_label']) ?> <a href="<?= esc(webLink($settings['copy_footer_credit_url'])) ?>"><?= esc($settings['copy_footer_credit_name']) ?></a></span></div></footer>
<div class="dock" aria-label="<?= esc($settings['copy_dock_label']) ?>"><a href="<?= esc($wa) ?>" target="_blank" rel="noopener" class="dock-wa"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 12 12 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg><span><?= esc($settings['copy_dock_whatsapp']) ?></span></a><a href="<?= esc($call) ?>" class="dock-call"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.3 1Z"/></svg><span><?= esc($settings['copy_dock_call']) ?></span></a></div>
<dialog id="viewer" aria-label="<?= esc($settings['copy_viewer_label']) ?>"><button type="button" class="close" aria-label="<?= esc($settings['copy_viewer_close']) ?>"><?= esc($settings['copy_viewer_close']) ?></button><button type="button" class="prev" aria-label="<?= esc($settings['copy_viewer_previous']) ?>">←</button><button type="button" class="next" aria-label="<?= esc($settings['copy_viewer_next']) ?>">→</button><figure><img alt=""><figcaption><p></p><span class="counter"></span></figcaption></figure></dialog>
</body></html>
