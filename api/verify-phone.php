<?php
/**
 * Phone Number Verification API
 * Uses Dojah OTP API for verification
 * Supports both job seekers and employers
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/constants.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = getCurrentUserId();
$userType = $_SESSION['user_type'] ?? '';

class PhoneVerificationAPI {
    private $pdo;
    private $userId;
    private $userType;
    
    public function __construct($pdo, $userId, $userType) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->userType = $userType;
    }
    
    /**
     * Send OTP to phone number via Dojah API
     */
    public function sendOTP($phoneNumber, $channel = 'sms') {
        try {
            // Validate phone number
            $cleanPhone = $this->validateAndCleanPhone($phoneNumber);
            if (!$cleanPhone) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format. Use Nigerian format: 080xxxxxxxx or 234xxxxxxxx'
                ];
            }
            
            // Validate channel
            if (!in_array($channel, ['sms', 'voice'])) {
                $channel = 'sms';
            }
            
            // Call Dojah API to send OTP
            $result = $this->callDojahSendOTP($cleanPhone, $channel);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Store OTP reference in database
            $this->storeOTPReference($cleanPhone, $result['reference_id']);
            
            $channelText = $channel === 'voice' ? 'voice call' : 'SMS';
            
            return [
                'success' => true,
                'message' => "OTP sent successfully via {$channelText} to " . $this->maskPhone($cleanPhone),
                'reference_id' => $result['reference_id'],
                'phone' => $this->maskPhone($cleanPhone)
            ];
            
        } catch (Exception $e) {
            error_log("Phone verification send OTP error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send OTP. Please try again.'
            ];
        }
    }
    
    /**
     * Verify OTP code
     */
    public function verifyOTP($code, $referenceId, $phoneNumber) {
        try {
            // Validate inputs
            if (empty($code) || empty($referenceId)) {
                return [
                    'success' => false,
                    'error' => 'OTP code and reference ID are required'
                ];
            }
            
            // Verify OTP with Dojah API
            $result = $this->callDojahVerifyOTP($code, $referenceId);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Mark phone as verified in database
            $cleanPhone = $this->validateAndCleanPhone($phoneNumber);
            $this->markPhoneAsVerified($cleanPhone);
            
            // Update session
            $_SESSION['phone_verified'] = true;
            
            return [
                'success' => true,
                'message' => 'Phone number verified successfully!'
            ];
            
        } catch (Exception $e) {
            error_log("Phone verification verify OTP error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to verify OTP. Please try again.'
            ];
        }
    }
    
    /**
     * Call Dojah API to send OTP
     */
    private function callDojahSendOTP($phoneNumber, $channel = 'sms') {
        $url = DOJAH_API_BASE_URL . '/messaging/otp/';
        
        $data = [
            'sender_id' => PHONE_OTP_SENDER_ID,
            'destination' => $phoneNumber,
            'channel' => $channel, // 'sms' or 'voice'
            'expiry' => PHONE_OTP_EXPIRY,
            'length' => PHONE_OTP_LENGTH
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'AppId: ' . DOJAH_APP_ID,
            'Authorization: ' . DOJAH_API_KEY,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("Dojah API cURL error: " . $error);
            return [
                'success' => false,
                'error' => 'Network error. Please try again.'
            ];
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            error_log("Dojah Send OTP API error: " . $response);
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send OTP'
            ];
        }
        
        if (isset($result['entity'][0]['reference_id'])) {
            return [
                'success' => true,
                'reference_id' => $result['entity'][0]['reference_id'],
                'status' => $result['entity'][0]['status'] ?? 'sent'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Unexpected API response'
        ];
    }
    
    /**
     * Call Dojah API to verify OTP
     */
    private function callDojahVerifyOTP($code, $referenceId) {
        $url = DOJAH_API_BASE_URL . '/messaging/otp/validate?code=' . urlencode($code) . '&reference_id=' . urlencode($referenceId);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'AppId: ' . DOJAH_APP_ID,
            'Authorization: ' . DOJAH_API_KEY,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("Dojah API cURL error: " . $error);
            return [
                'success' => false,
                'error' => 'Network error. Please try again.'
            ];
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            error_log("Dojah Verify OTP API error: " . $response);
            return [
                'success' => false,
                'error' => 'Invalid or expired OTP code'
            ];
        }
        
        if (isset($result['entity']['valid']) && $result['entity']['valid'] === true) {
            return [
                'success' => true,
                'valid' => true
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Invalid OTP code'
        ];
    }
    
    /**
     * Validate and clean phone number
     */
    private function validateAndCleanPhone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to international format
        if (strlen($phone) == 11 && $phone[0] == '0') {
            // 080xxxxxxxx -> 2348xxxxxxxx
            $phone = '234' . substr($phone, 1);
        } elseif (strlen($phone) == 10) {
            // 80xxxxxxxx -> 2348xxxxxxxx
            $phone = '234' . $phone;
        } elseif (strlen($phone) == 13 && substr($phone, 0, 3) == '234') {
            // Already in correct format
            $phone = $phone;
        } else {
            return false;
        }
        
        // Validate Nigerian number (should be 13 digits starting with 234)
        if (strlen($phone) != 13 || substr($phone, 0, 3) != '234') {
            return false;
        }
        
        return $phone;
    }
    
    /**
     * Mask phone number for display
     */
    private function maskPhone($phone) {
        if (strlen($phone) == 13) {
            // 2348012345678 -> +234 801 234 ***8
            return '+' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 3) . ' ***' . substr($phone, -1);
        }
        return $phone;
    }
    
    /**
     * Store OTP reference in database
     */
    private function storeOTPReference($phone, $referenceId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO phone_verification_attempts 
            (user_id, phone_number, reference_id, created_at, expires_at)
            VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE))
            ON DUPLICATE KEY UPDATE
            reference_id = VALUES(reference_id),
            created_at = VALUES(created_at),
            expires_at = VALUES(expires_at),
            verified = 0
        ");
        
        $stmt->execute([
            $this->userId,
            $phone,
            $referenceId,
            PHONE_OTP_EXPIRY
        ]);
    }
    
    /**
     * Mark phone as verified and update phone number
     */
    private function markPhoneAsVerified($phone) {
        $this->pdo->beginTransaction();
        
        try {
            // Update verification attempts table
            $stmt = $this->pdo->prepare("
                UPDATE phone_verification_attempts 
                SET verified = 1, verified_at = NOW()
                WHERE user_id = ? AND phone_number = ?
            ");
            $stmt->execute([$this->userId, $phone]);
            
            // Update user's phone number and verification status
            if ($this->userType === 'job_seeker') {
                // Update job seeker profile with verified phone number
                $stmt = $this->pdo->prepare("
                    UPDATE job_seeker_profiles 
                    SET phone_verified = 1, phone_verified_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$this->userId]);
            } elseif ($this->userType === 'employer') {
                // Update employer profile with verified phone number
                $stmt = $this->pdo->prepare("
                    UPDATE employer_profiles 
                    SET provider_phone = ?,
                        provider_phone_verified = 1, 
                        provider_phone_verified_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$phone, $this->userId]);
            }
            
            // Update users table with verified phone number
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET phone = ?,
                    phone_verified = 1, 
                    phone_verified_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$phone, $this->userId]);
            
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

// Handle API request
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    $api = new PhoneVerificationAPI($pdo, $userId, $userType);
    
    switch ($action) {
        case 'send_otp':
            $phoneNumber = $input['phone_number'] ?? '';
            $channel = $input['channel'] ?? 'sms'; // Default to SMS
            $result = $api->sendOTP($phoneNumber, $channel);
            echo json_encode($result);
            break;
            
        case 'verify_otp':
            $code = $input['code'] ?? '';
            $referenceId = $input['reference_id'] ?? '';
            $phoneNumber = $input['phone_number'] ?? '';
            $result = $api->verifyOTP($code, $referenceId, $phoneNumber);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Phone verification API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
