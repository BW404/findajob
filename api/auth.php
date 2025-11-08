<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

class AuthAPI {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Register user
    public function register($data) {
        try {
            // Debug logging in development mode
            if (defined('DEV_MODE') && DEV_MODE) {
                error_log("Registration attempt with data: " . json_encode($data));
            }
            
            // Validate input
            $errors = $this->validateRegistration($data);
            if (!empty($errors)) {
                if (defined('DEV_MODE') && DEV_MODE) {
                    error_log("Registration validation errors: " . json_encode($errors));
                }
                return ['success' => false, 'errors' => $errors];
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'errors' => ['email' => 'Email already registered']];
            }
            
            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Generate email verification token
            $verificationToken = bin2hex(random_bytes(32));
            $verificationExpires = date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_EXPIRY);
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (user_type, email, password_hash, first_name, last_name, phone, 
                                 email_verification_token, email_verification_expires) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['user_type'],
                $data['email'],
                $passwordHash,
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $verificationToken,
                $verificationExpires
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Create profile based on user type
            if ($data['user_type'] === 'job_seeker') {
                $this->createJobSeekerProfile($userId);
            } else if ($data['user_type'] === 'employer') {
                $this->createEmployerProfile($userId, $data);
            }
            
            // Send verification email
            $this->sendVerificationEmail($data['email'], $verificationToken, $data['first_name']);
            
            return [
                'success' => true, 
                'message' => 'Registration successful. Please check your email to verify your account.',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            // Log error for debugging
            error_log("Registration error: " . $e->getMessage());
            
            // In development mode, show detailed error
            if (defined('DEV_MODE') && DEV_MODE) {
                return ['success' => false, 'errors' => ['general' => 'Registration failed: ' . $e->getMessage()]];
            }
            
            return ['success' => false, 'errors' => ['general' => 'Registration failed. Please try again.']];
        }
    }
    
    // Login user
    public function login($email, $password, $userType = null, $rememberMe = false) {
        try {
            // Get user with optional user type filter
            $sql = "
                SELECT id, user_type, email, password_hash, first_name, email_verified, is_active 
                FROM users 
                WHERE email = ? AND is_active = 1
            ";
            $params = [$email];
            
            // If user type is specified, add it to the query
            if ($userType) {
                $sql .= " AND user_type = ?";
                $params[] = $userType;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $user = $stmt->fetch();
            
            // Log login attempt
            $this->logLoginAttempt($email, $user && password_verify($password, $user['password_hash']));
            
            if (!$user) {
                if ($userType) {
                    return ['success' => false, 'message' => 'No ' . ucfirst(str_replace('_', ' ', $userType)) . ' account found with this email'];
                }
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check if too many failed attempts
            if ($this->hasTooManyFailedAttempts($email)) {
                return ['success' => false, 'error' => 'Too many failed login attempts. Please try again later.'];
            }
            
            // Start session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['email_verified'] = $user['email_verified'];
            
            // Set remember me cookie if requested
            if ($rememberMe) {
                setcookie('remember_token', bin2hex(random_bytes(32)), time() + (30 * 24 * 60 * 60), '/');
            }
            
            return [
                'success' => true, 
                'user_type' => $user['user_type'],
                'email_verified' => $user['email_verified']
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Login failed. Please try again.'];
        }
    }
    
    // Verify email
    public function verifyEmail($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email_verification_expires 
                FROM users 
                WHERE email_verification_token = ? AND email_verified = 0
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid verification token'];
            }
            
            if (strtotime($user['email_verification_expires']) < time()) {
                return ['success' => false, 'error' => 'Verification token has expired'];
            }
            
            // Update user as verified
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_verified = 1, email_verification_token = NULL, email_verification_expires = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // Get user details for welcome email
            $stmt = $this->pdo->prepare("SELECT email, first_name, user_type FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();
            
            // Send welcome email
            if ($userData) {
                $this->sendWelcomeEmail($userData['email'], $userData['first_name'], $userData['user_type']);
            }
            
            return ['success' => true, 'message' => 'Email verified successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Verification failed. Please try again.'];
        }
    }
    
    // Resend verification email
    public function resendVerification($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, first_name 
                FROM users 
                WHERE email = ? AND email_verified = 0
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'User not found or already verified'];
            }
            
            // Generate new token
            $verificationToken = bin2hex(random_bytes(32));
            $verificationExpires = date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_EXPIRY);
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_verification_token = ?, email_verification_expires = ? 
                WHERE id = ?
            ");
            $stmt->execute([$verificationToken, $verificationExpires, $user['id']]);
            
            // Send verification email
            $this->sendVerificationEmail($email, $verificationToken, $user['first_name']);
            
            return ['success' => true, 'message' => 'Verification email sent'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to send verification email'];
        }
    }
    
    // Request password reset
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, first_name 
                FROM users 
                WHERE email = ? AND is_active = 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Don't reveal if email exists
                return ['success' => true, 'message' => 'If the email exists, a reset link has been sent'];
            }
            
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $resetExpires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password_reset_token = ?, password_reset_expires = ? 
                WHERE id = ?
            ");
            $stmt->execute([$resetToken, $resetExpires, $user['id']]);
            
            // Send reset email
            $this->sendPasswordResetEmail($email, $resetToken, $user['first_name']);
            
            return ['success' => true, 'message' => 'If the email exists, a reset link has been sent'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to process password reset request'];
        }
    }
    
    // Reset password
    public function resetPassword($token, $newPassword) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, password_reset_expires 
                FROM users 
                WHERE password_reset_token = ?
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Invalid reset token'];
            }
            
            if (strtotime($user['password_reset_expires']) < time()) {
                return ['success' => false, 'error' => 'Reset token has expired'];
            }
            
            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $user['id']]);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Password reset failed'];
        }
    }
    
    // Private helper methods
    private function validateRegistration($data) {
        $errors = [];
        
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (!in_array($data['user_type'], ['job_seeker', 'employer'])) {
            $errors['user_type'] = 'Invalid user type';
        }
        
        if ($data['user_type'] === 'employer' && empty($data['company_name'])) {
            $errors['company_name'] = 'Company name is required for employers';
        }
        
        return $errors;
    }
    
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    private function createJobSeekerProfile($userId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO job_seeker_profiles (user_id) VALUES (?)
        ");
        $stmt->execute([$userId]);
    }
    
    private function createEmployerProfile($userId, $data) {
        // Get user data for provider information
        $stmt = $this->pdo->prepare("
            SELECT first_name, last_name, phone FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Create employer profile with both company and provider (representative) information
        $stmt = $this->pdo->prepare("
            INSERT INTO employer_profiles (
                user_id, 
                company_name,
                provider_first_name,
                provider_last_name,
                provider_phone
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $data['company_name'],
            $user['first_name'],
            $user['last_name'],
            $user['phone']
        ]);
    }
    
    private function logLoginAttempt($email, $success) {
        $stmt = $this->pdo->prepare("
            INSERT INTO login_attempts (email, ip_address, success) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $success]);
    }
    
    private function hasTooManyFailedAttempts($email) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE email = ? AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result['attempts'] >= 5;
    }
    
    private function sendVerificationEmail($email, $token, $firstName) {
        $verificationUrl = SITE_URL . "/api/auth.php?action=verify&token=" . $token;
        $subject = "✓ Verify your FindAJob Nigeria account";
        $message = $this->getEmailTemplate([
            'title' => 'Welcome to FindAJob Nigeria!',
            'greeting' => "Hi {$firstName},",
            'content' => "
                <p style='font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;'>
                    Thank you for joining Nigeria's premier job platform! We're excited to help you find your dream career.
                </p>
                <p style='font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;'>
                    To get started and access all features, please verify your email address by clicking the button below:
                </p>
            ",
            'button_text' => 'Verify My Account',
            'button_url' => $verificationUrl,
            'footer_content' => "
                <p style='font-size: 14px; color: #6B7280; margin-bottom: 16px;'>
                    If the button doesn't work, copy and paste this link into your browser:
                </p>
                <p style='font-size: 14px; color: #DC2626; word-break: break-all; margin-bottom: 24px;'>
                    {$verificationUrl}
                </p>
                <p style='font-size: 14px; color: #6B7280; margin-bottom: 16px;'>
                    ⏰ <strong>Important:</strong> This verification link will expire in 24 hours for security reasons.
                </p>
                <p style='font-size: 14px; color: #6B7280;'>
                    Once verified, you'll be able to:
                </p>
                <ul style='font-size: 14px; color: #6B7280; margin: 16px 0; padding-left: 20px;'>
                    <li>Browse thousands of job opportunities</li>
                    <li>Apply to jobs with one click</li>
                    <li>Get AI-powered job recommendations</li>
                    <li>Build professional CVs with our tools</li>
                </ul>
            "
        ]);
        
        $this->sendEmail($email, $subject, $message, 'verification');
    }
    
    private function sendPasswordResetEmail($email, $token, $firstName) {
        $resetUrl = SITE_URL . "/pages/auth/reset.php?token=" . $token;
        $subject = "🔐 Reset your FindAJob Nigeria password";
        $message = $this->getEmailTemplate([
            'title' => 'Password Reset Request',
            'greeting' => "Hi {$firstName},",
            'content' => "
                <p style='font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;'>
                    We received a request to reset your password for your FindAJob Nigeria account.
                </p>
                <p style='font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 32px;'>
                    If you requested this password reset, click the button below to create a new password:
                </p>
            ",
            'button_text' => 'Reset My Password',
            'button_url' => $resetUrl,
            'footer_content' => "
                <p style='font-size: 14px; color: #6B7280; margin-bottom: 16px;'>
                    If the button doesn't work, copy and paste this link into your browser:
                </p>
                <p style='font-size: 14px; color: #DC2626; word-break: break-all; margin-bottom: 24px;'>
                    {$resetUrl}
                </p>
                <div style='background-color: #FEF2F2; border-left: 4px solid #DC2626; padding: 16px; margin: 24px 0; border-radius: 4px;'>
                    <p style='font-size: 14px; color: #991B1B; margin: 0; font-weight: 600;'>
                        ⚠️ Security Notice:
                    </p>
                    <ul style='font-size: 14px; color: #991B1B; margin: 8px 0 0 0; padding-left: 20px;'>
                        <li>This reset link expires in 1 hour</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Your password remains unchanged until you create a new one</li>
                    </ul>
                </div>
                <p style='font-size: 14px; color: #6B7280;'>
                    For your account security, never share your password reset links with anyone.
                </p>
            "
        ]);
        
        $this->sendEmail($email, $subject, $message, 'password_reset');
    }
    
    private function sendWelcomeEmail($email, $firstName, $userType) {
        $subject = "🎉 Welcome to FindAJob Nigeria - Your account is now active!";
        $dashboardUrl = SITE_URL . ($userType === 'employer' ? "/pages/company/dashboard.php" : "/pages/user/dashboard.php");
        $userTypeText = $userType === 'employer' ? 'Employer' : 'Job Seeker';
        
        $nextSteps = $userType === 'employer' ? "
            <ul style='font-size: 16px; color: #374151; margin: 16px 0; padding-left: 20px; line-height: 1.6;'>
                <li><strong>Post Your First Job:</strong> Create compelling job listings to attract top talent</li>
                <li><strong>Search CVs:</strong> Browse through thousands of qualified candidates</li>
                <li><strong>Upgrade to Pro:</strong> Get unlimited job posts and advanced features</li>
                <li><strong>Company Profile:</strong> Complete your company profile to build trust</li>
            </ul>
        " : "
            <ul style='font-size: 16px; color: #374151; margin: 16px 0; padding-left: 20px; line-height: 1.6;'>
                <li><strong>Complete Your Profile:</strong> Add your skills, experience, and preferences</li>
                <li><strong>Upload Your CV:</strong> Create or upload your professional resume</li>
                <li><strong>Browse Jobs:</strong> Explore thousands of job opportunities</li>
                <li><strong>Set Job Alerts:</strong> Get notified when relevant jobs are posted</li>
            </ul>
        ";
        
        $features = $userType === 'employer' ? "
            <div style='background-color: #F0FDF4; border-left: 4px solid #059669; padding: 20px; margin: 24px 0; border-radius: 4px;'>
                <h3 style='color: #059669; margin: 0 0 16px 0; font-size: 18px;'>🏢 Employer Features You'll Love:</h3>
                <div style='display: flex; flex-wrap: wrap; gap: 16px;'>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #047857;'><strong>🎯 Smart Matching:</strong> AI finds the best candidates for your roles</p>
                    </div>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #047857;'><strong>📊 Analytics:</strong> Track application performance and engagement</p>
                    </div>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #047857;'><strong>🏪 Mini-Site:</strong> Get your own branded recruitment page</p>
                    </div>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #047857;'><strong>✅ Verified Talent:</strong> All candidates go through ID verification</p>
                    </div>
                </div>
            </div>
        " : "
            <div style='background-color: #EFF6FF; border-left: 4px solid #3B82F6; padding: 20px; margin: 24px 0; border-radius: 4px;'>
                <h3 style='color: #1D4ED8; margin: 0 0 16px 0; font-size: 18px;'>💼 Job Seeker Features You'll Love:</h3>
                <div style='display: flex; flex-wrap: wrap; gap: 16px;'>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>🎯 AI Matching:</strong> Get personalized job recommendations</p>
                    </div>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>📝 CV Builder:</strong> Create professional resumes with AI assistance</p>
                    </div>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>🔔 Job Alerts:</strong> Never miss relevant opportunities</p>
                    </div>
                    <div style='flex: 1; min-width: 200px;'>
                        <p style='margin: 0; font-size: 14px; color: #1E40AF;'><strong>✅ Verified Status:</strong> Stand out with ID verification</p>
                    </div>
                </div>
            </div>
        ";
        
        $message = $this->getEmailTemplate([
            'title' => "You're All Set, {$firstName}!",
            'greeting' => "Congratulations {$firstName}! 🎉",
            'content' => "
                <p style='font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 24px;'>
                    Your FindAJob Nigeria account has been successfully verified and is now fully active! As a <strong>{$userTypeText}</strong>, you now have access to all our powerful features.
                </p>
                
                {$features}
                
                <h3 style='color: #1F2937; font-size: 20px; margin: 32px 0 16px 0;'>🚀 Your Next Steps:</h3>
                {$nextSteps}
                
                <p style='font-size: 16px; line-height: 1.6; color: #374151; margin: 24px 0;'>
                    Ready to get started? Click the button below to access your personalized dashboard:
                </p>
            ",
            'button_text' => "Go to My Dashboard",
            'button_url' => $dashboardUrl,
            'footer_content' => "
                <div style='background-color: #F8FAFC; border: 1px solid #E5E7EB; border-radius: 8px; padding: 20px; margin: 24px 0;'>
                    <h4 style='color: #374151; margin: 0 0 12px 0; font-size: 16px;'>💡 Pro Tips for Success:</h4>
                    <ul style='font-size: 14px; color: #6B7280; margin: 0; padding-left: 20px;'>
                        <li>Complete your profile 100% to get 3x more visibility</li>
                        <li>Join our Pro plan for premium features and priority support</li>
                        <li>Follow us on social media for job market insights and tips</li>
                        <li>Refer friends and earn rewards through our referral program</li>
                    </ul>
                </div>
                
                <p style='font-size: 14px; color: #6B7280; margin-bottom: 16px;'>
                    Need help getting started? Our support team is here to assist you at 
                    <a href='mailto:support@findajob.ng' style='color: #DC2626;'>support@findajob.ng</a>
                </p>
                
                <p style='font-size: 14px; color: #6B7280;'>
                    Welcome to the FindAJob Nigeria family! We're excited to be part of your career journey. 🚀
                </p>
            "
        ]);
        
        $this->sendEmail($email, $subject, $message, 'welcome');
    }
    
    private function sendEmail($to, $subject, $message, $type = 'general') {
        // For XAMPP development, store emails in temporary file instead of sending
        if (isDevelopmentMode()) {
            devStoreEmail($to, $subject, $message, $type);
            return true;
        }
        
        // Production email sending
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . SITE_NAME . ' <' . SITE_EMAIL . '>',
            'Reply-To: ' . SITE_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    

    
    private function getEmailTemplate($data) {
        $title = $data['title'] ?? 'FindAJob Nigeria';
        $greeting = $data['greeting'] ?? 'Hello,';
        $content = $data['content'] ?? '';
        $buttonText = $data['button_text'] ?? 'Click Here';
        $buttonUrl = $data['button_url'] ?? '#';
        $footerContent = $data['footer_content'] ?? '';
        
        return "
        <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
        <html xmlns='http://www.w3.org/1999/xhtml'>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
            <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
            <title>{$title}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #F8FAFC; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <!-- Main Container -->
                        <table border='0' cellpadding='0' cellspacing='0' width='600' style='max-width: 600px; width: 100%; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden;'>
                            
                            <!-- Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #DC2626, #991B1B); padding: 40px 40px 30px 40px; text-align: center;'>
                                    <h1 style='color: #FFFFFF; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; letter-spacing: -0.5px;'>
                                        FindAJob Nigeria
                                    </h1>
                                    <p style='color: #FECACA; font-size: 14px; margin: 0; opacity: 0.9;'>
                                        Nigeria's Premier Job Platform
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style='padding: 40px;'>
                                    <h2 style='color: #1F2937; font-size: 24px; font-weight: 600; margin: 0 0 24px 0; line-height: 1.3;'>
                                        {$title}
                                    </h2>
                                    
                                    <p style='font-size: 16px; line-height: 1.6; color: #374151; margin: 0 0 24px 0;'>
                                        {$greeting}
                                    </p>
                                    
                                    {$content}
                                    
                                    <!-- CTA Button -->
                                    <table border='0' cellspacing='0' cellpadding='0' style='margin: 32px 0;'>
                                        <tr>
                                            <td align='center'>
                                                <a href='{$buttonUrl}' style='background-color: #DC2626; color: #FFFFFF; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.3); transition: all 0.2s ease;'>
                                                    {$buttonText}
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    {$footerContent}
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #F9FAFB; padding: 30px 40px; border-top: 1px solid #E5E7EB;'>
                                    <table border='0' cellspacing='0' cellpadding='0' width='100%'>
                                        <tr>
                                            <td>
                                                <p style='font-size: 14px; color: #6B7280; margin: 0 0 16px 0;'>
                                                    <strong>Best regards,</strong><br>
                                                    The FindAJob Nigeria Team
                                                </p>
                                                
                                                <div style='border-top: 1px solid #E5E7EB; padding-top: 20px; margin-top: 20px;'>
                                                    <p style='font-size: 12px; color: #9CA3AF; margin: 0 0 8px 0;'>
                                                        <strong>FindAJob Nigeria</strong> - Connecting Nigerian Talent with Opportunities
                                                    </p>
                                                    <p style='font-size: 12px; color: #9CA3AF; margin: 0 0 8px 0;'>
                                                        📧 Email: support@findajob.ng • 🌐 Web: www.findajob.ng
                                                    </p>
                                                    <p style='font-size: 12px; color: #9CA3AF; margin: 0;'>
                                                        This email was sent to you from FindAJob Nigeria. If you didn't request this email, please ignore it.
                                                    </p>
                                                </div>
                                                
                                                <!-- Social Links -->
                                                <table border='0' cellspacing='0' cellpadding='0' style='margin-top: 20px;'>
                                                    <tr>
                                                        <td>
                                                            <p style='font-size: 12px; color: #9CA3AF; margin: 0;'>
                                                                Follow us: 
                                                                <a href='#' style='color: #DC2626; text-decoration: none; margin: 0 8px;'>LinkedIn</a>
                                                                <a href='#' style='color: #DC2626; text-decoration: none; margin: 0 8px;'>Twitter</a>
                                                                <a href='#' style='color: #DC2626; text-decoration: none; margin: 0 8px;'>Facebook</a>
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $auth = new AuthAPI($pdo);
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'register':
            echo json_encode($auth->register($_POST));
            break;
            
        case 'login':
            $userType = isset($_POST['user_type']) ? $_POST['user_type'] : null;
            $rememberMe = isset($_POST['remember_me']);
            echo json_encode($auth->login($_POST['email'], $_POST['password'], $userType, $rememberMe));
            break;
            
        case 'resend_verification':
            echo json_encode($auth->resendVerification($_POST['email']));
            break;
            
        case 'request_password_reset':
            echo json_encode($auth->requestPasswordReset($_POST['email']));
            break;
            
        case 'reset_password':
            echo json_encode($auth->resetPassword($_POST['token'], $_POST['password']));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $auth = new AuthAPI($pdo);
    
    if ($_GET['action'] === 'verify' && isset($_GET['token'])) {
        $result = $auth->verifyEmail($_GET['token']);
        if ($result['success']) {
            header('Location: /findajob/pages/auth/login.php?verified=1');
        } else {
            header('Location: /findajob/pages/auth/login.php?error=' . urlencode($result['error']));
        }
        exit();
    }
}
?>