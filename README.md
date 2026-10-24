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
