<?php
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/seo.php';
function assertContent($ok, string $label): void { if (!$ok) throw new RuntimeException($label); }
assertContent(contentLines("Wedding\r\n\r\nFamily stay\n") === ['Wedding','Family stay'], 'Content lists normalize lines');
$settings = ['site_name'=>'Venue & Events','seo_meta_title'=>'Venue search title','seo_meta_description'=>'Venue search description','seo_canonical_url'=>'https://example.org/','seo_social_title'=>'Social "title"','seo_social_description'=>'Social description','seo_social_image'=>'https://example.org/share.jpg','seo_social_image_alt'=>'Venue photograph','seo_google_verification'=>'token"/><script>bad()</script>','gemini_api_key'=>'PRIVATE_TEST_VALUE'] + homepageDefaults() + publicCopyDefaults();
$offerings=[];
ob_start();include __DIR__.'/../includes/seo_head.php';$head=ob_get_clean();
assertContent(str_contains($head,'property="og:title" content="Social &quot;title&quot;"'),'Social title is escaped');
assertContent(str_contains($head,'name="description" content="Venue search description"'),'Search description remains independent');
assertContent(str_contains($head,'name="twitter:image" content="https://example.org/share.jpg"'),'Social image is emitted');
assertContent(!str_contains($head,'<script>bad()'),'Verification tokens cannot inject scripts');
assertContent(!str_contains($head,'PRIVATE_TEST_VALUE'),'Private settings excluded from public metadata');
$settings['seo_social_title']='';$settings['seo_social_image']='javascript:alert(1)';
ob_start();include __DIR__.'/../includes/seo_head.php';$fallback=ob_get_clean();
assertContent(str_contains($fallback,'property="og:title" content="Venue search title"'),'Blank social title falls back');
assertContent(!str_contains($fallback,'javascript:'),'Invalid social image falls back');
$custom=$settings+['copy_spaces_description'=>'Unique courtyard celebrations'];
$custom['copy_spaces_description']='Unique courtyard celebrations';$custom['seo_keywords']='Unique courtyard celebrations';
assertContent(seoAudit($custom,[],[],[])['keywords'][0]['in_content'],'SEO audit sees edited public copy');
echo "Content and metadata tests passed.\n";
