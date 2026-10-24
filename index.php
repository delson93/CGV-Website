<?php
/**
 * Charles Gala Venue - Production MySQL Public Website
 * Location: Kulur, Melkoppala, Mangalore 575013
 * Privacy Rule: Raw WhatsApp & Phone numbers are NEVER shown as visible page text;
 * Call or WhatsApp message actions become visible ONLY when clicking the WhatsApp icon or Call icon.
 */
require_once __DIR__ . '/includes/functions.php';

$settings  = getSiteSettings() + homepageDefaults();
$about     = getAboutSection();
$offerings = getOfferings(true);
$gallery   = getGalleryItems();

$globalWaUrl = buildWhatsAppUrl($settings['whatsapp_number'], $settings['whatsapp_default_msg']);
$globalCallUrl = buildCallUrl($settings['phone_primary']);
$heroImage = publicImage($settings['hero_image_path']);
$poolImage = 'assets/images/placeholder.svg';
foreach ($offerings as $offering) {
    if (($offering['category'] ?? '') === 'pool') { $poolImage = publicImage($offering['image_path']); break; }
}
$aboutHighlights = json_decode($about['highlights_json'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/seo_head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/public.css?v=3">
</head>
<body class="public-site">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <!-- Top Bar (No raw phone number displayed — Call Icon & WhatsApp Icon trigger actions on click) -->
    <div class="top-bar">
        <div class="container top-bar-inner">
            <span><?= htmlspecialchars($settings['address']) ?></span>
            <div class="top-bar-links">
                <button type="button" class="icon-trigger-btn" onclick="openCallModal('Reservation Desk — Charles Gala Venue')" aria-label="Call Venue">
                    Call Venue
                </button>
                <button type="button" class="icon-trigger-btn wa-trigger" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'General Booking Inquiry')" aria-label="WhatsApp Message">
                    WhatsApp
                </button>
                <a href="mailto:<?= htmlspecialchars($settings['email']) ?>"><?= htmlspecialchars($settings['email']) ?></a>

            </div>
        </div>
    </div>

    <!-- Sticky Navigation Header -->
    <header class="site-header">
        <div class="container nav-container">
            <a href="#home" class="brand-logo">
                <img src="assets/images/logo.png" alt="Charles Gala Venue Golden Palm Monogram Logo" width="56" height="56">
                <div>
                    <span class="brand-title"><?= htmlspecialchars($settings['site_name']) ?></span>
                    <span class="brand-sub">KULUR • MELKOPPALA • MANGALORE</span>
                </div>
            </a>
            <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="main-navigation">Menu <span aria-hidden="true">☰</span></button>
            <nav class="main-nav" id="main-navigation" aria-label="Main navigation">
                <a href="#offerings">Our Spaces</a>
                <a href="#pool-spotlight">Pool Booking</a>
                <a href="#about">About Us</a>
                <a href="#gallery">Gallery</a>
                <a href="#location">Contact</a>
            </nav>
            <div style="display:flex;align-items:center;gap:10px;">
                <button type="button" class="btn-circle-call" onclick="openCallModal('Direct Header Call')" title="Call Charles Gala Venue" aria-label="Call Charles Gala Venue">
                    
                </button>
                <button type="button" class="btn btn-gold" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Direct Header Book Now')">
                    Book Now
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main id="main-content">
    <section id="home" class="hero-section">
        <img class="hero-background" src="<?= htmlspecialchars($heroImage) ?>" alt="" fetchpriority="high" width="1920" height="1080">
        <div class="container hero-content">
            <span class="hero-pill"><?= htmlspecialchars($settings['hero_eyebrow']) ?></span>
            <h1><?= htmlspecialchars($settings['hero_heading']) ?><br><span class="gold-text"><?= htmlspecialchars($settings['site_name']) ?></span></h1>
            <p class="hero-lead"><?= htmlspecialchars($settings['hero_description']) ?></p>
            <div class="hero-actions">
                <button type="button" class="btn btn-whatsapp-lg" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Hero Instant WhatsApp Booking')">
                    Enquire about your date ↗
                </button>
                <button type="button" class="btn btn-outline-gold" onclick="openCallModal('Hero Voice Call Inquiry')">
                    Speak to our team
                </button>
                <a href="#offerings" class="btn btn-outline-gold">Discover our spaces ↓</a>
            </div>
            <div class="hero-note"><span>CELEBRATE</span><span>UNWIND</span><span>STAY</span><span>KULUR, MANGALORE</span></div>
        </div>
    </section>

    <!-- Our Spaces Section -->
    <section id="offerings" class="section-pad">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">WHAT WE OFFER IN KULUR, MANGALORE</span>
                <h2>A setting for every occasion</h2>
                <p>From celebrations to quiet escapes, explore our spaces and speak with our team about availability, packages and your plans.</p>
            </div>

            <div class="offerings-grid">
                <?php foreach ($offerings as $item):
                    $features = json_decode($item['features_json'] ?? '[]', true) ?: [];
                ?>
                <article class="offering-card" id="<?= htmlspecialchars($item['slug']) ?>">
                    <div class="offering-img-wrap">
                        <img src="<?= htmlspecialchars(publicImage($item['image_path'])) ?>" alt="<?= htmlspecialchars($item['title'] . ' at ' . $settings['site_name']) ?>" loading="lazy" decoding="async" width="800" height="600" onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
                        <span class="offering-badge"><?= htmlspecialchars($item['badge']) ?></span>
                        <span class="capacity-pill"><?= htmlspecialchars($item['capacity_label']) ?></span>
                    </div>
                    <div class="offering-body">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['short_desc']) ?></p>
                        <ul class="feature-list">
                            <?php foreach ($features as $feat): ?>
                                <li>✓ <?= htmlspecialchars($feat) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="offering-footer" style="display:flex;gap:10px;">
                            <button type="button" class="btn btn-book-wa" style="flex:1;" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($item['whatsapp_custom_msg'])) ?>, <?= htmlspecialchars(json_encode($item['title'])) ?>)">
                                Enquire about this space ↗
                            </button>
                            <button type="button" class="btn-circle-call" onclick="openCallModal(<?= htmlspecialchars(json_encode($item['title'])) ?>)" title="Call for <?= htmlspecialchars($item['title']) ?>" aria-label="Call for <?= htmlspecialchars($item['title']) ?>">
                                
                            </button>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SEO Spotlight Banner: Pool for Anyone & Best Stay in Mangalore -->
    <section id="pool-spotlight" class="section-pad dark-emerald-bg">
        <div class="container spotlight-grid">
            <div>
                <span class="eyebrow gold-text">A LITTLE TIME TO UNWIND</span>
                <h2><?= htmlspecialchars($settings['pool_heading']) ?></h2>
                <p><?= htmlspecialchars($settings['pool_description']) ?></p>
                <div style="margin-top:1.5rem;display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-gold" onclick="openWhatsAppModal('Hello Charles Gala Venue! I want to book a Swimming Pool slot / Stay package. Please share rates.', 'Swimming Pool Pass Booking')">
                        Enquire about pool access ↗
                    </button>
                    <button type="button" class="btn btn-outline-gold" onclick="openCallModal('Swimming Pool & Stay Desk')">
                        Ask about timings
                    </button>
                </div>
            </div>
            <div class="spotlight-image-box">
                <img src="<?= htmlspecialchars($poolImage) ?>" alt="Swimming Pool for Anyone at Charles Gala Venue Mangalore" loading="lazy" decoding="async" width="800" height="600" onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="section-pad">
        <div class="container about-grid">
            <div class="about-img-col">
                <img src="<?= htmlspecialchars(publicImage($about['image_path'])) ?>" alt="<?= htmlspecialchars($about['headline']) ?>" loading="lazy" decoding="async" width="800" height="600" onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
            </div>
            <div class="about-text-col">
                <span class="eyebrow">ABOUT CHARLES GALA VENUE</span>
                <h2><?= htmlspecialchars($about['headline']) ?></h2>
                <p class="subheadline"><?= htmlspecialchars($about['subheadline']) ?></p>
                <div class="about-rich-text">
                    <?= $about['content_html'] ?>
                </div>
                <div class="highlights-grid">
                    <?php foreach ($aboutHighlights as $hl): ?>
                        <div class="highlight-chip"><?= htmlspecialchars($hl) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="section-pad bg-cream">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">VISUAL SHOWCASE</span>
                <h2>A glimpse of Charles Gala</h2>
                <p>Discover our venue, celebration spaces, pool and rooms. Contact our team to arrange a visit.</p>
            </div>
            <div class="gallery-grid">
                <?php foreach ($gallery as $g): ?>
                    <div class="gallery-card">
                        <?php if ($g['media_type'] === 'youtube' && !empty($g['youtube_id'])):
                            $autoplayParam = !empty($g['autoplay']) ? '1&mute=1' : '0';
                        ?>
                            <div class="video-container">
                                <iframe
                                    src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($g['youtube_id']) ?>?autoplay=<?= $autoplayParam ?>&rel=0"
                                    title="<?= htmlspecialchars($g['title']) ?>"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    loading="lazy" decoding="async" width="800" height="600" onerror="this.onerror=null;this.src='assets/images/placeholder.svg'"></iframe>
                            </div>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars(publicImage($g['image_path'])) ?>" alt="<?= htmlspecialchars($g['title']) ?>" loading="lazy" decoding="async" width="800" height="600" onerror="this.onerror=null;this.src='assets/images/placeholder.svg'">
                        <?php endif; ?>
                        <div class="gallery-caption">
                            <span class="gallery-cat"><?= htmlspecialchars($g['category']) ?> <?= $g['media_type'] === 'youtube' ? '• ▶ YouTube' : '' ?></span>
                            <h3><?= htmlspecialchars($g['title']) ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section (No raw phone/WhatsApp number shown in text!) -->
    <section id="location" class="section-pad">
        <div class="container contact-grid">
            <div class="contact-card">
                <span class="eyebrow">VISIT OR CONTACT US</span>
                <h2>Book Your Date at Charles Gala Venue</h2>
                <ul class="contact-details">
                    <li><strong>Address:</strong> <?= htmlspecialchars($settings['address']) ?></li>
                    <li><strong>Google Plus Code:</strong> <?= htmlspecialchars($settings['plus_code']) ?></li>
                    <li><strong>Official Email:</strong> <a href="mailto:<?= htmlspecialchars($settings['email']) ?>"><?= htmlspecialchars($settings['email']) ?></a></li>
                    <li>
                        <strong>Instant Booking & Voice Desk:</strong>
                        <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-whatsapp-lg" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Contact Section Inquiry')">
                                Click for WhatsApp Message
                            </button>
                            <button type="button" class="btn btn-gold" onclick="openCallModal('Contact Section Voice Call')">
                                Click to Call Venue
                            </button>
                        </div>
                    </li>
                </ul>
                <div class="social-links-row">
                    <?php if (!empty($settings['instagram_url'])): ?>
                        <a href="<?= htmlspecialchars($settings['instagram_url']) ?>" target="_blank" rel="noopener" class="social-btn">Instagram</a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= htmlspecialchars($settings['youtube_url']) ?>" target="_blank" rel="noopener" class="social-btn">YouTube</a>
                    <?php endif; ?>
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= htmlspecialchars($settings['facebook_url']) ?>" target="_blank" rel="noopener" class="social-btn">Facebook</a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($settings['google_maps_url']) ?>" target="_blank" rel="noopener" class="social-btn">Open in Google Maps ↗</a>
                </div>
            </div>
            <div class="map-embed-wrap">
                <iframe
                    src="<?= htmlspecialchars($settings['google_maps_embed']) ?>"
                    width="100%"
                    height="420"
                    style="border:0;border-radius:16px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Charles Gala Venue Location Map - Kulur, Melkoppala, Mangalore 575013"></iframe>
            </div>
        </div>
    </section>

    </main>
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                <strong><?= htmlspecialchars($settings['site_name']) ?></strong> — <?= htmlspecialchars($settings['address']) ?>
            </div>
            <div>
                © <?= date('Y') ?> <?= htmlspecialchars($settings['site_name']) ?><br>Designed and Developed by <a href="https://codeixe.com/" target="_blank" rel="noopener">CodeIXE AI Solutions</a>
            </div>
        </div>
    </footer>

    <!-- Persistent Floating Call & WhatsApp Icons (Numbers are NEVER shown on screen until clicked!) -->
    <div class="floating-action-stack">
        <button type="button" class="floating-call-btn" onclick="openCallModal('Floating Call Icon')" aria-label="Call Charles Gala Venue" title="Call Charles Gala Venue">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.61 21 3 13.39 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.01l-2.2 2.2z"/></svg>
        </button>
        <button type="button" class="floating-whatsapp" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Floating WhatsApp Icon')" aria-label="WhatsApp Message" title="Message on WhatsApp">
            <svg viewBox="0 0 32 32" width="30" height="30" fill="#ffffff"><path d="M16.004 3.2c-7.072 0-12.8 5.728-12.8 12.8 0 2.256.592 4.464 1.712 6.416L3.2 28.8l6.56-1.712c1.888 1.024 4.016 1.568 6.244 1.568 7.072 0 12.8-5.728 12.8-12.8s-5.728-12.656-12.8-12.656zm7.472 18.096c-.32.896-1.856 1.712-2.56 1.824-.656.096-1.488.144-2.4-.144-.56-.176-1.28-.416-2.192-.816-3.856-1.664-6.368-5.552-6.56-5.808-.192-.256-1.568-2.08-1.568-3.968s.992-2.816 1.344-3.2c.352-.384.768-.48 1.024-.48.256 0 .512.016.736.016.24.016.56-.096.88.672.32.768 1.088 2.656 1.184 2.848.096.192.16.416.032.672-.128.256-.192.416-.384.64-.192.224-.4.496-.576.672-.192.192-.384.4-.16.784.224.384.992 1.632 2.128 2.64 1.456 1.296 2.688 1.696 3.072 1.888.384.192.608.16.832-.096.224-.256.96-1.12 1.216-1.504.256-.384.512-.32.864-.192.352.128 2.24 1.056 2.624 1.248.384.192.64.288.736.448.096.16.096.928-.224 1.824z"/></svg>
        </button>
    </div>

    <!-- Modal 1: Visible ONLY when clicking on the WhatsApp icon / Book Now -->
    <div id="wa-action-modal" class="action-modal-backdrop" style="display:none;">
        <div class="action-modal-card" role="dialog" aria-modal="true" aria-label="Contact the venue" tabindex="-1">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <strong style="font-size:1.15rem;color:#061a14;">Send WhatsApp Booking Message</strong>
                <button type="button" onclick="closeActionModals()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;">✕</button>
            </div>
            <p id="wa-modal-context" style="font-size:0.85rem;color:#475569;margin-bottom:8px;"></p>
            <label for="wa-modal-msg" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#334155;">Your Pre-Filled WhatsApp Message:</label>
            <textarea id="wa-modal-msg" rows="3" style="width:100%;padding:10px;border-radius:10px;border:1px solid #cbd5e1;margin:8px 0 14px;font-family:inherit;"></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn" onclick="closeActionModals()" style="background:#e2e8f0;color:#1e293b;">Cancel</button>
                <a id="wa-modal-launch" href="#" target="_blank" rel="noopener" class="btn btn-whatsapp-lg" onclick="closeActionModals()">
                    Open WhatsApp & Send Message ↗
                </a>
            </div>
        </div>
    </div>

    <!-- Modal 2: Visible ONLY when clicking on the Call icon -->
    <div id="call-action-modal" class="action-modal-backdrop" style="display:none;">
        <div class="action-modal-card" role="dialog" aria-modal="true" aria-label="Contact the venue" tabindex="-1">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <strong style="font-size:1.15rem;color:#061a14;">Call Charles Gala Venue</strong>
                <button type="button" onclick="closeActionModals()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;">✕</button>
            </div>
            <p id="call-modal-context" style="font-size:0.9rem;color:#334155;margin-bottom:14px;"></p>
            <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:16px;text-align:center;">
                <div style="font-size:0.75rem;text-transform:uppercase;color:#64748b;font-weight:700;">Direct Reservation Line</div>
                <div id="call-modal-number" style="font-size:1.35rem;font-weight:800;color:#061a14;margin-top:4px;"></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn" onclick="closeActionModals()" style="background:#e2e8f0;color:#1e293b;">Close</button>
                <a id="call-modal-dial" href="#" class="btn btn-gold">Dial Now</a>
            </div>
        </div>
    </div>

    <dialog id="photo-viewer"><button type="button" class="photo-close" aria-label="Close photograph">Close ×</button><img alt=""><p></p></dialog>
    <script src="assets/js/immersive.js" defer></script>
    <script>
    const _WA_ENDPOINT = <?= json_encode(preg_replace('/[^0-9]/', '', $settings['whatsapp_number']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const _CALL_NUM = <?= json_encode($settings['phone_primary'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function openWhatsAppModal(defaultMsg, contextLabel) {
        const modal = document.getElementById('wa-action-modal');
        const msgBox = document.getElementById('wa-modal-msg');
        const launch = document.getElementById('wa-modal-launch');
        document.getElementById('wa-modal-context').textContent = 'Inquiry: ' + (contextLabel || 'Charles Gala Venue Booking');
        msgBox.value = defaultMsg || 'Hello Charles Gala Venue! I would like to inquire about booking and availability.';
        const updateHref = () => {
            const clean = _WA_ENDPOINT.length === 10 ? '91' + _WA_ENDPOINT : _WA_ENDPOINT;
            launch.href = 'https://wa.me/' + clean + '?text=' + encodeURIComponent(msgBox.value);
        };
        msgBox.oninput = updateHref;
        updateHref();
        showContactDialog(modal);
    }

    function openCallModal(contextLabel) {
        const modal = document.getElementById('call-action-modal');
        document.getElementById('call-modal-context').textContent = 'Connecting you with Charles Gala Venue (' + (contextLabel || 'Booking Desk') + ')';
        const digits = _CALL_NUM.replace(/[^0-9]/g, '');
        const dialNumber = '+' + (digits.length === 10 ? '91' + digits : digits);
        const formatted = dialNumber;
        document.getElementById('call-modal-number').textContent = formatted;
        document.getElementById('call-modal-dial').href = 'tel:' + dialNumber;
        showContactDialog(modal);
    }

    let previousFocus;
    function showContactDialog(modal) {
        previousFocus = document.activeElement;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        modal.querySelector('.action-modal-card').focus();
    }
    function closeActionModals() {
        document.body.style.overflow = '';
        if (previousFocus) previousFocus.focus();
        document.getElementById('wa-action-modal').style.display = 'none';
        document.getElementById('call-action-modal').style.display = 'none';
    }
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.main-nav');
    toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(expanded));
        navigation.classList.toggle('is-open', expanded);
    });
    navigation.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        toggle.setAttribute('aria-expanded', 'false'); navigation.classList.remove('is-open');
    }));
    document.querySelectorAll('.action-modal-backdrop').forEach(modal => modal.addEventListener('click', event => {
        if (event.target === modal) closeActionModals();
    }));
    document.addEventListener('keydown', event => {
        const modal = [...document.querySelectorAll('.action-modal-backdrop')].find(el => el.style.display === 'flex');
        if (!modal) return;
        if (event.key === 'Escape') closeActionModals();
        if (event.key === 'Tab') {
            const focusable = [...modal.querySelectorAll('button, a[href], textarea')];
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === modal.querySelector('.action-modal-card'))) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    </script>
</body>
</html>

