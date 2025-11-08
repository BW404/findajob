# FindAJob Nigeria - AI Coding Agent Instructions

## Project Overview
FindAJob is a Progressive Web App (PWA) job platform tailored for the Nigerian market (ages 18-35). Built on XAMPP/PHP stack with plans for Android APK deployment.

**Tech Stack**: PHP 8.x, MySQL/MariaDB, Vanilla JavaScript, PWA (Service Worker), CSS3
**Database**: `findajob_ng` (24 tables, utf8mb4_unicode_ci)
**Deployment**: XAMPP local development (`http://localhost/findajob`)

## Architecture Patterns

### File Organization & Require Path Convention
All PHP files follow a strict layered architecture with relative paths from their location:

```php
// Standard page header (adjust ../ depth based on location)
require_once '../../config/database.php';     // PDO connection ($pdo)
require_once '../../config/session.php';      // Session helpers
require_once '../../config/constants.php';    // App-wide constants
require_once '../../includes/functions.php';  // Utility functions
```

**Critical**: Paths are relative to the file location, not the web root. `pages/user/` uses `../../`, `api/` uses `../`

### Dual User System
Two distinct user types with separate dashboards and workflows:

1. **Job Seekers** (`user_type='job_seeker'`)
   - Dashboard: `pages/user/dashboard.php`
   - Profile: `job_seeker_profiles` table
   - Features: CV management, job applications, saved jobs, AI recommendations

2. **Employers** (`user_type='employer'`)
   - Dashboard: `pages/company/dashboard.php`
   - Profile: `employer_profiles` table
   - Features: Job posting, applicant management, CV search, analytics

**Session helpers** (from `config/session.php`): `isLoggedIn()`, `isJobSeeker()`, `isEmployer()`, `getCurrentUserId()`

### API Pattern - Class-Based vs Functional
The codebase uses **two different API patterns**:

1. **Class-based** (newer, preferred): `api/auth.php`
   ```php
   class AuthAPI {
       public function __construct($pdo) { ... }
       public function register($data) { ... }
   }
   ```

2. **Functional** (older): `api/locations.php`, `api/search.php`
   ```php
   function handleLocationRequest() { ... }
   ```

**When creating new APIs**: Use class-based pattern for complex logic, functional for simple endpoints.

### Database Access Pattern
Global `$pdo` object from `config/database.php` (PDO with prepared statements):

```php
// Correct: Use parameterized queries
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = ?");
$stmt->execute([$jobId, 'active']);
$job = $stmt->fetch();

// Never: Direct string concatenation (security risk)
```

**Critical**: All user input must use prepared statements. The codebase has zero raw SQL concatenation.

### Nigerian Localization
The platform is deeply integrated with Nigerian geography:

- **Tables**: `nigeria_states` (37 states), `nigeria_lgas` (774 LGAs)
- **Phone Format**: Validate for `0xxxxxxxxxx`, `234xxxxxxxxxx`, or `xxxxxxxxxx` formats
- **Currency**: Always Naira (₦). Use `formatNaira()` helper for display
- **NIN/BVN Verification**: Dojah API integration (`config/constants.php` - `DOJAH_*` constants)

### PWA Implementation
Full Progressive Web App with offline support:

- **Manifest**: `manifest.json` (theme color: #dc2626)
- **Service Worker**: `sw.js` (cache-first strategy, v1.0.0)
- **Bottom Navigation**: Context-aware for job seekers vs employers (all pages include this)
- **Icons**: SVG-based for iOS/Android compatibility (`assets/images/icons/`)

**When adding new pages**: Include PWA meta tags and bottom navigation from `includes/header.php` and `includes/footer.php`

### Email System with Dev Mode
Development mode captures emails instead of sending (XAMPP-friendly):

```php
// config/constants.php
define('DEV_MODE', true);              // Set false in production
define('DEV_EMAIL_CAPTURE', true);     // Captures emails to logs/

// Email templates are in includes/email-notifications.php
// Uses inline CSS for email client compatibility
```

**HTML Email Templates**: 6 different templates (welcome, verification, password reset, application status, job alert, etc.) all use inline styles.

## Critical Features & Workflows

### CV System (3 Components)
1. **Upload CVs**: `pages/user/cv-manager.php` - Upload PDF/Word files, max 5MB
2. **Generate CVs**: `pages/services/cv-generator.php` - 6-step wizard with AI-powered suggestions
3. **CV Analytics**: `api/cv-analytics.php` - Track views/downloads per CV

**Storage**: Files in `uploads/cvs/{user_id}/`, database records in `user_cvs` table with `cv_data` JSON column

### Job Posting (3-Step Wizard)
Employer job posting at `pages/company/post-job.php`:
- Step 1: Job Details (title, category, type, location)
- Step 2: Requirements (education, experience, salary range)
- Step 3: Application Settings (Easy Apply, Manual Apply, or Both)

**Dual Application Methods**:
- `easy_apply=1`: One-click applications (uses uploaded CV)
- `external_url`: Redirects to company ATS
- Both can be enabled simultaneously

### Search & Filtering Architecture
Advanced search with 8+ filter dimensions:

```php
// Browse jobs: pages/jobs/browse.php
// URL params: keywords, location, category, job_type, experience_level, 
//             salary_min, salary_max, sort
// Real-time autocomplete: api/search.php (jobs, companies, locations, categories)
```

**SQL Pattern**: Dynamic WHERE clause building with prepared statement arrays (see `pages/jobs/browse.php` lines 50-150)

### Saved Jobs System
Optimistic UI pattern with database sync:

- **Table**: `saved_jobs` (user_id, job_id, unique constraint)
- **API**: `api/jobs.php` - POST with `action=save` or `action=unsave`
- **UI**: Heart icon toggle on job cards, instant visual feedback
- **Page**: `pages/user/saved-jobs.php` - Dedicated saved jobs view

## Development Patterns

### Error Handling & Logging
```php
// Production error handling
try {
    // Operation
} catch (Exception $e) {
    error_log("Context: " . $e->getMessage());  // Always log
    
    if (defined('DEV_MODE') && DEV_MODE) {
        return ['error' => $e->getMessage()];   // Dev: show details
    }
    return ['error' => 'Operation failed'];     // Prod: generic message
}
```

**Log Files**: `logs/` directory (create if missing)

### Nigerian States & LGAs
Always use the database tables, never hardcode:

```php
// Fetch states
$states = $pdo->query("SELECT * FROM nigeria_states ORDER BY name")->fetchAll();

// Fetch LGAs for a state
$stmt = $pdo->prepare("SELECT * FROM nigeria_lgas WHERE state_id = ? ORDER BY name");
$stmt->execute([$stateId]);
```

### Form Validation Pattern
Consistent validation approach across forms:

```php
// includes/validators.php has: isValidEmail(), isValidPhone(), isValidNIN()
// includes/functions.php has: sanitizeInput(), formatCurrency(), timeAgo()

// Always sanitize before display
$safe = sanitizeInput($_POST['input']);
echo htmlspecialchars($safe, ENT_QUOTES, 'UTF-8');
```

### CSS Architecture
Component-based CSS in `assets/css/main.css` (2700+ lines):

- **CSS Variables**: `--primary (#dc2626)`, `--primary-light`, `--primary-dark`, etc.
- **Mobile-first**: All components responsive by default
- **Animations**: `@keyframes slideInUp` for hero sections
- **Bottom Nav**: `.bottom-nav` with context-aware active states

**Color Palette**: Red primary (#dc2626), with amber, purple, teal accents for different sections

## Testing & Debugging

### XAMPP MySQL Access
```bash
# PowerShell commands for database access
cd E:\XAMPP\mysql\bin
.\mysql.exe -u root

USE findajob_ng;
SHOW TABLES;  # 24 tables
```

### Development Checklist
When creating new features:
1. ✅ Add session check (`isLoggedIn()`, role verification)
2. ✅ Use prepared statements for all queries
3. ✅ Include PWA meta tags and bottom navigation
4. ✅ Test with `DEV_MODE=true` first
5. ✅ Add error logging with context
6. ✅ Validate all user input (see `includes/validators.php`)
7. ✅ Use relative paths correctly (`../../config/...`)
8. ✅ Follow existing naming conventions (snake_case DB, camelCase JS)

### Common Pitfalls to Avoid
- ❌ Never use `../../` in API files (use `../`)
- ❌ Don't hardcode absolute URLs (use relative paths)
- ❌ Never mix employer/job seeker features in same file
- ❌ Don't forget CSRF tokens on forms (use `generateCSRFToken()`)
- ❌ Avoid `SELECT *` in production code (specify columns)

## Quick Reference

### Key Files
- **Entry**: `index.php` (redirects based on login state)
- **Auth**: `api/auth.php` (AuthAPI class - register, login, password reset)
- **Schema**: `database/schema.sql` (source of truth for structure)
- **Migrations**: `database/add-*.sql` (8 migration files - keep for audit trail)
- **Constants**: `config/constants.php` (all app-wide settings)
- **Utilities**: `includes/functions.php` (450+ lines of helpers)

### Database Tables (Most Important)
- `users` - Authentication (job seekers, employers, admins)
- `job_seeker_profiles` / `employer_profiles` - User data
- `jobs` - Job postings (status: draft, active, closed, expired)
- `job_applications` - Applications with CV references
- `user_cvs` - CV metadata and JSON data
- `saved_jobs` - User saved jobs
- `nigeria_states` / `nigeria_lgas` - Location data

### API Endpoints
All in `api/` directory, JSON responses:
- `auth.php` - Registration, login, email verification
- `jobs.php` - CRUD for jobs, save/unsave
- `generate-cv.php` - CV generation with templates
- `ai-job-recommendations.php` - ML-based job matching
- `verify-nin.php` - Dojah NIN/BVN verification
- `search.php` - Autocomplete for jobs, companies, locations

### Project Status
See `todo.md` for detailed feature roadmap. Currently 75% complete:
- ✅ Complete: Auth, DB, PWA, Jobs, CV System, Search, Applications
- 🟡 In Progress: Email system (55%), Payment integration
- 🔴 Not Started: Full payment system, BVN verification

---

**Remember**: This is a Nigerian-focused platform - always consider local context (currency, phone formats, geography, job market dynamics) when adding features.
