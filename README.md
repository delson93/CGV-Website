# Charles Gala Venue — Production PHP 8 & MySQL Website + Admin CMS

**GitHub Repository:** `https://github.com/delson93/CGV-Website.git`  
**Location:** Kulur, Melkoppala, Mangalore 575013 (`WRJM+V4 Mangaluru, Karnataka, India`)  
**Official Email:** `info@charlesgala.in`

## Production Architecture Highlights

1. **100% MySQL-Driven (`charles_gala_db`)**:
   - Auto-provisioning MySQL PDO connection in `config/db.php` + interactive web installer at `install.php`.
   - 7 relational InnoDB tables in `database.sql`: `admins`, `admin_sessions`, `site_settings`, `offerings`, `gallery_items`, `about_section`, and `seo_audit_logs`.
   - Full JSON REST API at `api/mysql_api.php`.
2. **Production Admin Authentication (`admin/login.php`)**:
   - Uses native PHP `password_verify()` against Bcrypt hashes in the MySQL `admins` table (`$2y$10$...`), CSRF protection, brute-force account lockout after 5 failed attempts, and MySQL-backed session validation in `admin_sessions`.
   - Change Admin Username & Password anytime inside the Admin Dashboard.
3. **Strict Call & WhatsApp Number Privacy**:
   - The phone/WhatsApp number is **never** displayed as visible text on the public website.
   - Clicking any **WhatsApp icon** (or "Book Now" button) opens the interactive WhatsApp Message composer / redirect.
   - Clicking any **Call icon** opens the interactive Voice Call dialer / `tel:` action.

## Green and gold theme, video and SEO dashboard

The public page now follows the supplied dark green and gold reference, with arched venue photographs, animated headings, scroll entrances, a gallery dialog and an optional muted YouTube background. All content is server rendered. Motion can be paused; reduced-motion and data-saving preferences suppress automatic video loading. The hero photograph remains available when YouTube is blocked, unavailable or not permitted to autoplay.

Edit the video URL at **Admin → Homepage design & background**. The initial URL is `https://www.youtube.com/watch?v=zVg8op029Mg`. Leave it blank for an image-only background. YouTube's own availability and embed restrictions apply. Upload landscape photographs through the existing CMS.

### SEO workflow

1. Open `/admin/seo.php` through the content manager. There are no public admin or sitemap links.
2. Configure a concise title, description, exact HTTPS homepage canonical, and comma-separated target phrases. Existing metadata is retained until saved. Keywords are an editorial planning aid, not a promise of ranking improvement.
3. Add factual visitor questions. The homepage and FAQ structured data use the same saved answers. Clear both fields to remove a question. No unsupported reviews, prices or ratings are generated.
4. Run an audit before editing content. Apply recommendations in the existing content manager, then run another audit. Select any two available reports to compare scores, checks and metadata. The latest 200 database records are considered; legacy synthetic reports are excluded.
5. Download a JSON report or use **Print / save PDF** to share it manually. Reports require an authenticated administrator; no public report URLs are created.

Scores are versioned weighted editorial checklists. On-page weights total 100: title 15, description 15, canonical 10, heading 10, content 15, gallery captions 10, keyword coverage 10, contact information 15. AI discovery weights total 100: identity 25, service descriptions 25, direct answers 25, coordinates 15, official profiles 10. Length and exact-phrase checks are heuristics. They do not measure actual search rankings, crawling, indexing, traffic, Core Web Vitals or AI citations. Use Search Console and PageSpeed Insights for those separate measurements. AI discovery work follows ordinary accessible, factual SEO practices; inclusion in AI responses is not guaranteed.

### Optional Gemini insights

Set `GEMINI_API_KEY` in the PHP server environment, or save a key in the SEO dashboard. An environment key takes precedence. Saved keys are write-only in the UI and never included in API bootstrap responses, audit snapshots, HTML or downloads. Protect database backups because a database-stored key must remain readable by the server. Blank preserves the saved key; the removal checkbox deletes it. Model defaults to `gemini-2.5-flash` and is editable for models available to your account.

Selecting **Include Gemini insights** sends only the report's public content, metadata, target phrases and check evidence to Google's `generateContent` API, using a server-side API-key header. PHP cURL, outbound HTTPS, a valid model and quota are required. Provider charges may apply. Results are escaped text suggestions requiring review; they never write metadata or determine scores. Failures are recorded as unavailable while the local report is retained. The old automatic writer endpoint now returns HTTP 410 after authentication.

### Deployment and sitemap routing

Deploy the full change on the existing PHP 8 / MySQL host. No Node.js or PostgreSQL migration is required. Existing `seo_audit_logs` tables are reused with versioned JSON in `summary`; existing reports and settings are not migrated or overwritten. Do not rerun `database.sql` on an existing installation because its original seed statements update existing settings.

Apache: the included `.htaccess` rewrites `/sitemap.xml` to `sitemap.php`; enable `mod_rewrite` and the appropriate `AllowOverride`. Nginx: add this rule inside the existing server block, before any catch-all route, and retain your existing PHP-FPM handler:

```nginx
location = /sitemap.xml {
    rewrite ^ /sitemap.php last;
}
```

Adjust these paths for a subdirectory installation. The repository also includes a static `sitemap.xml` fallback. Saving SEO settings refreshes the fallback and `robots.txt` atomically when the application root is writable. If writes fail, the dashboard shows a warning; configure dynamic routing and update `robots.txt` during deployment. The canonical must match the deployed URL. The sitemap contains the one public homepage, without fragment URLs or admin routes. Submit `https://charlesgala.in/sitemap.xml` in Search Console after deployment and verify it returns XML with the intended canonical. Robots exclusions are crawling guidance, not access controls.

The generic authenticated settings API now requires a POST CSRF token and no longer accepts SEO scores, metadata or API keys; use the SEO dashboard for those changes.

### Validation

Run `php tests/seo_test.php` and PHP syntax checks before deployment. The implementation was checked using PHP WebAssembly, including deterministic scores and deltas, URL validation, keyword coverage, secret exclusion, FAQ parsing, no-key Gemini behavior and sitemap XML. Isolated SQLite fixtures additionally exercised report persistence, comparison rendering, settings updates, CSRF rejection, escaped output and key preservation. These fixtures are not production database tests. Live MySQL, Apache/Nginx rewrite behavior, real Gemini responses and browser visual QA require verification on the staging host; browser preview was unavailable in the editing environment.
