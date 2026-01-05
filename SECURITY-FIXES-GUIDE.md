# Security Fixes - Quick Implementation Guide

**Date**: January 5, 2026  
**Priority**: CRITICAL - Implement ASAP  

---

## 🔴 CRITICAL FIX #1: Command Injection in CV Generator

**FILE**: `api/generate-cv.php` (Line 440)

**CURRENT CODE** (VULNERABLE):
```php
exec("wkhtmltopdf $tempHtml $tempPdf");
```

**FIXED CODE**:
```php
exec(sprintf(
    "wkhtmltopdf %s %s 2>&1",
    escapeshellarg($tempHtml),
    escapeshellarg($tempPdf)
), $output, $return_var);

if ($return_var !== 0) {
    error_log("wkhtmltopdf failed: " . implode("\n", $output));
    throw new Exception("PDF generation failed");
}
```

---

## 🔴 CRITICAL FIX #2: Add CSRF Protection

### Step 1: Create CSRF Helper (Already exists in `config/session.php` ✅)

Functions already available:
- `generateCSRFToken()` ✅
- `validateCSRFToken($token)` ✅

### Step 2: Add CSRF Tokens to Forms

**PATTERN FOR ALL FORMS**:
```php
<form method="POST">
    <!-- Add this hidden field to EVERY form -->
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <!-- ...rest of form fields... -->
</form>
```

**PATTERN FOR AJAX REQUESTS**:
```javascript
const formData = new FormData();
formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
formData.append('other_field', value);

fetch('api/endpoint.php', {
    method: 'POST',
    body: formData
});
```

### Step 3: Validate CSRF Tokens in Handlers

**PATTERN FOR ALL POST HANDLERS**:
```php
// Add this at the top of EVERY POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh and try again.']);
        exit;
    }
}

// Continue with normal processing...
```

### Files That MUST Be Updated:

1. **`pages/user/cv-manager.php`** - CV upload form
2. **`pages/company/post-job.php`** - Job posting form
3. **`pages/jobs/apply.php`** - Job application form
4. **`api/upload-profile-picture.php`** - Profile upload
5. **`api/jobs.php`** - Save/unsave jobs
6. **`api/payment.php`** - Payment initialization
7. **`api/private-job-offers.php`** - Offer creation
8. **`api/interview.php`** - Interview scheduling
9. **`api/notifications.php`** - Notification actions
10. **`api/admin-actions.php`** - All admin actions

---

## 🔴 CRITICAL FIX #3: Fix Webhook Signature Verification

**FILE**: `api/flutterwave-webhook.php` (Line 20)

**CURRENT CODE** (VULNERABLE):
```php
if (empty($signature) || $signature !== FLUTTERWAVE_SECRET_KEY) {
    error_log("Flutterwave Webhook: Invalid signature");
    http_response_code(401);
    exit('Invalid signature');
}
```

**FIXED CODE**:
```php
if (empty($signature) || !hash_equals(FLUTTERWAVE_SECRET_KEY, $signature)) {
    error_log("Flutterwave Webhook: Invalid signature received");
    http_response_code(401);
    exit('Invalid signature');
}
```

---

## 🟡 HIGH PRIORITY FIX #1: Open Redirect Protection

**FILE**: `includes/functions.php` (Line 121)

**CURRENT CODE** (VULNERABLE):
```php
function redirect($url) {
    header('Location: ' . $url);
    exit();
}
```

**FIXED CODE**:
```php
function redirect($url) {
    // Validate URL is internal only
    $parsed = parse_url($url);
    
    // Check if URL has a host component
    if (isset($parsed['host'])) {
        // Get current host
        $current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Allow only same domain or localhost
        $allowed_hosts = [$current_host, 'localhost', 'localhost:80', 'localhost:443'];
        
        if (!in_array($parsed['host'], $allowed_hosts)) {
            error_log("Blocked redirect to external URL: $url from IP: " . getUserIP());
            // Redirect to safe default instead
            $url = '/findajob/index.php';
        }
    }
    
    header('Location: ' . $url);
    exit();
}
```

---

## 🟡 HIGH PRIORITY FIX #2: Fix XSS in JavaScript Context

**FILE**: `pages/services/cv-creator.php` (Lines 1562-1564)

**CURRENT CODE** (VULNERABLE):
```php
document.getElementById('firstName').value = '<?php echo addslashes($user["first_name"]); ?>';
document.getElementById('lastName').value = '<?php echo addslashes($user["last_name"]); ?>';
document.getElementById('email').value = '<?php echo addslashes($user["email"]); ?>';
```

**FIXED CODE**:
```php
document.getElementById('firstName').value = <?php echo json_encode($user["first_name"], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
document.getElementById('lastName').value = <?php echo json_encode($user["last_name"], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
document.getElementById('email').value = <?php echo json_encode($user["email"], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
```

**ALSO FIX**: `admin/view-job-seeker.php` (Lines 726, 730)

**CURRENT CODE**:
```php
alert('<?= addslashes($success) ?>');
alert('Error: <?= addslashes($error) ?>');
```

**FIXED CODE**:
```php
alert(<?php echo json_encode($success, JSON_HEX_TAG | JSON_HEX_AMP); ?>);
alert('Error: ' + <?php echo json_encode($error, JSON_HEX_TAG | JSON_HEX_AMP); ?>);
```

---

## 🟡 HIGH PRIORITY FIX #3: Add Rate Limiting

### Step 1: Create Rate Limits Table

**SQL**: Add to `database/schema.sql`
```sql
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_action (user_id, action, created_at),
    INDEX idx_ip_action (ip_address, action, created_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-cleanup old records (older than 24 hours)
CREATE EVENT IF NOT EXISTS cleanup_rate_limits
ON SCHEDULE EVERY 1 HOUR
DO DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

**RUN**:
```bash
cd /opt/lampp/bin
./mysql -u root findajob_ng < /opt/lampp/htdocs/findajob/database/add-rate-limits.sql
```

### Step 2: Add Rate Limit Helper Function

**FILE**: `includes/functions.php` (Add at end)

```php
/**
 * Check if action is rate limited
 * @param string $action Action identifier (e.g., 'nin_verify', 'phone_otp')
 * @param int $limit Maximum attempts allowed
 * @param int $window Time window in seconds (default: 1 hour)
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => timestamp]
 */
function checkRateLimit($action, $limit = 10, $window = 3600) {
    global $pdo;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip_address = getUserIP();
    
    // Count recent attempts
    if ($user_id) {
        // For logged-in users, check by user_id
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, 
                   MIN(created_at) as oldest,
                   MAX(created_at) as newest
            FROM rate_limits 
            WHERE user_id = ? 
            AND action = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$user_id, $action, $window]);
    } else {
        // For anonymous users, check by IP
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count,
                   MIN(created_at) as oldest,
                   MAX(created_at) as newest
            FROM rate_limits 
            WHERE ip_address = ? 
            AND action = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip_address, $action, $window]);
    }
    
    $result = $stmt->fetch();
    $count = (int)$result['count'];
    
    if ($count >= $limit) {
        // Calculate when limit resets
        $oldest_timestamp = strtotime($result['oldest']);
        $reset_at = $oldest_timestamp + $window;
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset_at' => $reset_at,
            'retry_after' => $reset_at - time()
        ];
    }
    
    // Log this attempt
    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (user_id, ip_address, action, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $ip_address, $action]);
    
    return [
        'allowed' => true,
        'remaining' => $limit - $count - 1,
        'reset_at' => time() + $window,
        'retry_after' => 0
    ];
}
```

### Step 3: Apply Rate Limiting to Critical Endpoints

**PATTERN**:
```php
// At the top of API file, before processing
$rate_check = checkRateLimit('action_name', $limit, $window);

if (!$rate_check['allowed']) {
    http_response_code(429); // Too Many Requests
    echo json_encode([
        'success' => false,
        'error' => 'Rate limit exceeded. Please try again later.',
        'retry_after' => $rate_check['retry_after']
    ]);
    exit;
}

// Continue with normal processing...
```

**APPLY TO**:

1. **`api/verify-nin.php`** (MOST CRITICAL - costs money!)
```php
// Add before line 50
$rate_check = checkRateLimit('nin_verify', 3, 3600); // 3 per hour
if (!$rate_check['allowed']) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'NIN verification rate limit exceeded. Maximum 3 attempts per hour.',
        'retry_after' => $rate_check['retry_after']
    ]);
    exit;
}
```

2. **`api/verify-phone.php`** - OTP generation
```php
$rate_check = checkRateLimit('phone_otp', 5, 3600); // 5 per hour
```

3. **`api/payment.php`** - Payment initialization
```php
$rate_check = checkRateLimit('payment_init', 10, 3600); // 10 per hour
```

4. **`api/upload-profile-picture.php`** - File uploads
```php
$rate_check = checkRateLimit('profile_upload', 10, 3600); // 10 per hour
```

---

## 🟠 MEDIUM PRIORITY FIX #1: Fix File Permissions

**FILE**: `api/upload-profile-picture.php` (Line 59)

**CURRENT CODE**:
```php
mkdir($uploadDir, 0777, true);
```

**FIXED CODE**:
```php
mkdir($uploadDir, 0755, true);

// After file upload, also set file permissions
chmod($filePath, 0644);
```

**ALSO FIX**: Check all `mkdir()` calls in codebase

---

## 🟠 MEDIUM PRIORITY FIX #2: Add Security Headers

**FILE**: `includes/header.php` (Add after opening `<php` tag)

```php
<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Only send CSP if not in development mode
if (!defined('DEV_MODE') || !DEV_MODE) {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://checkout.flutterwave.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https://api.flutterwave.com;");
}
?>
```

---

## 🟠 MEDIUM PRIORITY FIX #3: Protect Upload Directories

**CREATE FILE**: `uploads/.htaccess`

```apache
# Prevent PHP execution in upload directories
<FilesMatch "\.(php|phtml|php3|php4|php5|phps|inc)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Allow only specific file types
<FilesMatch "\.(jpg|jpeg|png|gif|pdf|doc|docx|webp)$">
    Order allow,deny
    Allow from all
</FilesMatch>
```

**COPY TO**:
- `uploads/cvs/.htaccess`
- `uploads/profile-pictures/.htaccess`
- `uploads/logos/.htaccess`

---

## 🟠 MEDIUM PRIORITY FIX #4: Production Environment Detection

**FILE**: `config/constants.php` (Line 9)

**CURRENT CODE**:
```php
define('DEV_MODE', true); // Set to false in production
```

**IMPROVED CODE**:
```php
// Auto-detect production environment
$is_localhost = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '192.168.') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '10.0.') === 0
);

define('DEV_MODE', $is_localhost);

// Can also use environment variable override:
// define('DEV_MODE', getenv('APP_ENV') === 'development');
```

---

## 📋 IMPLEMENTATION CHECKLIST

### Day 1 - Critical Fixes:
- [ ] Fix command injection (`api/generate-cv.php`)
- [ ] Fix webhook signature (`api/flutterwave-webhook.php`)
- [ ] Test both fixes thoroughly

### Day 2-3 - CSRF Protection:
- [ ] Add CSRF tokens to all 10 identified forms
- [ ] Add validation to all POST handlers
- [ ] Test all forms with invalid/missing tokens

### Day 4 - High Priority:
- [ ] Fix open redirect (`includes/functions.php`)
- [ ] Fix XSS in JavaScript contexts (2 files)
- [ ] Test redirect validation
- [ ] Test JavaScript value injection

### Day 5-6 - Rate Limiting:
- [ ] Create `rate_limits` table
- [ ] Add `checkRateLimit()` function
- [ ] Apply to 4 critical endpoints
- [ ] Test rate limit enforcement

### Day 7 - Medium Priority:
- [ ] Fix file permissions (all upload directories)
- [ ] Add security headers (`includes/header.php`)
- [ ] Create `.htaccess` for upload directories
- [ ] Auto-detect production environment

### Day 8 - Testing:
- [ ] Full security testing
- [ ] Penetration testing (if possible)
- [ ] Code review
- [ ] Documentation update

---

## 🧪 TESTING COMMANDS

### Test Command Injection Fix:
```bash
# Before fix: This would be dangerous
# After fix: Should be safely escaped
```

### Test CSRF Protection:
```bash
# Try submitting form without CSRF token - should fail
curl -X POST http://localhost/findajob/api/jobs.php \
  -d "action=save&job_id=1" \
  -b "PHPSESSID=your_session_id"
```

### Test Rate Limiting:
```bash
# Send 10 requests rapidly - last ones should be blocked
for i in {1..10}; do
  curl http://localhost/findajob/api/verify-nin.php -X POST -d "nin=12345678901"
  sleep 0.5
done
```

---

## 📞 GET HELP

If you need assistance implementing these fixes:
1. Review the full `SECURITY-AUDIT-REPORT.md`
2. Test each fix in development first
3. Use version control (git) before making changes
4. Keep backups of original files

---

**Priority**: 🔴 CRITICAL  
**Timeline**: Complete within 7-8 days  
**Next Steps**: Start with Day 1 critical fixes immediately
