<?php
if (!isset($settings, $csrfToken)) { http_response_code(404); exit; }
foreach (bookingRoutes() as $key => $path):
    $booking = bookingContent($key, $settings); ?>
<section class="card">
    <div class="card-title"><h2><?= adminEscape($booking['title']) ?></h2><a href="../<?= adminEscape($path) ?>">View page ↗</a></div>
    <?php adminFormStart('save_booking', $csrfToken); ?>
    <input type="hidden" name="booking_key" value="<?= adminEscape($key) ?>">
    <?php
    adminField('title', 'Page heading', $booking['title']);
    adminField('intro', 'Introduction', $booking['intro'], 'textarea');
    foreach ($booking['packages'] as $i=>$package):
        $prefix = 'packages['.$i.']'; ?>
        <fieldset><legend><?= adminEscape($package['name']) ?></legend>
        <?php
        adminField($prefix.'[name]', 'Package or room name', $package['name']);
        adminField($prefix.'[price]', 'Package price (₹)', $package['price'], 'number');
        adminField($prefix.'[facilities]', 'Included facilities and guest terms', implode("\n", $package['facilities']), 'textarea', 'One item per line.');
        ?>
        </fieldset>
    <?php endforeach;
    adminField('notes', 'Additional charges and booking notes', $booking['notes'], 'textarea');
    adminField('seo_title', 'Search title', $booking['seo_title'] ?? '', 'text', 'Leave blank to use the venue and page name.');
    adminField('seo_description', 'Meta description', $booking['seo_description'] ?? '', 'textarea', 'Leave blank to generate a description from the current package prices and booking notes.');
    adminSave('Save booking details'); ?>
</section>
<?php endforeach; ?>
