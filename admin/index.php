<?php
/**
 * Charles Gala Venue - Production MySQL Admin Dashboard (`admin/index.php`)
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdminAuth();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

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
            $flash = 'Contact details and social links saved.';
        }

        if ($action === 'save_homepage' && trim($_POST['hero_video_url'] ?? '') !== '' && backgroundYoutubeId((string)$_POST['hero_video_url']) === '') {
            $errorMsg = 'Enter a valid YouTube video URL, or leave the field blank for a photo background.';
        } elseif ($action === 'save_homepage') {
            updateSiteSetting('hero_video_url', trim((string)($_POST['hero_video_url'] ?? '')));
            foreach (homepageDefaults() as $key => $default) {
                if ($key !== 'hero_image_path') updateSiteSetting($key, trim((string)($_POST[$key] ?? $default)));
            }
            $image = handleImageUpload($_FILES['hero_image'] ?? [], 'about');
            if ($image) updateSiteSetting('hero_image_path', $image);
            if (isset($_POST['reset_hero'])) updateSiteSetting('hero_image_path', 'assets/images/placeholder.svg');
            $flash = 'Homepage content and background saved.';
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
            $flash = 'About section and photo updated.';
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
                $flash = 'Offering updated.';
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
                $flash = 'New offering saved.';
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
            $flash = 'Gallery item saved.';
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
                $flash = 'Administrator credentials updated.';
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
    <title>Content manager | <?= htmlspecialchars($settings['site_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container admin-header-inner">
            <div style="display:flex;align-items:center;gap:12px;">
                <img src="../assets/images/logo.png" alt="Logo" width="44" height="44">
                <div>
                    <strong>Charles Gala Venue | Content manager</strong>
                    <div style="font-size:0.8rem;opacity:0.8;">Signed in as <?= htmlspecialchars($_SESSION['cgv_admin_user'] ?? 'admin') ?></div>
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
            <p>Upload your own venue photograph for the hero background. The existing venue photograph is used until replaced. Offering, gallery and About images remain editable in their sections below.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_homepage">
                <?php foreach (homepageDefaults() as $key => $default): if ($key === 'hero_image_path') continue; ?>
                    <label for="<?= $key ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></label>
                    <textarea id="<?= $key ?>" name="<?= $key ?>" rows="2" required><?= htmlspecialchars($settings[$key]) ?></textarea>
                <?php endforeach; ?>
                <label for="hero_video_url">YouTube background video URL</label>
                <input id="hero_video_url" type="url" name="hero_video_url" value="<?= htmlspecialchars($settings['hero_video_url'] ?? 'https://www.youtube.com/watch?v=zVg8op029Mg') ?>">
                <p>The video plays muted. Leave blank to use only the background photograph. The photograph also appears when video playback is unavailable or reduced motion is enabled.</p>
                <label for="hero_image">Homepage background image (JPEG, PNG or WebP; landscape recommended)</label>
                <input id="hero_image" type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
                <label><input type="checkbox" name="reset_hero" value="1" style="width:auto"> Use the neutral background placeholder</label>
                <button class="btn btn-gold" type="submit">Save homepage</button>
            </form>
        </section>

        <section class="admin-card"><h2>SEO & AI discovery</h2><p>Edit metadata, target keywords and visitor questions. Review saved scores, compare reports and request optional Gemini insights.</p><a class="btn btn-gold" href="seo.php">Open SEO dashboard</a></section>

        <!-- 2. Offerings Management -->
        <section class="admin-card">
            <h2>Spaces and experiences</h2>
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
                    <button type="submit" class="btn btn-gold" style="margin-top:0.75rem;">Save <?= htmlspecialchars($off['title']) ?></button>
                </form>
            <?php endforeach; ?>
        </section>

        <!-- 3. Gallery Management -->
        <section class="admin-card">
            <h2>Gallery</h2>
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
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Add to gallery</button>
            </form>
        </section>

        <!-- 4. About Us Section Management -->
        <section class="admin-card">
            <h2>About the venue</h2>
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
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Save About Us</button>
            </form>
        </section>

        <!-- 5. Backend WhatsApp, Call & Social Media Management -->
        <section class="admin-card">
            <h2>Contact details and social links</h2>
            <p style="font-size:0.85rem;color:#64748b;margin-bottom:1rem;">Note: The Call & WhatsApp numbers configured below are kept hidden from public website text and are triggered only when a visitor clicks the Call icon or WhatsApp icon.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save_settings">
                <div class="form-grid">
                    <div>
                        <label>Backend Telephone number</label>
                        <input type="text" name="phone_primary" value="<?= htmlspecialchars($settings['phone_primary']) ?>">
                    </div>
                    <div>
                        <label>WhatsApp number</label>
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
                <label>Default WhatsApp enquiry message</label>
                <input type="text" name="whatsapp_default_msg" value="<?= htmlspecialchars($settings['whatsapp_default_msg']) ?>">
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Save Contact & Social Settings</button>
            </form>
        </section>

        <!-- 6. Admin Security & MySQL Password Management -->
        <section class="admin-card">
            <h2>Administrator account</h2>
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
                        <label>New password (minimum 8 characters)</label>
                        <input type="password" name="new_password" minlength="8" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold" style="margin-top:1rem;">Update administrator credentials</button>
            </form>
        </section>
    </main>


</body>
</html>


