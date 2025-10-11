<?php
/**
 * FindAJob Nigeria - Utility Functions
 * Essential helper functions for the application
 */

// Authentication and session functions are defined in config/session.php
// Available functions from session.php:
// - isLoggedIn(), isJobSeeker(), isEmployer(), isAdmin()
// - getCurrentUserId(), getCurrentUserType()
// - loginUser(), logoutUser(), isEmailVerified()
// - requireLogin(), requireJobSeeker(), requireEmployer()
// - generateCSRFToken(), validateCSRFToken()

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Nigerian format)
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Nigerian phone number
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        return true; // 0xxxxxxxxxx format
    }
    if (strlen($phone) === 13 && substr($phone, 0, 3) === '234') {
        return true; // 234xxxxxxxxxx format
    }
    if (strlen($phone) === 10) {
        return true; // xxxxxxxxxx format
    }
    
    return false;
}

/**
 * Format phone number to Nigerian standard
 * @param string $phone
 * @return string
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        return '+234' . substr($phone, 1);
    }
    if (strlen($phone) === 13 && substr($phone, 0, 3) === '234') {
        return '+' . $phone;
    }
    if (strlen($phone) === 10) {
        return '+234' . $phone;
    }
    
    return $phone;
}

/**
 * Generate a secure random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Check password strength
 * @param string $password
 * @return array
 */
function checkPasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return [
        'is_strong' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Redirect to a specific page
 * @param string $url
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Get the base URL of the application
 * @return string
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Format currency (Nigerian Naira)
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '₦' . number_format($amount, 0, '.', ',');
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Get time ago string
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        return floor($time / 60) . ' minutes ago';
    } elseif ($time < 86400) {
        return floor($time / 3600) . ' hours ago';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . ' days ago';
    } elseif ($time < 31536000) {
        return floor($time / 2592000) . ' months ago';
    } else {
        return floor($time / 31536000) . ' years ago';
    }
}

/**
 * Generate breadcrumb navigation
 * @param array $breadcrumbs
 * @return string
 */
function generateBreadcrumbs($breadcrumbs) {
    $html = '<nav class="breadcrumb">';
    $html .= '<ol class="breadcrumb-list">';
    
    foreach ($breadcrumbs as $key => $breadcrumb) {
        $isLast = ($key === array_key_last($breadcrumbs));
        
        $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '">';
        
        if (!$isLast && isset($breadcrumb['url'])) {
            $html .= '<a href="' . htmlspecialchars($breadcrumb['url']) . '">';
            $html .= htmlspecialchars($breadcrumb['title']);
            $html .= '</a>';
        } else {
            $html .= htmlspecialchars($breadcrumb['title']);
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Log activity for debugging
 * @param string $message
 * @param string $level
 */
function logActivity($message, $level = 'INFO') {
    $logFile = '../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// CSRF token functions are defined in config/session.php
// generateCSRFToken(), validateCSRFToken() are available from session.php

/**
 * Get user's IP address
 * @return string
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Rate limiting check
 * @param string $key
 * @param int $limit
 * @param int $window
 * @return bool
 */
function checkRateLimit($key, $limit = 5, $window = 300) {
    $cacheFile = '../cache/rate_limit_' . md5($key) . '.txt';
    
    // Create cache directory if it doesn't exist
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $now = time();
    $attempts = [];
    
    // Load existing attempts
    if (file_exists($cacheFile)) {
        $attempts = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Filter out old attempts
    $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $limit) {
        return false;
    }
    
    // Add current attempt
    $attempts[] = $now;
    
    // Save attempts
    file_put_contents($cacheFile, json_encode($attempts), LOCK_EX);
    
    return true;
}

/**
 * Development Email Function - Store emails in temp file for XAMPP
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email content (HTML)
 * @param string $type Email type (verification, reset, welcome, etc.)
 * @return bool
 */
function devStoreEmail($to, $subject, $message, $type = 'general') {
    if (!defined('DEV_MODE') || !DEV_MODE || !defined('DEV_EMAIL_CAPTURE') || !DEV_EMAIL_CAPTURE) {
        return false;
    }
    
    $emailsFile = __DIR__ . '/../temp_emails.json';
    
    $email = [
        'id' => uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'type' => $type,
        'read' => false,
        'from' => SITE_EMAIL,
        'headers' => [
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=UTF-8',
            'From' => SITE_NAME . ' <' . SITE_EMAIL . '>',
            'Reply-To' => SITE_EMAIL
        ]
    ];
    
    // Read existing emails
    $emails = [];
    if (file_exists($emailsFile)) {
        $existingData = file_get_contents($emailsFile);
        $emails = json_decode($existingData, true) ?: [];
    }
    
    // Add new email to the beginning
    array_unshift($emails, $email);
    
    // Keep only last 100 emails for better development experience
    $emails = array_slice($emails, 0, 100);
    
    // Save back to file
    file_put_contents($emailsFile, json_encode($emails, JSON_PRETTY_PRINT));
    
    return true;
}

/**
 * Check if we're in development environment
 * @return bool
 */
function isDevelopmentMode() {
    // Primary check - use DEV_MODE constant (works from any IP/domain)
    if (defined('DEV_MODE') && DEV_MODE) {
        return true;
    }
    
    // Fallback check for common development environments
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    
    return $server_name === 'localhost' || 
           $server_name === '127.0.0.1' ||
           strpos($server_name, 'localhost') !== false ||
           strpos($http_host, 'localhost') !== false ||
           // Local network IPs (192.168.x.x, 10.x.x.x)
           preg_match('/^192\.168\.\d+\.\d+/', $server_name) ||
           preg_match('/^10\.\d+\.\d+\.\d+/', $server_name) ||
           preg_match('/^192\.168\.\d+\.\d+/', $http_host) ||
           preg_match('/^10\.\d+\.\d+\.\d+/', $http_host);
}
?>