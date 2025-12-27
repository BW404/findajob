<?php
/**
 * FindAJob Nigeria - Email Notification System
 * Application status change notifications
 */

// Ensure constants are loaded
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
}

// Ensure functions.php is loaded for devStoreEmail
if (!function_exists('devStoreEmail')) {
    require_once __DIR__ . '/functions.php';
}

/**
 * Send application status change notification email
 * @param int $applicationId
 * @param string $newStatus
 * @param PDO $pdo
 * @return bool
 */
function sendApplicationStatusEmail($applicationId, $newStatus, $pdo) {
    try {
        // Fetch application details
        $stmt = $pdo->prepare("
            SELECT ja.*, 
                   j.title as job_title, 
                   j.id as job_id,
                   u.first_name, u.last_name, u.email,
                   ep.company_name
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON ja.job_seeker_id = u.id
            JOIN users eu ON j.employer_id = eu.id
            JOIN employer_profiles ep ON eu.id = ep.user_id
            WHERE ja.id = ?
        ");
        $stmt->execute([$applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            return false;
        }
        
        $jobSeekerName = $application['first_name'] . ' ' . $application['last_name'];
        $jobTitle = $application['job_title'];
        $companyName = $application['company_name'];
        $jobUrl = SITE_URL . '/pages/jobs/details.php?id=' . $application['job_id'];
        
        // Status-specific email content
        $statusMessages = [
            'viewed' => [
                'subject' => "Your application for {$jobTitle} has been viewed",
                'heading' => "Application Viewed",
                'message' => "Good news! {$companyName} has viewed your application for the {$jobTitle} position.",
                'icon' => '👀',
                'color' => '#3b82f6'
            ],
            'shortlisted' => [
                'subject' => "You've been shortlisted for {$jobTitle}",
                'heading' => "Congratulations! You've Been Shortlisted",
                'message' => "{$companyName} has shortlisted you for the {$jobTitle} position. They may contact you soon for the next steps.",
                'icon' => '⭐',
                'color' => '#8b5cf6'
            ],
            'interviewed' => [
                'subject' => "Interview scheduled for {$jobTitle}",
                'heading' => "Interview Scheduled",
                'message' => "{$companyName} has scheduled an interview for the {$jobTitle} position. Please check your email or phone for interview details.",
                'icon' => '📅',
                'color' => '#10b981'
            ],
            'offered' => [
                'subject' => "Job offer for {$jobTitle}",
                'heading' => "Congratulations! Job Offer Received",
                'message' => "Excellent news! {$companyName} has made you an offer for the {$jobTitle} position. They will contact you with the offer details.",
                'icon' => '🎉',
                'color' => '#10b981'
            ],
            'hired' => [
                'subject' => "You've been hired for {$jobTitle}!",
                'heading' => "Congratulations! You Got The Job!",
                'message' => "Welcome aboard! {$companyName} has confirmed your hiring for the {$jobTitle} position. Congratulations on your new role!",
                'icon' => '🎊',
                'color' => '#059669'
            ],
            'rejected' => [
                'subject' => "Update on your application for {$jobTitle}",
                'heading' => "Application Update",
                'message' => "Thank you for your interest in the {$jobTitle} position at {$companyName}. Unfortunately, they have decided to move forward with other candidates at this time. Keep applying - your next opportunity is just around the corner!",
                'icon' => '📧',
                'color' => '#6b7280'
            ]
        ];
        
        if (!isset($statusMessages[$newStatus])) {
            return false; // No email for 'applied' status
        }
        
        $emailData = $statusMessages[$newStatus];
        
        // Build HTML email
        $darkerColor = darkenColor($emailData['color'], 20);
        $emailHtml = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f4f6; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, {$emailData['color']} 0%, {$darkerColor} 100%); padding: 40px 20px; text-align: center; color: white; }
                .icon { font-size: 60px; margin-bottom: 20px; }
                .heading { font-size: 28px; font-weight: 700; margin: 0; }
                .content { padding: 40px 30px; }
                .message { font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 30px; }
                .job-card { background: #f9fafb; border-left: 4px solid {$emailData['color']}; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .job-title { font-size: 20px; font-weight: 600; color: #111827; margin: 0 0 10px 0; }
                .company { font-size: 16px; color: #6b7280; margin: 0; }
                .button { display: inline-block; padding: 14px 32px; background-color: {$emailData['color']}; color: white !important; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
                .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb; }
                .tips { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .tips-title { font-weight: 600; color: #1e40af; margin: 0 0 10px 0; }
                .tips-list { margin: 0; padding-left: 20px; color: #1e40af; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>{$emailData['icon']}</div>
                    <h1 class='heading'>{$emailData['heading']}</h1>
                </div>
                
                <div class='content'>
                    <p style='font-size: 18px; color: #111827; margin-bottom: 10px;'>Hi {$jobSeekerName},</p>
                    
                    <p class='message'>{$emailData['message']}</p>
                    
                    <div class='job-card'>
                        <h3 class='job-title'>{$jobTitle}</h3>
                        <p class='company'>📍 {$companyName}</p>
                    </div>
                    
                    <center>
                        <a href='{$jobUrl}' class='button'>View Job Details</a>
                    </center>
                    
                    " . ($newStatus === 'rejected' ? "
                    <div class='tips'>
                        <p class='tips-title'>💡 Keep Moving Forward!</p>
                        <ul class='tips-list'>
                            <li>Update your CV and profile</li>
                            <li>Apply to more relevant positions</li>
                            <li>Use our CV services to stand out</li>
                            <li>Every application is a learning opportunity</li>
                        </ul>
                    </div>
                    " : "") . "
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
                        View all your applications and track their status in your 
                        <a href='" . SITE_URL . "/pages/user/dashboard.php' style='color: {$emailData['color']};'>dashboard</a>.
                    </p>
                </div>
                
                <div class='footer'>
                    <p style='margin: 0 0 10px 0;'><strong>" . SITE_NAME . "</strong></p>
                    <p style='margin: 0;'>Nigeria's #1 Job Platform</p>
                    <p style='margin: 10px 0 0 0;'>
                        <a href='" . SITE_URL . "' style='color: #6b7280; text-decoration: none;'>Visit Website</a> | 
                        <a href='" . SITE_URL . "/pages/user/profile.php' style='color: #6b7280; text-decoration: none;'>Manage Preferences</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send email (will be captured in dev mode)
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <" . SITE_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
        
        // Store email in development mode
        if (defined('DEV_MODE') && DEV_MODE && function_exists('devStoreEmail')) {
            devStoreEmail($application['email'], $emailData['subject'], $emailHtml, 'application_status');
        }
        
        // Send actual email in production
        $sent = mail($application['email'], $emailData['subject'], $emailHtml, $headers);
        
        // Log the notification
        error_log("Application status email sent to {$application['email']} - Status: {$newStatus} - Job: {$jobTitle}");
        
        return $sent || (defined('DEV_MODE') && DEV_MODE);
        
    } catch (Exception $e) {
        error_log("Error sending application status email: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to darken a hex color
 * @param string $hex
 * @param int $percent
 * @return string
 */
function darkenColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, $r - ($r * $percent / 100));
    $g = max(0, $g - ($g * $percent / 100));
    $b = max(0, $b - ($b * $percent / 100));
    
    return '#' . sprintf("%02x%02x%02x", $r, $g, $b);
}

/**
 * Send interview scheduled notification email
 * @param string $candidateEmail
 * @param string $candidateName
 * @param string $jobTitle
 * @param string $companyName
 * @param string $interviewDatetime
 * @param string $interviewType
 * @param string|null $interviewLink
 * @param string|null $notes
 * @return bool
 */
function sendInterviewScheduledEmail($candidateEmail, $candidateName, $jobTitle, $companyName, $interviewDatetime, $interviewType, $interviewLink = null, $notes = null) {
    try {
        $formattedDate = date('l, F j, Y', strtotime($interviewDatetime));
        $formattedTime = date('g:i A', strtotime($interviewDatetime));
        
        $typeLabels = [
            'phone' => '📞 Phone Interview',
            'video' => '🎥 Video Interview',
            'in_person' => '🏢 In-Person Interview',
            'online' => '💻 Online Interview'
        ];
        
        $typeLabel = $typeLabels[$interviewType] ?? 'Interview';
        $primaryColor = '#10b981';
        $darkerColor = darkenColor($primaryColor, 20);
        
        $emailHtml = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 0; background-color: #f3f4f6; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { background: linear-gradient(135deg, {$primaryColor} 0%, {$darkerColor} 100%); padding: 40px 20px; text-align: center; color: white; }
                .icon { font-size: 60px; margin-bottom: 20px; }
                .heading { font-size: 28px; font-weight: 700; margin: 0; }
                .content { padding: 40px 30px; }
                .message { font-size: 16px; line-height: 1.6; color: #374151; margin-bottom: 30px; }
                .interview-card { background: #f0fdf4; border: 2px solid {$primaryColor}; padding: 24px; margin: 20px 0; border-radius: 12px; }
                .job-title { font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 8px 0; }
                .company { font-size: 18px; color: #6b7280; margin: 0 0 20px 0; }
                .detail-row { display: flex; align-items: flex-start; margin: 12px 0; padding: 12px; background: white; border-radius: 8px; }
                .detail-icon { font-size: 20px; margin-right: 12px; min-width: 24px; }
                .detail-content { flex: 1; }
                .detail-label { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 4px 0; }
                .detail-value { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }
                .link-button { display: inline-block; width: 100%; padding: 16px; background-color: {$primaryColor}; color: white !important; text-decoration: none; border-radius: 8px; font-weight: 600; text-align: center; margin: 16px 0; box-sizing: border-box; }
                .notes-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px; margin: 20px 0; border-radius: 8px; }
                .notes-title { font-weight: 600; color: #92400e; margin: 0 0 8px 0; font-size: 14px; }
                .notes-content { color: #78350f; margin: 0; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
                .tips { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .tips-title { font-weight: 600; color: #1e40af; margin: 0 0 10px 0; }
                .tips-list { margin: 0; padding-left: 20px; color: #1e40af; line-height: 1.8; }
                .footer { background: #f9fafb; padding: 30px; text-align: center; color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>📅</div>
                    <h1 class='heading'>Interview Scheduled!</h1>
                </div>
                
                <div class='content'>
                    <p style='font-size: 18px; color: #111827; margin-bottom: 10px;'>Hi {$candidateName},</p>
                    
                    <p class='message'>
                        Great news! <strong>{$companyName}</strong> has scheduled an interview with you for the <strong>{$jobTitle}</strong> position. 
                        Please review the details below and prepare accordingly.
                    </p>
                    
                    <div class='interview-card'>
                        <h3 class='job-title'>{$jobTitle}</h3>
                        <p class='company'>📍 {$companyName}</p>
                        
                        <div class='detail-row'>
                            <div class='detail-icon'>📅</div>
                            <div class='detail-content'>
                                <p class='detail-label'>Date</p>
                                <p class='detail-value'>{$formattedDate}</p>
                            </div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-icon'>🕐</div>
                            <div class='detail-content'>
                                <p class='detail-label'>Time</p>
                                <p class='detail-value'>{$formattedTime}</p>
                            </div>
                        </div>
                        
                        <div class='detail-row'>
                            <div class='detail-icon'>🎯</div>
                            <div class='detail-content'>
                                <p class='detail-label'>Interview Type</p>
                                <p class='detail-value'>{$typeLabel}</p>
                            </div>
                        </div>
                        
                        " . ($interviewLink ? "
                        <div style='margin-top: 16px;'>
                            <a href='{$interviewLink}' class='link-button'>
                                🔗 Join Interview Meeting
                            </a>
                            <p style='font-size: 12px; color: #6b7280; text-align: center; margin: 8px 0 0 0;'>
                                Meeting Link: <a href='{$interviewLink}' style='color: {$primaryColor}; word-break: break-all;'>{$interviewLink}</a>
                            </p>
                        </div>
                        " : "") . "
                    </div>
                    
                    " . ($notes ? "
                    <div class='notes-box'>
                        <p class='notes-title'>📝 Additional Instructions from Employer:</p>
                        <p class='notes-content'>{$notes}</p>
                    </div>
                    " : "") . "
                    
                    <div class='tips'>
                        <p class='tips-title'>💡 Interview Preparation Tips:</p>
                        <ul class='tips-list'>
                            <li>Research the company and the role thoroughly</li>
                            <li>Review your application and CV before the interview</li>
                            <li>Prepare answers to common interview questions</li>
                            <li>Test your internet connection and equipment (for video interviews)</li>
                            <li>Dress professionally and arrive/join 5-10 minutes early</li>
                            <li>Prepare thoughtful questions to ask the interviewer</li>
                        </ul>
                    </div>
                    
                    <p style='color: #6b7280; font-size: 14px; margin-top: 30px; padding: 16px; background: #f9fafb; border-radius: 8px;'>
                        <strong>Need help?</strong><br>
                        View your scheduled interviews and manage applications in your 
                        <a href='" . SITE_URL . "/pages/user/dashboard.php' style='color: {$primaryColor}; font-weight: 600;'>dashboard</a>.
                    </p>
                </div>
                
                <div class='footer'>
                    <p style='margin: 0 0 10px 0;'><strong>" . SITE_NAME . "</strong></p>
                    <p style='margin: 0;'>Nigeria's #1 Job Platform</p>
                    <p style='margin: 10px 0 0 0;'>
                        <a href='" . SITE_URL . "' style='color: #6b7280; text-decoration: none;'>Visit Website</a> | 
                        <a href='" . SITE_URL . "/pages/user/applications.php' style='color: #6b7280; text-decoration: none;'>My Applications</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $subject = "Interview Scheduled: {$jobTitle} at {$companyName}";
        
        // Send email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <" . SITE_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
        
        // Store email in development mode
        if (defined('DEV_MODE') && DEV_MODE && function_exists('devStoreEmail')) {
            devStoreEmail($candidateEmail, $subject, $emailHtml, 'interview_scheduled');
        }
        
        // Send actual email in production
        $sent = mail($candidateEmail, $subject, $emailHtml, $headers);
        
        // Log the notification
        error_log("Interview scheduled email sent to {$candidateEmail} - Job: {$jobTitle} - Date: {$interviewDatetime}");
        
        return $sent || (defined('DEV_MODE') && DEV_MODE);
        
    } catch (Exception $e) {
        error_log("Error sending interview email: " . $e->getMessage());
        return false;
    }
}
?>
