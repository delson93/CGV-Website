<?php
/**
 * Charles Gala Venue - Production MySQL Admin Dashboard (`admin/index.php`)
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdminAuth();

$pdo = getDB();
$flash = '';
$errorMsg = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid CSRF security token.';
    } else {
        $action = $_POST['action'] ?? '';

        // A. Update Contact, WhatsApp & Social Media Settings in MySQL
        if ($action === 'save_settings') {
            $keys = ['site_name', 'phone_primary', 'whatsapp_number', 'whatsapp_default_msg', 'email', 'address', 'plus_code', 'instagram_url', 'youtube_url', 'facebook_url', 'google_maps_url', 'google_maps_embed', 'latitude', 'longitude'];
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    updateSiteSetting($k, trim($_POST[$k]));
                }
            }
            $flash = 'WhatsApp backend contact, Call number, and Social Media links saved to MySQL!';
        }

        if ($action === 'save_homepage') {
            $videoUrl = trim((string)($_POST['hero_video_url'] ?? ''));
            if ($videoUrl !== '' && backgroundYoutubeId($videoUrl) === '') {
                $errorMsg = 'Enter a valid YouTube video URL, or leave it blank to use the background image.';
            } else {
            foreach (homepageDefaults() as $key => $default) {
                if ($key !== 'hero_image_path') updateSiteSetting($key, trim((string)($_POST[$key] ?? $default)));
            }
            $image = handleImageUpload($_FILES['hero_image'] ?? [], 'about');
            if ($image) updateSiteSetting('hero_image_path', $image);
            if (isset($_POST['reset_hero'])) updateSiteSetting('hero_image_path', 'assets/images/placeholder.svg');
            $flash = 'Homepage content and background saved.';
            }
        }

        // B. Update SEO Keywords & Gemini API Key in MySQL
        if ($action === 'save_seo') {
            $seoKeys = ['seo_meta_title', 'seo_meta_description', 'seo_keywords', 'seo_canonical_url', 'gemini_api_key', 'gemini_model'];
            foreach ($seoKeys as $k) {
                if (isset($_POST[$k])) {
                    updateSiteSetting($k, trim($_POST[$k]));
                }
            }
            $flash = 'SEO Meta Tags, Target Keywords, and Gemini AI configuration saved to MySQL!';
        }

        // C. Update About Us Image & Details in MySQL
        if ($action === 'save_about') {
            $uploadedImg = handleImageUpload($_FILES['about_image'] ?? [], 'about');
            $imgPath = $uploadedImg ?: ($_POST['existing_image'] ?? 'uploads/about/hero.jpg');
            $stmt = $pdo->prepare("UPDATE about_section SET headline = :h, subheadline = :s, content_html = :c, image_path = :i WHERE id = 1");
            $stmt->execute([
                ':h' => $_POST['headline'] ?? '',
                ':s' => $_POST['subheadline'] ?? '',
                ':c' => $_POST['content_html'] ?? '',
                ':i' => $imgPath
            ]);
            $flash = 'About Us section and photo updated in MySQL!';
        }

        // D. Update or Add Offering Photo & Details in MySQL
        if ($action === 'save_offering') {
            $id = (int)($_POST['offering_id'] ?? 0);
            $uploadedImg = handleImageUpload($_FILES['offering_image'] ?? [], 'offerings');
            $imgPath = $uploadedImg ?: ($_POST['existing_image'] ?? 'uploads/offerings/open_ground.jpg');
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE offerings SET title=:t, badge=:b, capacity_label=:cap, short_desc=:d, seo_focus_keyword=:kw, whatsapp_custom_msg=:wa, image_path=:img WHERE id=:id");
                $stmt->execute([
                    ':t'   => $_POST['title'],
                    ':b'   => $_POST['badge'],
                    ':cap' => $_POST['capacity_label'],
                    ':d'   => $_POST['short_desc'],
                    ':kw'  => $_POST['seo_focus_keyword'],
                    ':wa'  => $_POST['whatsapp_custom_msg'],
                    ':img' => $imgPath,
                    ':id'  => $id
                ]);
                $flash = 'Offering updated in MySQL!';
            } else {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['title']))) . '-' . time();
                $stmt = $pdo->prepare("INSERT INTO offerings (slug, category, title, badge, capacity_label, short_desc, features_json, seo_focus_keyword, whatsapp_custom_msg, image_path, sort_order) VALUES (:slug, :cat, :t, :b, :cap, :d, :f, :kw, :wa, :img, 10)");
                $stmt->execute([
                    ':slug' => $slug,
                    ':cat'  => $_POST['category'] ?? 'halls',
                    ':t'    => $_POST['title'],
                    ':b'    => $_POST['badge'] ?: 'Featured Offering',
                    ':cap'  => $_POST['capacity_label'] ?: 'Custom Capacity',
                    ':d'    => $_POST['short_desc'],
                    ':f'    => json_encode(['Custom Package Available', 'Instant WhatsApp Booking', 'In-House Support Team']),
                    ':kw'   => $_POST['seo_focus_keyword'] ?: strtolower($_POST['title']) . ' Mangalore',
                    ':wa'   => $_POST['whatsapp_custom_msg'],
                    ':img'  => $imgPath
                ]);
                $flash = 'New Offering inserted into MySQL `offerings` table!';
            }
        }

        // E. Add Gallery Photo or YouTube Video with Autoplay Option in MySQL
        if ($action === 'add_gallery') {
            $mediaType = $_POST['media_type'] === 'youtube' ? 'youtube' : 'photo';
            $title = trim($_POST['title'] ?? 'Charles Gala Venue Moment');
            $category = trim($_POST['category'] ?? 'Venue');
            $autoplay = isset($_POST['autoplay']) ? 1 : 0;
            $ytUrl = trim($_POST['youtube_url'] ?? '');
            $ytId = $mediaType === 'youtube' ? extractYoutubeId($ytUrl) : null;
            $uploadedImg = handleImageUpload($_FILES['gallery_image'] ?? [], 'gallery');
            $imgPath = $uploadedImg ?: 'uploads/gallery/hero.jpg';

            $stmt = $pdo->prepare("INSERT INTO gallery_items (media_type, title, category, image_path, youtube_url, youtube_id, autoplay) VALUES (:m, :t, :c, :i, :yu, :yi, :ap)");
            $stmt->execute([
                ':m'  => $mediaType,
                ':t'  => $title,
                ':c'  => $category,
                ':i'  => $imgPath,
                ':yu' => $ytUrl,
                ':yi' => $ytId,
                ':ap' => $autoplay
            ]);
            $flash = 'New Gallery item inserted into MySQL `gallery_items` table!';
        }

        // F. Change Admin Username & Password in MySQL `admins` Table
        if ($action === 'change_admin_password') {
            $currPass = $_POST['current_password'] ?? '';
            $newUser  = trim($_POST['new_username'] ?? 'admin');
            $newPass  = $_POST['new_password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$_SESSION['cgv_admin_id']]);
            $adminRow = $stmt->fetch();

            if (!$adminRow || !password_verify($currPass, $adminRow['password_hash'])) {
                $errorMsg = 'Current password is incorrect.';
            } elseif (strlen($newPass) < 8) {
                $errorMsg = 'New password must be at least 8 characters long.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE admins SET username = :u, password_hash = :h WHERE id = :id")
                    ->execute([':u' => $newUser, ':h' => $newHash, ':id' => (int)$adminRow['id']]);
                $_SESSION['cgv_admin_user'] = $newUser;
                $flash = 'Admin username & bcrypt password hash updated in MySQL `admins` table!';
            }
        }
    }
}

$settings  = getSiteSettings() + homepageDefaults();
$about     = getAboutSection();
$offerings = getOfferings(false);
$gallery   = getGalleryItems();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Admin CMS — <?= htmlspecialchars($settings['site_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container admin-header-inner">
            <div style="display:flex;align-items:center;gap:12px;">
                <img src="../assets/images/logo.png" alt="Logo" width="44" height="44">
                <div>
                    <strong>Charles Gala Venue — Production MySQL Admin CMS</strong>
                    <div style="font-size:0.8rem;opacity:0.8;">Connected to MySQL (`<?= htmlspecialchars(DB_NAME) ?>`) • Logged in as `<?= htmlspecialchars($_SESSION['cgv_admin_user'] ?? 'admin') ?>`</div>
                </div>
            </div>
            <div style="display:flex;gap:12px;align-items:center;">
                <a href="../index.php" target="_blank" class="btn btn-gold">View Live Website ↗</a>
                <a href="logout.php" class="btn btn-outline-gold">Sign Out</a>
            </div>
        </div>
    </header>

    <main class="container section-pad">
        <?php if ($flash): ?>
            <div class="alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <h2>Homepage design & background</h2>
            <p>Upload your own venue photograph for the hero background. Until then, a neutral placeholder is displayed. Offering, gallery and About images remain editable in their sections below.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_homepage">
                <?php foreach (homepageDefaults() as $key => $default): if (in_array($key, ['hero_image_path', 'hero_video_url'], true)) continue; ?>
                    <label for="<?= $key ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></label>
                    <textarea id="<?= $key ?>" name="<?= $key ?>" rows="2" required><?= htmlspecialchars($settings[$key]) ?></textarea>
                <?php endforeach; ?>
                <label for="hero_video_url">Background video (YouTube URL)</label>
                <input type="url" id="hero_video_url" name="hero_video_url" value="<?= htmlspecialchars($settings['hero_video_url']) ?>" placeholder="https://www.youtube.com/watch?v=...">
                <p>Plays muted and loops. Leave blank to show only the background image. The image is also used when playback is unavailable.</p>
                <label for="hero_image">Homepage background image (JPEG, PNG or WebP; landscape recommended)</label>
                <input id="hero_image" type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
                <label><input type="checkbox" name="reset_hero" value="1" style="width:auto"> Use the neutral background placeholder</label>
                <button class="btn btn-gold" type="submit">Save homepage</button>
            </form>
        </section>

        <!-- 1. Gemini AI SEO Checker & Auto-Fixer -->
        <section class="admin-card">
            <h2>✨ Gemini AI SEO Audit & Auto-Fixer + Keyword Manager</h2>
            <p>Boost organic rankings for <strong>best stay</strong>, <strong>open ground hall</strong>, <strong>pool for anyone</strong>, <strong>pool booking</strong>, and local Mangalore searches.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_seo">
                <div class="form-grid">
                    <div>
                        <label>SEO Meta Title</label>
                        <input type="text" name="seo_meta_title" id="seo_meta_title" value="<?= htmlspecialchars($settings['seo_meta_title']) ?>" required>
                    </div>
                    <div>
                        <label>Canonical URL</label>
                        <input type="url" name="seo_canonical_url" value="<?= htmlspecialchars($settings['seo_canonical_url']) ?>" required>
                    </div>
                </div>
                <label>SEO Meta Description (Phone number hidden per privacy setting)</label>
                <textarea name="seo_meta_description" id="seo_meta_description" rows="3" required><?= htmlspecialchars($settings['seo_meta_description']) ?></textarea>

                <label>Target Organic SEO Keywords (Comma-separated)</label>
                <textarea name="seo_keywords" id="seo_keywords" rows="2" required><?= htmlspecialchars($settings['seo_keywords']) ?></textarea>

                <div class="form-grid">
                    <div>
                        <label>Gemini API Key (for automated live SEO checking & fixing)</label>
                        <input type="password" name="gemini_api_key" id="gemini_api_key" value="<?= htmlspecialchars($settings['gemini_api_key']) ?>" placeholder="AIzaSy...">
                    </div>
                    <div>
                        <label>Gemini Model</label>
                        <input type="text" name="gemini_model" value="<?= htmlspecialchars($settings['gemini_model'] ?: 'gemini-2.5-flash') ?>">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-top:1rem;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-gold">Save SEO & Keywords to MySQL</button>
                    <button type="button" onclick="runGeminiSeoAudit()" class="btn btn-whatsapp-lg">🤖 Run Gemini AI Check & Auto-Fix SEO</button>
                </div>
                <div id="gemini-seo-result" style="margin-top:1rem;"></div>
            </form>
        </section>

        <!-- 2. Offerings Management -->
        <section class="admin-card">
            <h2>🏛️ Manage Offerings & Upload Offering Photos (MySQL `offerings` Table)</h2>
            <?php foreach ($offerings as $off): ?>
                <form method="POST" enctype="multipart/form-data" class="offering-admin-row">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_offering">
                    <input type="hidden" name="offering_id" value="<?= (int)$off['id'] ?>">
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($off['image_path']) ?>">
                    <div class="form-grid">
                        <div>
                            <label>Offering Title</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($off['title']) ?>" required>
                        </div>
                        <div>
                            <label>Capacity / Subtitle</label>
                            <input type="text" name="capacity_label" value="<?= htmlspecialchars($off['capacity_label']) ?>" required>
                        </div>
                        <div>
                            <label>Badge</label>
                            <input type="text" name="badge" value="<?= htmlspecialchars($off['badge']) ?>">
                        </div>
                        <div>
                            <label>Upload New Offering Photo</label>
                            <input type="file" name="offering_image" accept="image/*">
                        </div>
                    </div>
                    <label>Description</label>
                    <textarea name="short_desc" rows="2"><?= htmlspecialchars($off['short_desc']) ?></textarea>
                    <div class="form-grid">
                        <div>
                            <label>SEO Focus Keyword</label>
                            <input type="text" name="seo_focus_keyword" value="<?= htmlspecialchars($off['seo_focus_keyword']) ?>">
                        </div>
                        <div>
                            <label>Pre-filled WhatsApp "Book Now" Message</label>
                            <input type="text" name="whatsapp_custom_msg" value="<?= htmlspecialchars($off['whatsapp_custom_msg']) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-gold" style="margin-top:0.75rem;">Save <?= htmlspecialchars($off['title']) ?> to MySQL</button>
                </form>
            <?php endforeach; ?>
        </section>

        <!-- 3. Gallery Management -->
        <section class="admin-card">
            <h2>🎬 Gallery Management (MySQL `gallery_items` Table)</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="add_gallery">
                <div class="form-grid">
                    <div>
                        <label>Media Type</label>
                        <select name="media_type">
                            <option value="photo">Uploaded Photo</option>
                            <option value="youtube">YouTube Video Link</option>
                        </select>
                    </div>
                    <div>
                        <label>Title / Caption</label>
                        <input type="text" name="title" placeholder="e.g. Grand Wedding Reception Setup" required>
                    </div>
                    <div>
                        <label>Category</label>
                        <input type="text" name="category" value="Open Ground Hall">
                    </div>
                    <div>
                        <label>Upload Photo (if Photo)</label>
                        <input type="file" name="gallery_image" accept="image/*">
                    </div>
                </div>
                <div class="form-grid" style="margin-top:0.75rem;">
                    <div>
                        <label>YouTube Video URL (if YouTube)</label>
                        <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding-top:1.6rem;">
                        <input type="checkbox" name="autoplay" id="ap_new" value="1" style="width:auto;">
                        <label for="ap_new" style="margin:0;">Enable YouTube Autoplay (Muted inline)</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">+ Insert into MySQL Gallery</button>
            </form>
        </section>

        <!-- 4. About Us Section Management -->
        <section class="admin-card">
            <h2>🌴 About Us Section (MySQL `about_section` Table)</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_about">
                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($about['image_path']) ?>">
                <div class="form-grid">
                    <div>
                        <label>Headline</label>
                        <input type="text" name="headline" value="<?= htmlspecialchars($about['headline']) ?>" required>
                    </div>
                    <div>
                        <label>Upload New About Us Image</label>
                        <input type="file" name="about_image" accept="image/*">
                    </div>
                </div>
                <label>Subheadline</label>
                <input type="text" name="subheadline" value="<?= htmlspecialchars($about['subheadline']) ?>">
                <label>About Details (HTML supported)</label>
                <textarea name="content_html" rows="4"><?= htmlspecialchars($about['content_html']) ?></textarea>
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Save About Us to MySQL</button>
            </form>
        </section>

        <!-- 5. Backend WhatsApp, Call & Social Media Management -->
        <section class="admin-card">
            <h2>💬 Backend WhatsApp, Call & Social Media Links (MySQL `site_settings` Table)</h2>
            <p style="font-size:0.85rem;color:#64748b;margin-bottom:1rem;">Note: The Call & WhatsApp numbers configured below are kept hidden from public website text and are triggered only when a visitor clicks the Call icon or WhatsApp icon.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_settings">
                <div class="form-grid">
                    <div>
                        <label>Backend Call Number (Triggered on Call Icon Click)</label>
                        <input type="text" name="phone_primary" value="<?= htmlspecialchars($settings['phone_primary']) ?>">
                    </div>
                    <div>
                        <label>Backend WhatsApp Number (Triggered on WhatsApp Icon Click)</label>
                        <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($settings['whatsapp_number']) ?>">
                    </div>
                    <div>
                        <label>Official Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($settings['email']) ?>">
                    </div>
                    <div>
                        <label>Instagram Page URL</label>
                        <input type="url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url']) ?>">
                    </div>
                    <div>
                        <label>YouTube Channel URL</label>
                        <input type="url" name="youtube_url" value="<?= htmlspecialchars($settings['youtube_url']) ?>">
                    </div>
                    <div>
                        <label>Facebook Page URL</label>
                        <input type="url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url']) ?>">
                    </div>
                </div>
                <div class="form-grid">
                    <?php foreach (['google_maps_url' => 'Google Maps location URL', 'google_maps_embed' => 'Google Maps embed URL (src only)', 'latitude' => 'Latitude', 'longitude' => 'Longitude'] as $key => $label): ?>
                    <div><label for="<?= $key ?>"><?= $label ?></label><input id="<?= $key ?>" name="<?= $key ?>" type="text" value="<?= htmlspecialchars($settings[$key] ?? '') ?>"></div>
                    <?php endforeach; ?>
                </div>
                <label>Default Floating WhatsApp Greeting Message</label>
                <input type="text" name="whatsapp_default_msg" value="<?= htmlspecialchars($settings['whatsapp_default_msg']) ?>">
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Save Contact & Social Settings to MySQL</button>
            </form>
        </section>

        <!-- 6. Admin Security & MySQL Password Management -->
        <section class="admin-card">
            <h2>🔐 Admin Security — Update MySQL Login Credentials (`admins` Table)</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="change_admin_password">
                <div class="form-grid">
                    <div>
                        <label>Admin Username</label>
                        <input type="text" name="new_username" value="<?= htmlspecialchars($_SESSION['cgv_admin_user'] ?? 'admin') ?>" required>
                    </div>
                    <div>
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div>
                        <label>New Password (min 8 chars, stored as Bcrypt hash in MySQL)</label>
                        <input type="password" name="new_password" minlength="8" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Update Admin Credentials in MySQL</button>
            </form>
        </section>
    </main>

    <script>
    async function runGeminiSeoAudit() {
        const box = document.getElementById('gemini-seo-result');
        box.innerHTML = '<div class="alert-success">Running Gemini AI SEO analysis & auto-optimization...</div>';
        try {
            const resp = await fetch('api_gemini_seo.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    title: document.getElementById('seo_meta_title').value,
                    description: document.getElementById('seo_meta_description').value,
                    keywords: document.getElementById('seo_keywords').value,
                    api_key: document.getElementById('gemini_api_key').value
                })
            });
            const data = await resp.json();
            if (data.fixed_title) document.getElementById('seo_meta_title').value = data.fixed_title;
            if (data.fixed_description) document.getElementById('seo_meta_description').value = data.fixed_description;
            if (data.fixed_keywords) document.getElementById('seo_keywords').value = data.fixed_keywords;
            box.innerHTML = `<div class="alert-success"><strong>Gemini SEO Score: ${data.score}/100</strong><br>${data.summary}</div>`;
        } catch (e) {
            box.innerHTML = '<div class="alert-error">Failed to run SEO check.</div>';
        }
    }
    </script>
</body>
</html>


