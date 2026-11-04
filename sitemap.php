<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/seo.php';
header('Content-Type: application/xml; charset=utf-8');
echo seoSitemap(seoCanonical(getSiteSettings()));
