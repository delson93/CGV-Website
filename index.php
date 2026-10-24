<?php
/**
 * Charles Gala Venue - Production MySQL Public Website
 * Location: Kulur, Melkoppala, Mangalore 575013
 * Privacy Rule: Raw WhatsApp & Phone numbers are NEVER shown as visible page text;
 * Call or WhatsApp message actions become visible ONLY when clicking the WhatsApp icon or Call icon.
 */
require_once __DIR__ . '/includes/functions.php';

$settings  = getSiteSettings();
$about     = getAboutSection();
$offerings = getOfferings(true);
$gallery   = getGalleryItems();

$globalWaUrl = buildWhatsAppUrl($settings['whatsapp_number'], $settings['whatsapp_default_msg']);
$globalCallUrl = buildCallUrl($settings['phone_primary']);
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
</head>
<body>
    <!-- Top Bar (No raw phone number displayed — Call Icon & WhatsApp Icon trigger actions on click) -->
    <div class="top-bar">
        <div class="container top-bar-inner">
            <span>📍 Kulur, Melkoppala, Mangalore 575013 (<?= htmlspecialchars($settings['plus_code']) ?>)</span>
            <div class="top-bar-links">
                <button type="button" class="icon-trigger-btn" onclick="openCallModal('Reservation Desk — Charles Gala Venue')" aria-label="Call Venue">
                    📞 Call Venue
                </button>
                <button type="button" class="icon-trigger-btn wa-trigger" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'General Booking Inquiry')" aria-label="WhatsApp Message">
                    💬 WhatsApp
                </button>
                <a href="mailto:<?= htmlspecialchars($settings['email']) ?>">✉️ <?= htmlspecialchars($settings['email']) ?></a>
                <a href="admin/login.php" class="admin-link">🔒 Admin Login</a>
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
            <nav class="main-nav">
                <a href="#offerings">What We Offer</a>
                <a href="#pool-spotlight">Pool Booking</a>
                <a href="#about">About Us</a>
                <a href="#gallery">Gallery & Video</a>
                <a href="#location">Map & Contact</a>
            </nav>
            <div style="display:flex;align-items:center;gap:10px;">
                <button type="button" class="btn-circle-call" onclick="openCallModal('Direct Header Call')" title="Call Charles Gala Venue" aria-label="Call Charles Gala Venue">
                    📞
                </button>
                <button type="button" class="btn btn-gold" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Direct Header Book Now')">
                    💬 Book Now
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="hero-section" style="background-image: linear-gradient(135deg, rgba(8,20,16,0.88) 0%, rgba(12,28,22,0.72) 100%), url('<?= htmlspecialchars($about['image_path']) ?>');">
        <div class="container hero-content">
            <span class="hero-pill">✨ Mangalore's Premier Gala Venue, Stay & Public Pool</span>
            <h1>Celebrate Grandly at <span class="gold-text"><?= htmlspecialchars($settings['site_name']) ?></span></h1>
            <p class="hero-lead">
                Experience the <strong>best stay in Mangalore</strong>, our <strong>1,500-person Open Ground Hall</strong>, <strong>250-person AC Mini Hall</strong>, bespoke <strong>Decorations & Lightings</strong>, and crystal-clear <strong>Swimming Pool open for anyone</strong> in Kulur, Melkoppala (575013).
            </p>
            <div class="hero-actions">
                <button type="button" class="btn btn-whatsapp-lg" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Hero Instant WhatsApp Booking')">
                    💬 Book Instantly on WhatsApp
                </button>
                <button type="button" class="btn btn-outline-gold" onclick="openCallModal('Hero Voice Call Inquiry')">
                    📞 Call Venue Desk
                </button>
                <a href="#offerings" class="btn btn-outline-gold">Explore All 5 Offerings ↓</a>
            </div>
            <div class="hero-stats">
                <div class="stat-card"><strong>1,500</strong><span>Open Ground Crowd</span></div>
                <div class="stat-card"><strong>250</strong><span>AC Mini Hall Guests</span></div>
                <div class="stat-card"><strong>Open to All</strong><span>Pool Booking Passes</span></div>
                <div class="stat-card"><strong>24/7</strong><span>Luxury Stay & Decor</span></div>
            </div>
        </div>
    </section>

    <!-- What We Offer Section -->
    <section id="offerings" class="section-pad">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">WHAT WE OFFER IN KULUR, MANGALORE</span>
                <h2>Tailored Spaces for Grand Celebrations, Stays & Pool Leisure</h2>
                <p>Select any offering below and click the <strong>WhatsApp icon</strong> or <strong>Call icon</strong> to connect with our reservation team.</p>
            </div>

            <div class="offerings-grid">
                <?php foreach ($offerings as $item):
                    $features = json_decode($item['features_json'] ?? '[]', true) ?: [];
                ?>
                <article class="offering-card" id="<?= htmlspecialchars($item['slug']) ?>">
                    <div class="offering-img-wrap">
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title'] . ' - ' . $item['seo_focus_keyword']) ?>" loading="lazy">
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
                                💬 Book Now on WhatsApp →
                            </button>
                            <button type="button" class="btn-circle-call" onclick="openCallModal(<?= htmlspecialchars(json_encode($item['title'])) ?>)" title="Call for <?= htmlspecialchars($item['title']) ?>" aria-label="Call for <?= htmlspecialchars($item['title']) ?>">
                                📞
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
                <span class="eyebrow gold-text">PUBLIC & PRIVATE POOL BOOKING • KULUR MANGALORE</span>
                <h2>Swimming Pool Open for Anyone & Best Resort Stay</h2>
                <p>Looking for a refreshing <strong>pool for anyone in Mangalore</strong>? You don't need to book a full wedding hall to enjoy our tropical swimming pool! We welcome families, friend groups, fitness swimmers, and weekend day-out guests with flexible hourly passes and private pool party bookings.</p>
                <div class="keyword-tags">
                    <span>#BestStayMangalore</span>
                    <span>#OpenGroundHall1500</span>
                    <span>#PoolForAnyone</span>
                    <span>#PoolBookingKulur</span>
                    <span>#MiniHall250</span>
                </div>
                <div style="margin-top:1.5rem;display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-gold" onclick="openWhatsAppModal('Hello Charles Gala Venue! I want to book a Swimming Pool slot / Stay package. Please share rates.', 'Swimming Pool Pass Booking')">
                        🏊 Book Pool Pass on WhatsApp
                    </button>
                    <button type="button" class="btn btn-outline-gold" onclick="openCallModal('Swimming Pool & Stay Desk')">
                        📞 Call for Pool Slot
                    </button>
                </div>
            </div>
            <div class="spotlight-image-box">
                <img src="uploads/offerings/pool.jpg" alt="Swimming Pool for Anyone at Charles Gala Venue Mangalore" loading="lazy">
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="section-pad">
        <div class="container about-grid">
            <div class="about-img-col">
                <img src="<?= htmlspecialchars($about['image_path']) ?>" alt="<?= htmlspecialchars($about['headline']) ?>" loading="lazy">
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
                        <div class="highlight-chip">🌴 <?= htmlspecialchars($hl) ?></div>
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
                <h2>Photo & YouTube Video Gallery</h2>
                <p>Explore real moments across our 1,500-capacity Open Ground Hall, 250-capacity Mini Hall, Swimming Pool, and Luxury Rooms.</p>
            </div>
            <div class="gallery-grid">
                <?php foreach ($gallery as $g): ?>
                    <div class="gallery-card">
                        <?php if ($g['media_type'] === 'youtube' && !empty($g['youtube_id'])):
                            $autoplayParam = !empty($g['autoplay']) ? '1&mute=1' : '0';
                        ?>
                            <div class="video-container">
                                <iframe
                                    src="https://www.youtube.com/embed/<?= htmlspecialchars($g['youtube_id']) ?>?autoplay=<?= $autoplayParam ?>&rel=0"
                                    title="<?= htmlspecialchars($g['title']) ?>"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    loading="lazy"></iframe>
                            </div>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($g['image_path']) ?>" alt="<?= htmlspecialchars($g['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="gallery-caption">
                            <span class="gallery-cat"><?= htmlspecialchars($g['category']) ?> <?= $g['media_type'] === 'youtube' ? '• ▶ YouTube' : '' ?></span>
                            <h4><?= htmlspecialchars($g['title']) ?></h4>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Map & Contact Section (No raw phone/WhatsApp number shown in text!) -->
    <section id="location" class="section-pad">
        <div class="container contact-grid">
            <div class="contact-card">
                <span class="eyebrow">VISIT OR CONTACT US</span>
                <h2>Book Your Date at Charles Gala Venue</h2>
                <ul class="contact-details">
                    <li><strong>📍 Address:</strong> <?= htmlspecialchars($settings['address']) ?></li>
                    <li><strong>🧭 Google Plus Code:</strong> <?= htmlspecialchars($settings['plus_code']) ?></li>
                    <li><strong>✉️ Official Email:</strong> <a href="mailto:<?= htmlspecialchars($settings['email']) ?>"><?= htmlspecialchars($settings['email']) ?></a></li>
                    <li>
                        <strong>💬 Instant Booking & Voice Desk:</strong>
                        <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-whatsapp-lg" onclick="openWhatsAppModal(<?= htmlspecialchars(json_encode($settings['whatsapp_default_msg'])) ?>, 'Contact Section Inquiry')">
                                💬 Click for WhatsApp Message
                            </button>
                            <button type="button" class="btn btn-gold" onclick="openCallModal('Contact Section Voice Call')">
                                📞 Click to Call Venue
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

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                <strong><?= htmlspecialchars($settings['site_name']) ?></strong> — <?= htmlspecialchars($settings['address']) ?>
            </div>
            <div>
                <a href="sitemap.php">XML Sitemap</a> • <a href="admin/login.php">🔒 Admin CMS Login</a>
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
        <div class="action-modal-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <strong style="font-size:1.15rem;color:#061a14;">💬 Send WhatsApp Booking Message</strong>
                <button type="button" onclick="closeActionModals()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;">✕</button>
            </div>
            <p id="wa-modal-context" style="font-size:0.85rem;color:#475569;margin-bottom:8px;"></p>
            <label style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#334155;">Your Pre-Filled WhatsApp Message:</label>
            <textarea id="wa-modal-msg" rows="3" style="width:100%;padding:10px;border-radius:10px;border:1px solid #cbd5e1;margin:8px 0 14px;font-family:inherit;"></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn" onclick="closeActionModals()" style="background:#e2e8f0;color:#1e293b;">Cancel</button>
                <a id="wa-modal-launch" href="#" target="_blank" rel="noopener" class="btn btn-whatsapp-lg" onclick="closeActionModals()">
                    💬 Open WhatsApp & Send Message ↗
                </a>
            </div>
        </div>
    </div>

    <!-- Modal 2: Visible ONLY when clicking on the Call icon -->
    <div id="call-action-modal" class="action-modal-backdrop" style="display:none;">
        <div class="action-modal-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <strong style="font-size:1.15rem;color:#061a14;">📞 Call Charles Gala Venue</strong>
                <button type="button" onclick="closeActionModals()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;">✕</button>
            </div>
            <p id="call-modal-context" style="font-size:0.9rem;color:#334155;margin-bottom:14px;"></p>
            <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:16px;text-align:center;">
                <div style="font-size:0.75rem;text-transform:uppercase;color:#64748b;font-weight:700;">Direct Reservation Line</div>
                <div id="call-modal-number" style="font-size:1.35rem;font-weight:800;color:#061a14;margin-top:4px;"></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn" onclick="closeActionModals()" style="background:#e2e8f0;color:#1e293b;">Close</button>
                <a id="call-modal-dial" href="#" class="btn btn-gold">📞 Dial Now</a>
            </div>
        </div>
    </div>

    <script>
    const _WA_ENDPOINT = <?= json_encode(preg_replace('/[^0-9]/', '', $settings['whatsapp_number'])) ?>;
    const _CALL_NUM = <?= json_encode($settings['phone_primary']) ?>;

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
        modal.style.display = 'flex';
    }

    function openCallModal(contextLabel) {
        const modal = document.getElementById('call-action-modal');
        document.getElementById('call-modal-context').textContent = 'Connecting you with Charles Gala Venue (' + (contextLabel || 'Booking Desk') + ')';
        const formatted = '+91 ' + _CALL_NUM.replace(/^91/, '');
        document.getElementById('call-modal-number').textContent = formatted;
        document.getElementById('call-modal-dial').href = 'tel:+91' + _CALL_NUM.replace(/^91/, '');
        modal.style.display = 'flex';
    }

    function closeActionModals() {
        document.getElementById('wa-action-modal').style.display = 'none';
        document.getElementById('call-action-modal').style.display = 'none';
    }
    </script>
</body>
</html>
