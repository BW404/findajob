<?php
// App constants
define('SITE_NAME', 'FindAJob Nigeria');
define('SITE_URL', 'http://localhost/findajob');
define('SITE_EMAIL', 'noreply@findajob.ng');

// Development mode for XAMPP (works from any IP/domain - localhost, 192.168.x.x, etc.)
define('DEV_MODE', true); // Set to false in production
define('DEV_EMAIL_CAPTURE', true); // Capture emails instead of sending in development

// Office address
define('OFFICE_ADDRESS_LINE1', 'Unit 1, 29-31 Memorial Avenue');
define('OFFICE_ADDRESS_LINE2', 'Ingleburn, NSW 2565');
define('OFFICE_ADDRESS_COUNTRY', 'Australia');
define('OFFICE_EMAIL', 'info@findajob.ng');

// Colors
define('PRIMARY_COLOR', '#dc2626');
define('PRIMARY_LIGHT', '#fecaca');
define('PRIMARY_DARK', '#991b1b');

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Verification
define('EMAIL_VERIFICATION_EXPIRY', 24 * 60 * 60); // 24 hours
define('PASSWORD_RESET_EXPIRY', 60 * 60); // 1 hour

// File uploads
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Pagination
define('JOBS_PER_PAGE', 20);
define('APPLICATIONS_PER_PAGE', 10);
?>