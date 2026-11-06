<?php
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/seo.php';
requireAdminAuth();
require_once __DIR__.'/layout.php';
header('Cache-Control: no-store'); header('X-Robots-Tag: noindex, nofollow');
function h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
$pdo=getDB(); $notice=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!verifyCsrfToken($_POST['csrf_token']??'')) { http_response_code(403); $error='Your session token expired. Reload this page and try again.'; }
    else try {
        if(($_POST['action']??'')==='save') {
            $canonical=trim($_POST['seo_canonical_url']??'');
            if(!$canonical || rtrim($canonical,'/')!==rtrim(seoCanonical(['seo_canonical_url'=>$canonical]),'/')) throw new RuntimeException('Enter a valid HTTPS canonical URL without credentials, query parameters or fragments.');
            $model=trim($_POST['gemini_model']??'') ?: 'gemini-2.5-flash';
            if(!preg_match('/^[a-zA-Z0-9._-]{1,100}$/D',$model)) throw new RuntimeException('Enter a valid Gemini model identifier.');
            $faqs=[]; for($i=0;$i<6;$i++) {
                $q=trim((string)($_POST['question'][$i]??'')); $a=trim((string)($_POST['answer'][$i]??''));
                if(($q==='')!==($a==='')) throw new RuntimeException('Each FAQ needs both a question and an answer.');
                if($q && $a) $faqs[]=['question'=>substr($q,0,250),'answer'=>substr($a,0,1500)];
            }
            $socialImage=trim((string)($_POST['seo_social_image']??''));
            if ($socialImage !== '' && (!filter_var($socialImage,FILTER_VALIDATE_URL) || parse_url($socialImage,PHP_URL_SCHEME)!=='https' || parse_url($socialImage,PHP_URL_USER)!==null || parse_url($socialImage,PHP_URL_PASS)!==null)) throw new RuntimeException('Use an HTTPS social image URL without credentials.');
            $pdo->beginTransaction();
            foreach(['seo_social_title'=>255,'seo_social_description'=>1000,'seo_social_image'=>2000,'seo_social_image_alt'=>300,'seo_google_verification'=>250,'seo_bing_verification'=>250] as $key=>$limit) updateSiteSetting($key,substr(trim((string)($_POST[$key]??'')),0,$limit));
            foreach(['seo_meta_title'=>255,'seo_meta_description'=>1000] as $key=>$limit) updateSiteSetting($key,substr(trim((string)($_POST[$key]??'')),0,$limit));
            updateSiteSetting('seo_keywords',implode(', ',seoKeywords((string)($_POST['seo_keywords']??''))));
            updateSiteSetting('seo_canonical_url',seoCanonical(['seo_canonical_url'=>$canonical]));
            updateSiteSetting('gemini_model',$model);
            updateSiteSetting('seo_faq_json',json_encode($faqs,JSON_UNESCAPED_UNICODE));
            if(isset($_POST['remove_key'])) updateSiteSetting('gemini_api_key','');
            elseif(trim($_POST['gemini_api_key']??'')!=='') {
                $key=trim($_POST['gemini_api_key']);
                if(!preg_match('/^[a-zA-Z0-9_-]{10,200}$/D',$key)) throw new RuntimeException('The API key format is invalid.');
                updateSiteSetting('gemini_api_key',$key);
            }
            $pdo->commit();
            $notice='SEO settings saved. Run an audit to record the new results.';
            // Static fallback for servers without Apache rewriting. The PHP route remains authoritative.
            $root=dirname(__DIR__);
            $files=['sitemap.xml'=>seoSitemap(seoCanonical(['seo_canonical_url'=>$canonical])), 'robots.txt'=>"User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\nDisallow: /config/\nDisallow: /install.php\nSitemap: ".seoCanonical(['seo_canonical_url'=>$canonical])."sitemap.xml\n"];
            foreach($files as $file=>$content) {
                $temp=@tempnam($root,'seo-');
                if(!$temp || @file_put_contents($temp,$content)===false || !@chmod($temp,0644) || !@rename($temp,$root.'/'.$file)) {
                    if($temp && is_file($temp)) @unlink($temp);
                    $notice.=' Could not refresh '.$file.'. Configure the documented dynamic route or update this file during deployment.';
                }
            }
        }
        if(($_POST['action']??'')==='audit') {
            $last=(int)($_SESSION['seo_last_audit']??0);
            if(time()-$last<20) throw new RuntimeException('Please wait 20 seconds between reports.');
            $_SESSION['seo_last_audit']=time();
            $s=getSiteSettings()+homepageDefaults();
            $report=seoAudit($s,getAboutSection()?:[],getOfferings(true),getGalleryItems());
            if(isset($_POST['use_gemini'])) $report['gemini']=seoGemini($report,$s);
            $stmt=$pdo->prepare('INSERT INTO seo_audit_logs (score,meta_title,meta_description,keywords,summary) VALUES (?,?,?,?,?)');
            $stmt->execute([$report['scores']['seo'],$report['metadata']['title'],$report['metadata']['description'],implode(', ',$report['metadata']['keywords']),json_encode($report,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
            header('Location: seo.php?report='.(int)$pdo->lastInsertId()); exit;
        }
    } catch(Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $error=$e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'The report could not be saved. Check the database connection and server logs.';
    }
}
$s=getSiteSettings()+homepageDefaults(); $faqs=seoFaqs($s);
$reports=[];
foreach($pdo->query('SELECT id,summary,created_at FROM seo_audit_logs ORDER BY id DESC LIMIT 200') as $row) {
    $r=json_decode($row['summary'],true);
    if(is_array($r) && ($r['version']??null)===1 && isset($r['scores'],$r['checks'])) { $r['id']=(int)$row['id']; $reports[$row['id']]=$r; }
}
$selected=$reports[(int)($_GET['report']??0)]??(reset($reports)?:null);
$comparison=$reports[(int)($_GET['compare']??0)]??null;
if(isset($_GET['download'])) {
    $download=$reports[(int)$_GET['download']]??null;
    if(!$download) { http_response_code(404); exit('Report not found in the latest 200 records.'); }
    header('Content-Type: application/json; charset=utf-8'); header('Content-Disposition: attachment; filename="cgv-seo-report-'.(int)$download['id'].'.json"');
    echo json_encode($download,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit;
}
$csrf=generateCsrfToken();
?>
<?php adminStart('seo','SEO & discovery',$s); ?>
<p class="page-intro">Manage search previews, factual venue information and discovery reports in one place.</p>
<?php if($notice): ?><p class="notice" role="status"><?= h($notice) ?></p><?php endif; ?><?php if($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
<section class="card controls"><h2>Search settings</h2><form method="post"><input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="save">
<div class="columns"><label>Search title<input name="seo_meta_title" maxlength="255" required value="<?= h($s['seo_meta_title']??'') ?>"></label><label>Canonical homepage URL<input type="url" name="seo_canonical_url" required value="<?= h(seoCanonical($s)) ?>"></label></div>
<label>Search description<textarea name="seo_meta_description" maxlength="1000" required rows="3"><?= h($s['seo_meta_description']??'') ?></textarea></label>
<label>Target keywords and phrases<textarea name="seo_keywords" rows="2" maxlength="3000"><?= h($s['seo_keywords']??'') ?></textarea></label><p class="hint">Separate phrases with commas. Up to 30 phrases, 100 characters each. Keywords guide content reviews; a meta keywords tag does not improve Google rankings.</p>
<h3>Visitor questions</h3><p>Publish accurate answers visitors need. These appear on the homepage and in matching structured data. Leave unused pairs empty.</p>
<?php for($i=0;$i<6;$i++): ?><div class="columns"><label>Question <?= $i+1 ?><input name="question[<?= $i ?>]" maxlength="250" value="<?= h($faqs[$i]['question']??'') ?>"></label><label>Answer <?= $i+1 ?><textarea name="answer[<?= $i ?>]" rows="2" maxlength="1500"><?= h($faqs[$i]['answer']??'') ?></textarea></label></div><?php endfor; ?>
<div class="search-preview" aria-label="Search result preview"><small id="preview-url"><?= h(seoCanonical($s)) ?></small><strong id="preview-title"><?= h($s['seo_meta_title']??'') ?></strong><p id="preview-description"><?= h($s['seo_meta_description']??'') ?></p></div><p class="hint">Illustrative search preview. Google may choose a different title or snippet. Target keywords are used for your editorial reports; Google does not use the meta keywords tag for ranking.</p>
<h3>Social sharing metadata</h3><p class="hint">Used by Open Graph and Twitter cards. Leave blank to use the search title, description and homepage photograph.</p><div class="columns"><?php foreach(['seo_social_title'=>'Social title','seo_social_description'=>'Social description','seo_social_image'=>'Social image HTTPS URL','seo_social_image_alt'=>'Social image description'] as $key=>$label): ?><label><?= h($label) ?><input name="<?= h($key) ?>" value="<?= h($s[$key]??'') ?>" <?= $key==='seo_social_image'?'type="url"':'' ?>></label><?php endforeach; ?></div>
<h3>Search engine verification</h3><p class="hint">Paste only the content value from your verification tag. These fields emit safe meta tags, not arbitrary HTML.</p><div class="columns"><?php foreach(['seo_google_verification'=>'Google Search Console token','seo_bing_verification'=>'Bing Webmaster Tools token'] as $key=>$label): ?><label><?= h($label) ?><input name="<?= h($key) ?>" value="<?= h($s[$key]??'') ?>" maxlength="250"></label><?php endforeach; ?></div>
<details><summary>Gemini configuration</summary><p>Optional. The key stays on the server and is never included in public responses or reports. Prefer the GEMINI_API_KEY server environment variable.</p><p>Key status: <?= getenv('GEMINI_API_KEY')?'Configured through the server environment':(!empty($s['gemini_api_key'])?'Stored server-side':'Not configured') ?>.</p><div class="columns"><label>Replace stored API key<input type="password" name="gemini_api_key" autocomplete="new-password" placeholder="Leave blank to preserve existing key"></label><label>Gemini model<input name="gemini_model" value="<?= h($s['gemini_model']??'gemini-2.5-flash') ?>" required></label></div><label class="check"><input type="checkbox" name="remove_key"> Remove stored key (does not remove an environment key)</label></details>
<button>Save settings</button></form></section>
<section class="card controls"><h2>Create a report</h2><p><a href="../sitemap.xml" target="_blank" rel="noopener">Review sitemap.xml ↗</a>. Submit this URL in your verified Search Console property after deployment.</p><p>Scores reflect a weighted editorial checklist of configured content. They do not measure Google rankings, live indexing, traffic, Core Web Vitals or AI citations. Check indexing in Search Console and performance with PageSpeed Insights separately.</p><form method="post"><input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="audit"><label class="check"><input type="checkbox" name="use_gemini"> Include Gemini insights</label><p class="hint">When selected, the report's public venue content, metadata, target keywords and checks are sent to Google. Gemini suggestions require review and never change your content automatically. Provider usage charges may apply.</p><button>Run audit and save report</button></form></section>
<section class="card"><h2>Report history & comparison</h2><p class="hint">Shows versioned reports from the latest 200 records. Earlier synthetic scores are excluded.</p>
<?php if(!$selected): ?><p>No verified reports yet. Run an audit to create your baseline.</p><?php else: ?>
<form method="get" class="controls"><div class="columns"><?php foreach(['report'=>'Report to review','compare'=>'Compare with'] as $field=>$label): ?><label><?= $label ?><select name="<?= $field ?>"><?php if($field==='compare'): ?><option value="0">No comparison</option><?php endif; ?><?php foreach($reports as $r): ?><option value="<?= $r['id'] ?>" <?= ($field==='report'?$selected['id']:($comparison['id']??0))===$r['id']?'selected':'' ?>>#<?= $r['id'] ?> · <?= h($r['created_at']) ?> · SEO <?= $r['scores']['seo'] ?>/100</option><?php endforeach; ?></select></label><?php endforeach; ?></div><button>Review reports</button></form>
<h3>Report #<?= $selected['id'] ?> <small><?= h($selected['created_at']) ?></small></h3><div class="columns scores"><?php foreach(['seo'=>'On-page readiness','ai'=>'AI discovery readiness'] as $group=>$label): ?><div><strong><?= (int)$selected['scores'][$group] ?><small>/100</small></strong><p><?= $label ?></p><?php if($comparison): ?><p>Compared with #<?= $comparison['id'] ?>: <?= sprintf('%+d',$selected['scores'][$group]-$comparison['scores'][$group]) ?> points</p><?php endif; ?></div><?php endforeach; ?></div>
<p><?= h($selected['limits']) ?></p><p class="controls"><a href="?download=<?= $selected['id'] ?>">Download JSON report</a> · <button type="button" onclick="window.print()">Print / save PDF</button></p>
<?php if($comparison): ?><h3>Metadata changes</h3><div class="table-wrap"><table><tr><th>Field</th><th>Comparison #<?= $comparison['id'] ?></th><th>Selected #<?= $selected['id'] ?></th></tr><?php foreach(['title','description','canonical','keywords'] as $key): ?><tr><th><?= h($key) ?></th><td><?= h(is_array($comparison['metadata'][$key])?implode(', ',$comparison['metadata'][$key]):$comparison['metadata'][$key]) ?></td><td><?= h(is_array($selected['metadata'][$key])?implode(', ',$selected['metadata'][$key]):$selected['metadata'][$key]) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
<h3>Checks and recommended actions</h3><div class="table-wrap"><table><thead><tr><th>Check</th><th>Result</th><?php if($comparison): ?><th>Previous</th><?php endif; ?><th>Evidence / next action</th></tr></thead><tbody>
<?php $old=[]; if($comparison) foreach($comparison['checks'] as $c) $old[$c['id']]=$c['pass']; foreach($selected['checks'] as $c): ?><tr><th><?= h($c['label']) ?><small><?= h(strtoupper($c['group'])) ?> · <?= (int)$c['weight'] ?> points</small></th><td><?= $c['pass']?'Pass':'Needs attention' ?></td><?php if($comparison): ?><td><?= isset($old[$c['id']])?($old[$c['id']]?'Pass':'Needs attention'):'Not assessed' ?></td><?php endif; ?><td><?= h($c['evidence']) ?><?php if(!$c['pass']): ?><p><?= h($c['recommendation']) ?></p><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<h3>Keyword coverage</h3><p>Exact phrase matching, case insensitive. This is an editorial aid, not a ranking signal measurement.</p><div class="table-wrap"><table><tr><th>Phrase</th><th>Title</th><th>Description</th><th>Visible content</th></tr><?php foreach($selected['keywords'] as $kw): ?><tr><td><?= h($kw['keyword']) ?></td><?php foreach(['in_title','in_description','in_content'] as $key): ?><td><?= $kw[$key]?'Present':'Not found' ?></td><?php endforeach; ?></tr><?php endforeach; ?></table></div>
<h3>Gemini insights</h3><p>Status: <?= h($selected['gemini']['status']) ?></p><div class="insights"><?= h($selected['gemini']['text'] ?: 'Gemini was not requested for this report.') ?></div><p class="hint">Review suggestions against real venue details before applying them in the content manager.</p>
<?php endif; ?></section><?php adminEnd(); ?>
