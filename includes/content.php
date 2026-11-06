<?php
/** Editable public copy. Defaults preserve the existing site until an administrator saves. */
function publicCopyGroups(): array {
 return [
'Navigation & accessibility' => [
'copy_brand_location' => 'Kulur · Mangalore',
'copy_nav_spaces' => 'Our spaces',
'copy_nav_pool' => 'The pool',
'copy_nav_about' => 'About',
'copy_nav_gallery' => 'Gallery',
'copy_nav_location' => 'Find us',
'copy_nav_cta' => 'Plan your visit',
'copy_menu_open' => 'Menu',
'copy_menu_close' => 'Close',
'copy_skip_label' => 'Skip to content',
'copy_nav_aria' => 'Main navigation',
'copy_home_label' => 'home',
],
'Homepage & spaces' => [
'copy_hero_cta' => 'Check availability',
'copy_hero_secondary_cta' => 'Explore the venue',
'copy_hero_image_alt' => 'Palm-lined lawn at Charles Gala Venue in Kulur, Mangalore',
'copy_stats_label' => 'Venue at a glance',
'copy_spaces_count_label' => 'Spaces to choose from',
'copy_pool_stat' => 'Pool',
'copy_pool_stat_note' => 'Enquire about visiting',
'copy_scroll_label' => 'Scroll',
'copy_scroll_aria' => 'Scroll to our spaces',
'copy_motion_pause' => 'Pause motion',
'copy_motion_play' => 'Play motion',
'copy_marquee_text' => 'Celebrate · Swim · Stay',
'copy_spaces_kicker' => 'Our spaces',
'copy_spaces_heading' => 'Good company. A great setting.',
'copy_spaces_description' => 'From a celebration with everyone you love to a relaxed day by the pool, find a space that suits your plans.',
'copy_space_cta' => 'Enquire about this space',
],
'Pool & about' => [
'copy_pool_kicker' => 'The pool',
'copy_pool_image_alt' => 'Swimming pool at Charles Gala Venue',
'copy_pool_cta' => 'Plan a pool day',
'copy_pool_message' => 'Hello, please share swimming pool timings, rates and availability.',
'copy_about_kicker' => 'About the venue',
],
'Gallery & questions' => [
'copy_gallery_kicker' => 'Gallery',
'copy_photo_count_label' => 'photographs',
'copy_gallery_heading' => 'Picture your day here.',
'copy_gallery_description' => 'Explore the lawn, pool and celebration spaces. Select a photograph for a closer look.',
'copy_gallery_all' => 'All',
'copy_gallery_filter_label' => 'Filter gallery',
'copy_gallery_region_label' => 'Venue gallery',
'copy_gallery_video_label' => 'Watch venue video',
'copy_faq_kicker' => 'Good to know',
'copy_faq_heading' => 'Before your visit.',
'copy_viewer_label' => 'Photograph viewer',
'copy_viewer_close' => 'Close',
'copy_viewer_previous' => 'Previous photograph',
'copy_viewer_next' => 'Next photograph',
'copy_cursor_view' => 'View',
],
'Contact & enquiry' => [
'copy_visit_kicker' => 'Plan your visit',
'copy_visit_heading' => 'Your next occasion starts here.',
'copy_visit_description' => 'Tell us your preferred date, occasion and number of guests. Our team will help you with availability and arrangements.',
'copy_visit_whatsapp' => 'Message on WhatsApp',
'copy_visit_call' => 'Call the venue',
'copy_address_label' => 'Visit',
'copy_email_label' => 'Write',
'copy_directions_label' => 'Directions',
'copy_directions_cta' => 'Open in Google Maps',
'copy_map_directions_cta' => 'Open directions in Google Maps',
'copy_map_title' => 'Map of',
'copy_booking_kicker' => 'Quick enquiry',
'copy_booking_heading' => 'Tell us about your occasion',
'copy_occasion_label' => 'Occasion',
'copy_guests_label' => 'Guests',
'copy_date_label' => 'Preferred date',
'copy_booking_cta' => 'Send enquiry on WhatsApp',
'copy_booking_note' => 'Opens WhatsApp with your details pre-filled. No account or payment is needed to ask.',
'copy_occasion_options' => 'Wedding
Reception
Engagement
Birthday
Corporate event
Pool party
Family stay',
'copy_guest_options' => 'Up to 50
50 – 150
150 – 400
400 – 800
800 – 1,500',
'copy_booking_message' => 'Hello {venue}! I\'m planning a {occasion} for {guests} guests on {date}. Could you share availability and packages?',
'copy_booking_date_fallback' => 'a date to be confirmed',
],
'Footer & quick contact' => [
'copy_footer_credit_label' => 'Designed and Developed by',
'copy_footer_credit_name' => 'CodeIXE AI Solutions',
'copy_footer_credit_url' => 'https://codeixe.com/',
'copy_social_label' => 'Social links',
'copy_instagram_label' => 'Instagram',
'copy_facebook_label' => 'Facebook',
'copy_youtube_label' => 'YouTube',
'copy_dock_label' => 'Quick contact',
'copy_dock_whatsapp' => 'WhatsApp',
'copy_dock_call' => 'Call',
],
];
}
function publicCopyDefaults(): array { return array_merge(...array_values(publicCopyGroups())); }
function contentLines(string $value): array { return array_slice(array_values(array_filter(array_map("trim", preg_split("/\R/u", $value)), static fn($v) => $v !== "")), 0, 40); }
