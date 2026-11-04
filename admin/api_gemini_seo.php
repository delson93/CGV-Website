<?php
require_once __DIR__.'/../includes/functions.php';
requireAdminAuth();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
http_response_code(410);
echo json_encode(['ok'=>false,'error'=>'The automatic metadata writer has been retired. Use the SEO dashboard at seo.php for saved audits and reviewed Gemini recommendations.']);
