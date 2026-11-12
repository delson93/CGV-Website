<?php
/**
 * Charles Gala Venue - Production MySQL Admin Dashboard (`admin/index.php`)
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdminAuth();
require_once __DIR__.'/layout.php';
require_once __DIR__.'/../includes/seo.php';
$page = (string)($_GET['page'] ?? 'dashboard');
if (!array_key_exists($page, adminPages()) || $page === 'seo') $page = 'dashboard';
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$pdo = getDB();
$flash = '';
$errorMsg = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $errorMsg = 'Your session token expired. Reload this page and try again.';
    } else try {
        $action = $_POST['action'] ?? '';
        if (!in_array($action, ['save_booking','save_copy','save_settings','save_homepage','save_about','save_offering','add_gallery','save_gallery','change_admin_password'],true)) throw new RuntimeException('Unknown save action.');
        foreach ($_FILES as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && ($file['error'] ?? 0) !== UPLOAD_ERR_OK) throw new RuntimeException('The image could not be uploaded. Check its size and try again.');
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && !in_array(mime_content_type($file['tmp_name']), ['image/jpeg','image/png','image/webp'],true)) throw new RuntimeException('Choose a JPEG, PNG or WebP image.');
        }
        if ($action === 'save_copy') foreach (['copy_occasion_options','copy_guest_options'] as $key) {
            if (isset($_POST[$key]) && !contentLines((string)$_POST[$key])) throw new RuntimeException('Occasion and guest options must each contain at least one choice.');
        }
        if (in_array($action,['save_offering','save_gallery','add_gallery'],true) && trim((string)($_POST['title']??''))==='') throw new RuntimeException('Enter a title before saving.');
        if ($action === 'change_admin_password' && trim((string)($_POST['new_username']??''))==='') throw new RuntimeException('Enter a username.');
        $pdo->beginTransaction();
        if ($action === 'save_booking') {
            $key = (string)($_POST['booking_key'] ?? '');
            if (!isset(bookingRoutes()[$key])) throw new RuntimeException('Unknown booking page.');
            $booking = bookingFromForm($key, $_POST);
            updateSiteSetting('booking_' . $key, json_encode($booking, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $flash = 'Booking details saved.';
        }
        if ($action === 'save_copy') {
            foreach (publicCopyDefaults() as $key=>$default) {
                if (isset($_POST[$key]) && is_string($_POST[$key])) updateSiteSetting($key, substr(trim($_POST[$key]),0,10000));
            }
            $flash = 'Website text saved.';
        }

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
                if ($key !== 'hero_image_path' && isset($_POST[$key])) updateSiteSetting($key, trim((string)$_POST[$key]));
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
            $stmt = $pdo->prepare("UPDATE about_section SET headline = :h, subheadline = :s, content_html = :c, highlights_json = :highlights, image_path = :i WHERE id = 1");
            $stmt->execute([
                ':h' => $_POST['headline'] ?? '',
                ':s' => $_POST['subheadline'] ?? '',
                ':c' => $_POST['content_html'] ?? '',
                ':highlights' => json_encode(contentLines((string)($_POST['highlights'] ?? '')), JSON_UNESCAPED_UNICODE),
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
                $stmt = $pdo->prepare("UPDATE offerings SET title=:t, badge=:b, capacity_label=:cap, short_desc=:d, seo_focus_keyword=:kw, whatsapp_custom_msg=:wa, image_path=:img, features_json=:features WHERE id=:id");
                $stmt->execute([
                    ':t'   => $_POST['title'],
                    ':b'   => $_POST['badge'],
                    ':cap' => $_POST['capacity_label'],
                    ':d'   => $_POST['short_desc'],
                    ':kw'  => $_POST['seo_focus_keyword'],
                    ':wa'  => $_POST['whatsapp_custom_msg'],
                    ':img' => $imgPath,
                    ':features' => json_encode(contentLines((string)($_POST['features'] ?? '')), JSON_UNESCAPED_UNICODE),
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
                    ':f'    => json_encode(contentLines((string)($_POST['features'] ?? '')), JSON_UNESCAPED_UNICODE),
                    ':kw'   => $_POST['seo_focus_keyword'] ?: strtolower($_POST['title']) . ' Mangalore',
                    ':wa'   => $_POST['whatsapp_custom_msg'],
                    ':img'  => $imgPath
                ]);
                $flash = 'New offering saved.';
            }
        }

        // E. Add Gallery Photo or YouTube Video with Autoplay Option in MySQL
        if ($action === 'add_gallery' || $action === 'save_gallery') {
            $mediaType = $_POST['media_type'] === 'youtube' ? 'youtube' : 'photo';
            $title = trim($_POST['title'] ?? 'Charles Gala Venue Moment');
            $category = trim($_POST['category'] ?? 'Venue');
            $autoplay = isset($_POST['autoplay']) ? 1 : 0;
            $ytUrl = trim($_POST['youtube_url'] ?? '');
            $ytId = $mediaType === 'youtube' ? extractYoutubeId($ytUrl) : null;
            $uploadedImg = handleImageUpload($_FILES['gallery_image'] ?? [], 'gallery');
            if ($mediaType === 'youtube' && !preg_match('/^[a-zA-Z0-9_-]{11}$/D', $ytId ?? '')) throw new RuntimeException('Enter a valid YouTube URL.');
            $galleryId = (int)($_POST['gallery_id'] ?? 0);
            $imgPath = $uploadedImg ?: 'assets/images/placeholder.svg';
            if ($action === 'save_gallery') {
                $existing = $pdo->prepare('SELECT image_path FROM gallery_items WHERE id=:id');
                $existing->execute([':id'=>$galleryId]);
                $old = $existing->fetch();
                if (!$old) throw new RuntimeException('Gallery item not found.');
                $imgPath = $uploadedImg ?: $old['image_path'];
            }

            $stmt = $pdo->prepare($action === 'save_gallery' ? "UPDATE gallery_items SET media_type=:m, title=:t, category=:c, image_path=:i, youtube_url=:yu, youtube_id=:yi, autoplay=:ap WHERE id=".$galleryId : "INSERT INTO gallery_items (media_type, title, category, image_path, youtube_url, youtube_id, autoplay) VALUES (:m, :t, :c, :i, :yu, :yi, :ap)");
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
        if ($errorMsg !== '') { $pdo->rollBack(); }
        else { $pdo->commit(); $_SESSION['admin_flash']=$flash; header('Location: index.php?page='.rawurlencode($page)); exit; }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorMsg = $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Unable to save changes. Please try again or contact your administrator.';
    }
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $flash = $_SESSION['admin_flash'] ?? ''; unset($_SESSION['admin_flash']); }

$settings  = getSiteSettings() + homepageDefaults() + publicCopyDefaults();
$about     = getAboutSection();
$offerings = getOfferings(false);
$gallery   = getGalleryItems();

adminStart($page, adminPages()[$page], $settings);
?>
<?php if($flash): ?><div class="notice" role="status"><?= adminEscape($flash) ?></div><?php endif; ?>
<?php if($errorMsg): ?><div class="error" role="alert"><?= adminEscape($errorMsg) ?></div><?php endif; ?>
<?php if($page==='dashboard'): $audit=seoAudit($settings,$about,getOfferings(true),$gallery); ?>
<div class="welcome-banner"><div><span class="eyebrow">YOUR VENUE, BEAUTIFULLY MANAGED</span><h2>A clear view of your website.</h2><p>Keep your venue information current, refine your content and help guests plan their next occasion.</p><a class="button" href="index.php?page=homepage">Edit homepage <span>↗</span></a></div><div class="banner-monogram" aria-hidden="true">CG<span>CHARLES GALA VENUE</span></div></div>
<div class="metric-grid"><a class="metric" href="index.php?page=offerings"><span>Spaces & experiences</span><strong><?= count($offerings) ?></strong><small>Manage your venue offerings ↗</small></a><a class="metric" href="index.php?page=gallery"><span>Library items</span><strong><?= count($gallery) ?></strong><small>Photos and venue videos ↗</small></a><a class="metric" href="seo.php"><span>On-page readiness</span><strong><?= $audit['scores']['seo'] ?><em>/100</em></strong><small>Review content checks ↗</small></a><a class="metric" href="seo.php"><span>AI discovery readiness</span><strong><?= $audit['scores']['ai'] ?><em>/100</em></strong><small>Review factual information ↗</small></a></div>
<div class="dashboard-grid"><section class="card"><div class="card-title"><h2>Your publishing workspace</h2><span>QUICK ACCESS</span></div><div class="quick-grid"><?php foreach(['homepage'=>'Update your first impression and background video.','copy'=>'Edit navigation, section text and enquiry labels.','gallery'=>'Manage captions, categories and photography.','seo'=>'Refine search previews and compare saved reports.'] as $key=>$desc): ?><a class="quick-link" href="<?= $key==='seo'?'seo.php':'index.php?page='.$key ?>"><strong><?= adminEscape(adminPages()[$key]) ?> <span>↗</span></strong><p><?= adminEscape($desc) ?></p></a><?php endforeach; ?></div></section><section class="card"><h2>Content priorities</h2><p class="hint">Editorial checks, not rankings or traffic measurements.</p><ul class="priority-list"><?php $failures=array_slice(array_filter($audit['checks'],static fn($c)=>!$c['pass']),0,4); foreach($failures as $check): ?><li><span class="priority-dot"></span><div><strong><?= adminEscape($check['label']) ?></strong><p><?= adminEscape($check['recommendation']) ?></p></div></li><?php endforeach; if(!$failures): ?><li>Your configured content passes the current editorial checklist.</li><?php endif; ?></ul><a href="seo.php">Review all SEO checks →</a></section></div>
<?php elseif($page==='homepage'): ?>
<p class="page-intro">Set the tone for your venue. Update the homepage introduction, pool feature and background media.</p><section class="card"><?php adminFormStart('save_homepage',$csrfToken); ?><h2>Homepage content</h2><div class="form-grid"><?php foreach(homepageDefaults() as $key=>$default): if(in_array($key,['hero_image_path','hero_video_url'])) continue; adminField($key,ucwords(str_replace('_',' ',$key)),$settings[$key],'textarea'); endforeach; ?></div><hr><h2>Background media</h2><?php adminField('hero_video_url','YouTube background video URL',$settings['hero_video_url'],'url','Video is muted. Leave blank for a photo background.'); adminField('hero_image','Replace background photograph','','file','JPEG, PNG or WebP. A wide photograph works best.'); ?><label class="check"><input type="checkbox" name="reset_hero" value="1"> Use a neutral placeholder background</label><?php adminSave('Save homepage'); ?></section>
<?php elseif($page==='copy'): ?>
<p class="page-intro">Edit public website labels and section copy. Use the other sections for venue details, photographs and FAQs.</p>
<?php adminFormStart('save_copy',$csrfToken); foreach(publicCopyGroups() as $group=>$fields): ?><section class="card"><h2><?= adminEscape($group) ?></h2><div class="form-grid"><?php foreach($fields as $key=>$default): adminField($key,ucwords(str_replace('_',' ',substr($key,5))),$settings[$key],$key==='copy_footer_credit_url'?'url':'textarea',in_array($key,['copy_occasion_options','copy_guest_options'])?'One choice per line.':($key==='copy_booking_message'?'Available placeholders: {venue}, {occasion}, {guests}, {date}.':'')); endforeach; ?></div></section><?php endforeach; adminSave('Save website text'); ?>
<?php elseif($page==='bookings'): require __DIR__ . '/booking_editor.php'; ?>
<?php elseif($page==='offerings'): ?>
<p class="page-intro">Manage the names, descriptions, capacities and features guests see on your website.</p>
<?php foreach($offerings as $off): ?><section class="card"><h2><?= adminEscape($off['title']) ?></h2><?php adminFormStart('save_offering',$csrfToken); ?><input type="hidden" name="offering_id" value="<?= (int)$off['id'] ?>"><input type="hidden" name="existing_image" value="<?= adminEscape($off['image_path']) ?>"><div class="form-grid"><?php foreach(['title'=>'Title','badge'=>'Badge','capacity_label'=>'Capacity / subtitle','seo_focus_keyword'=>'Focus keyword'] as $key=>$label) adminField($key,$label,$off[$key]??''); ?></div><?php adminField('short_desc','Description',$off['short_desc'],'textarea'); adminField('features','Features',implode("\n",json_decode($off['features_json']??'[]',true)?:[]),'textarea','One feature per line.'); adminField('whatsapp_custom_msg','WhatsApp enquiry message',$off['whatsapp_custom_msg'],'textarea'); adminField('offering_image','Replace photograph','','file'); adminSave('Save space'); ?></section><?php endforeach; ?>
<?php elseif($page==='gallery'): ?>
<p class="page-intro">Keep your photography organized. Titles also provide descriptive alternative text for gallery images.</p>
<?php $galleryForms=array_merge([['id'=>0,'title'=>'','category'=>'Venue','media_type'=>'photo','youtube_url'=>'']],$gallery); foreach($galleryForms as $g): ?><section class="card"><div class="media-heading"><?php if(!empty($g['image_path'])): ?><img src="<?= adminEscape(str_starts_with(publicImage($g['image_path']), 'https://') ? publicImage($g['image_path']) : '../'.publicImage($g['image_path'])) ?>" alt="" width="80" height="64" loading="lazy"><?php endif; ?><h2><?= $g['id'] ? adminEscape($g['title']) : 'Add media' ?></h2></div><?php adminFormStart($g['id']?'save_gallery':'add_gallery',$csrfToken); ?><input type="hidden" name="gallery_id" value="<?= (int)$g['id'] ?>"><div class="form-grid"><label class="field"><span>Media type</span><select name="media_type"><option value="photo" <?= $g['media_type']==='photo'?'selected':'' ?>>Photograph</option><option value="youtube" <?= $g['media_type']==='youtube'?'selected':'' ?>>YouTube video</option></select></label><?php adminField('title','Title / image description',$g['title']); adminField('category','Category',$g['category']); adminField('youtube_url','YouTube URL',$g['youtube_url']??'','url'); ?></div><?php adminField('gallery_image','Upload or replace photograph','','file','Leave empty to keep the existing image.'); ?><label class="check"><input type="checkbox" name="autoplay" value="1" <?= !empty($g['autoplay'])?'checked':'' ?>> Autoplay preference for embedded video</label><?php adminSave($g['id']?'Save media':'Add media'); ?></section><?php endforeach; ?>
<?php elseif($page==='about'): ?>
<p class="page-intro">Introduce your venue and highlight what makes it special.</p><section class="card"><?php adminFormStart('save_about',$csrfToken); ?><input type="hidden" name="existing_image" value="<?= adminEscape($about['image_path']??'') ?>"><?php adminField('headline','Headline',$about['headline']??''); adminField('subheadline','Subheading',$about['subheadline']??''); adminField('content_html','About content',$about['content_html']??'','textarea','HTML formatting is supported.'); adminField('highlights','Venue highlights',implode("\n",json_decode($about['highlights_json']??'[]',true)?:[]),'textarea','One highlight per line.'); adminField('about_image','About photograph','','file'); adminSave('Save venue introduction'); ?></section>
<?php elseif($page==='contact'): ?>
<p class="page-intro">Keep your business identity, contact details and location consistent across the website.</p><section class="card"><?php adminFormStart('save_settings',$csrfToken); ?><h2>Business information</h2><div class="form-grid"><?php foreach(['site_name'=>'Venue name','phone_primary'=>'Telephone number','whatsapp_number'=>'WhatsApp number','email'=>'Email address','address'=>'Full address','plus_code'=>'Google Maps Plus Code','instagram_url'=>'Instagram URL','facebook_url'=>'Facebook URL','youtube_url'=>'YouTube URL','google_maps_url'=>'Google Maps directions URL','google_maps_embed'=>'Google Maps embed URL (src only)','latitude'=>'Latitude','longitude'=>'Longitude','whatsapp_default_msg'=>'Default WhatsApp message'] as $key=>$label) adminField($key,$label,$settings[$key]??'',in_array($key,['address','whatsapp_default_msg'])?'textarea':(str_ends_with($key,'_url')?'url':($key==='email'?'email':'text'))); ?></div><?php adminSave('Save contact details'); ?></section>
<?php elseif($page==='account'): ?>
<p class="page-intro">Manage your sign-in details. Your current password is required to make a change.</p><section class="card narrow"><?php adminFormStart('change_admin_password',$csrfToken); adminField('new_username','Username',$_SESSION['cgv_admin_user']??'admin'); adminField('current_password','Current password','','password'); adminField('new_password','New password','','password','Use at least eight characters.'); adminSave('Update account'); ?></section>
<?php endif; adminEnd(); ?>
