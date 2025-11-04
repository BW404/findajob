<?php
/**
 * NIN Verification API
 * Handles National Identification Number verification using Dojah API
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit;
}

// Only job seekers can verify NIN
if (!isJobSeeker()) {
    echo json_encode(['success' => false, 'error' => 'Only job seekers can verify NIN.']);
    exit;
}

$userId = getCurrentUserId();

class NINVerificationAPI {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    /**
     * Verify NIN using Dojah API
     */
    public function verifyNIN($nin) {
        try {
            // Validate NIN format
            if (!$this->validateNIN($nin)) {
                return [
                    'success' => false, 
                    'error' => 'Invalid NIN format. NIN must be 11 digits.'
                ];
            }
            
            // Check if NIN is already verified for this user
            if ($this->isNINAlreadyVerified($nin)) {
                return [
                    'success' => false,
                    'error' => 'This NIN has already been verified for your account.'
                ];
            }
            
            // Check if NIN is used by another user
            if ($this->isNINUsedByAnotherUser($nin)) {
                return [
                    'success' => false,
                    'error' => 'This NIN is already registered to another user.'
                ];
            }
            
            // Create transaction record
            $transactionRef = $this->createTransaction();
            
            // Log verification attempt
            $logId = $this->logVerificationAttempt($nin, 'initiated');
            
            // Call Dojah API
            $apiResponse = $this->callDojahAPI($nin);
            
            if ($apiResponse['success']) {
                // Update profile with verification data
                $this->updateProfileWithVerificationData($nin, $apiResponse['data']);
                
                // Update transaction as completed
                $this->updateTransaction($transactionRef, 'completed', $apiResponse['data']);
                
                // Log successful verification
                $this->updateVerificationLog($logId, 'success', $apiResponse['data']);
                
                return [
                    'success' => true,
                    'message' => 'NIN verified successfully!',
                    'data' => $this->sanitizeVerificationData($apiResponse['data'])
                ];
            } else {
                // Update transaction as failed
                $this->updateTransaction($transactionRef, 'failed', null, $apiResponse['error']);
                
                // Log failed verification
                $this->updateVerificationLog($logId, 'failed', null, $apiResponse['error']);
                
                return [
                    'success' => false,
                    'error' => $apiResponse['error']
                ];
            }
            
        } catch (Exception $e) {
            error_log("NIN Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Verification failed. Please try again later.'
            ];
        }
    }
    
    /**
     * Call Dojah NIN Verification API
     */
    private function callDojahAPI($nin) {
        $url = DOJAH_API_BASE_URL . '/kyc/nin/advance?nin=' . $nin;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'AppId: ' . DOJAH_APP_ID,
                'Authorization: ' . DOJAH_API_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Dojah API cURL Error: " . $curlError);
            return [
                'success' => false,
                'error' => 'Connection error. Please try again.'
            ];
        }
        
        $data = json_decode($response, true);
        
        // Handle API response
        if ($httpCode === 200 && isset($data['entity'])) {
            return [
                'success' => true,
                'data' => $data['entity']
            ];
        } else {
            $errorMessage = $data['error']['message'] ?? 'NIN verification failed. Please check the NIN and try again.';
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }
    
    /**
     * Validate NIN format
     */
    private function validateNIN($nin) {
        // NIN should be exactly 11 digits
        return preg_match('/^\d{11}$/', $nin);
    }
    
    /**
     * Check if NIN is already verified for this user
     */
    private function isNINAlreadyVerified($nin) {
        $stmt = $this->pdo->prepare("
            SELECT nin_verified 
            FROM job_seeker_profiles 
            WHERE user_id = ? AND nin = ? AND nin_verified = 1
        ");
        $stmt->execute([$this->userId, $nin]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Check if NIN is used by another user
     */
    private function isNINUsedByAnotherUser($nin) {
        $stmt = $this->pdo->prepare("
            SELECT user_id 
            FROM job_seeker_profiles 
            WHERE nin = ? AND nin_verified = 1 AND user_id != ?
        ");
        $stmt->execute([$nin, $this->userId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Create verification transaction
     */
    private function createTransaction() {
        $reference = 'NIN_' . time() . '_' . $this->userId . '_' . bin2hex(random_bytes(4));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO verification_transactions 
            (user_id, transaction_type, amount, currency, status, reference)
            VALUES (?, 'nin_verification', ?, 'NGN', 'pending', ?)
        ");
        $stmt->execute([$this->userId, NIN_VERIFICATION_FEE, $reference]);
        
        return $reference;
    }
    
    /**
     * Update transaction status
     */
    private function updateTransaction($reference, $status, $metadata = null, $error = null) {
        $stmt = $this->pdo->prepare("
            UPDATE verification_transactions 
            SET status = ?, metadata = ?, updated_at = CURRENT_TIMESTAMP
            WHERE reference = ?
        ");
        
        $metadataJson = $metadata ? json_encode($metadata) : null;
        if ($error) {
            $metadataJson = json_encode(['error' => $error]);
        }
        
        $stmt->execute([$status, $metadataJson, $reference]);
    }
    
    /**
     * Log verification attempt
     */
    private function logVerificationAttempt($nin, $status) {
        $stmt = $this->pdo->prepare("
            INSERT INTO verification_audit_log 
            (user_id, verification_type, nin_number, status, api_provider, ip_address, user_agent)
            VALUES (?, 'nin', ?, ?, 'dojah', ?, ?)
        ");
        
        $stmt->execute([
            $this->userId,
            $nin,
            $status,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update verification log
     */
    private function updateVerificationLog($logId, $status, $apiResponse = null, $error = null) {
        $stmt = $this->pdo->prepare("
            UPDATE verification_audit_log 
            SET status = ?, api_response = ?, error_message = ?
            WHERE id = ?
        ");
        
        $responseJson = $apiResponse ? json_encode($apiResponse) : null;
        $stmt->execute([$status, $responseJson, $error, $logId]);
    }
    
    /**
     * Update profile with verification data
     */
    private function updateProfileWithVerificationData($nin, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE job_seeker_profiles 
            SET nin = ?, 
                nin_verified = 1, 
                nin_verified_at = CURRENT_TIMESTAMP,
                nin_verification_data = ?,
                verification_status = 'nin_verified'
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $nin,
            json_encode($data),
            $this->userId
        ]);
        
        // Also update user's basic info if available and not already set
        $this->updateUserBasicInfo($data);

        // Overwrite profile fields with authoritative NIN data where available
        $this->applyNINDataToProfile($data);
    }

    /**
     * Apply selected NIN data to user's profile and users table (overwrite existing values)
     * Fields applied: first_name, last_name, date_of_birth, state_of_origin, lga_of_origin, city_of_birth, religion
     */
    private function applyNINDataToProfile($data) {
        try {
            // Update users table (name fields) if present in NIN data
            $userUpdates = [];
            $userParams = [];
            if (!empty($data['first_name'])) {
                $userUpdates[] = 'first_name = ?';
                $userParams[] = $data['first_name'];
            }
            if (!empty($data['last_name'])) {
                $userUpdates[] = 'last_name = ?';
                $userParams[] = $data['last_name'];
            }
            if (!empty($userUpdates)) {
                $userParams[] = $this->userId;
                $sql = 'UPDATE users SET ' . implode(', ', $userUpdates) . ' WHERE id = ?';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($userParams);
                error_log("NIN: updated users table for user {$this->userId}");
                
                // Update session with new names so header shows correct name immediately
                if (!empty($data['first_name'])) {
                    $_SESSION['first_name'] = $data['first_name'];
                }
                if (!empty($data['last_name'])) {
                    $_SESSION['last_name'] = $data['last_name'];
                }
            }

            // Prepare profile updates with column existence checks
            $profileUpdates = [];
            $profileParams = [];

            // Date of birth
            if (!empty($data['date_of_birth'])) {
                $profileUpdates[] = 'date_of_birth = ?';
                $profileParams[] = $data['date_of_birth'];
            }

            // State of origin - map from origin_state or fallback to residence_state
            if (!empty($data['origin_state'])) {
                if ($this->columnExists('state_of_origin')) {
                    $profileUpdates[] = 'state_of_origin = ?';
                    $profileParams[] = $data['origin_state'];
                }
            } elseif (!empty($data['residence_state'])) {
                if ($this->columnExists('state_of_origin')) {
                    $profileUpdates[] = 'state_of_origin = ?';
                    $profileParams[] = $data['residence_state'];
                }
            }

            // LGA of origin - map from origin_lga or fallback to residence_lga
            if (!empty($data['origin_lga'])) {
                if ($this->columnExists('lga_of_origin')) {
                    $profileUpdates[] = 'lga_of_origin = ?';
                    $profileParams[] = $data['origin_lga'];
                }
            } elseif (!empty($data['residence_lga'])) {
                if ($this->columnExists('lga_of_origin')) {
                    $profileUpdates[] = 'lga_of_origin = ?';
                    $profileParams[] = $data['residence_lga'];
                }
            }

            // City of birth - prioritize birth_lga, then origin_lga, then place_of_birth
            // Dojah API may return: birth_lga, birthlga, origin_lga, or place_of_birth
            $cityOfBirth = $data['birth_lga'] ?? $data['birthlga'] ?? $data['origin_lga'] ?? $data['place_of_birth'] ?? null;
            if (!empty($cityOfBirth) && $this->columnExists('city_of_birth')) {
                $profileUpdates[] = 'city_of_birth = ?';
                $profileParams[] = $cityOfBirth;
                error_log("NIN: City of birth set to: " . $cityOfBirth);
            }

            // Religion - check multiple possible field names
            $religion = $data['religion'] ?? $data['Religion'] ?? null;
            if (!empty($religion) && $this->columnExists('religion')) {
                $profileUpdates[] = 'religion = ?';
                $profileParams[] = $religion;
                error_log("NIN: Religion set to: " . $religion);
            } else {
                if (empty($religion)) {
                    error_log("NIN: Religion field is empty or not present in NIN data");
                }
                if (!$this->columnExists('religion')) {
                    error_log("NIN: Religion column does not exist in database");
                }
            }

            // Execute profile updates
            if (!empty($profileUpdates)) {
                $profileParams[] = $this->userId;
                $sql = 'UPDATE job_seeker_profiles SET ' . implode(', ', $profileUpdates) . ' WHERE user_id = ?';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($profileParams);
                error_log("NIN: applied " . count($profileUpdates) . " profile field updates for user {$this->userId}");
            }
        } catch (Exception $e) {
            error_log('Error applying NIN data to profile: ' . $e->getMessage());
        }
    }

    /**
     * Check if a column exists in job_seeker_profiles table
     */
    private function columnExists($columnName) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'job_seeker_profiles' 
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([$columnName]);
            $exists = ($stmt->fetchColumn() > 0);
            
            if (!$exists) {
                error_log("NIN: column '$columnName' not found in job_seeker_profiles, skipping");
            }
            
            return $exists;
        } catch (Exception $e) {
            error_log("Error checking column existence for '$columnName': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user's basic information from NIN data
     */
    private function updateUserBasicInfo($data) {
        // Get current user data
        $stmt = $this->pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        $user = $stmt->fetch();
        
        // Update phone if not set
        if (empty($user['phone']) && !empty($data['phone_number'])) {
            $stmt = $this->pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmt->execute([$data['phone_number'], $this->userId]);
        }
        
        // Update profile with additional data
        $updates = [];
        $params = [];
        
        if (!empty($data['date_of_birth'])) {
            $updates[] = "date_of_birth = ?";
            $params[] = $data['date_of_birth'];
        }
        
        if (!empty($data['gender'])) {
            $gender = strtolower($data['gender']) === 'm' ? 'male' : 'female';
            $updates[] = "gender = ?";
            $params[] = $gender;
        }
        
        // Always update profile picture from NIN photo if available
        if (!empty($data['photo'])) {
            error_log("NIN Photo data found, attempting to save...");
            $photoPath = $this->saveNINPhoto($data['photo']);
            if ($photoPath) {
                error_log("NIN Photo saved successfully: " . $photoPath);
                $updates[] = "profile_picture = ?";
                $params[] = $photoPath;
            } else {
                error_log("Failed to save NIN photo");
            }
        } else {
            error_log("No photo data in NIN response");
        }
        
        if (!empty($updates)) {
            $params[] = $this->userId;
            $sql = "UPDATE job_seeker_profiles SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            error_log("Profile updated with " . count($updates) . " fields");
        }
    }
    
    /**
     * Save NIN photo as profile picture
     */
    private function saveNINPhoto($photoData) {
        try {
            // Create uploads directory if it doesn't exist
            $uploadDir = dirname(__DIR__) . '/uploads/profile_pictures/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                error_log("Created directory: " . $uploadDir);
            }
            
            // Check if photo is base64 encoded or URL
            if (filter_var($photoData, FILTER_VALIDATE_URL)) {
                error_log("Photo is URL: " . $photoData);
                // Download photo from URL
                $imageContent = @file_get_contents($photoData);
                if ($imageContent === false) {
                    error_log("Failed to download photo from URL");
                    return null;
                }
            } else {
                error_log("Photo is base64 encoded, length: " . strlen($photoData));
                // Assume it's base64 encoded
                // Remove data URI scheme if present
                if (strpos($photoData, 'data:image') === 0) {
                    $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
                }
                $imageContent = base64_decode($photoData, true);
                if ($imageContent === false) {
                    error_log("Failed to decode base64 photo");
                    return null;
                }
            }
            
            // Generate unique filename
            $filename = 'nin_' . $this->userId . '_' . time() . '.jpg';
            $filePath = $uploadDir . $filename;
            
            error_log("Attempting to save to: " . $filePath);
            
            // Save the file
            if (file_put_contents($filePath, $imageContent)) {
                error_log("File saved successfully, size: " . filesize($filePath) . " bytes");
                // Return relative path for database
                return 'uploads/profile_pictures/' . $filename;
            } else {
                error_log("Failed to write file to disk");
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error saving NIN photo: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Sanitize verification data for frontend
     */
    private function sanitizeVerificationData($data) {
        return [
            'first_name' => $data['first_name'] ?? '',
            'middle_name' => $data['middle_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'date_of_birth' => $data['date_of_birth'] ?? '',
            'gender' => $data['gender'] ?? '',
            'phone_number' => $data['phone_number'] ?? ''
        ];
    }
    
    /**
     * Get verification status
     */
    public function getVerificationStatus() {
        $stmt = $this->pdo->prepare("
            SELECT nin, nin_verified, nin_verified_at, verification_status
            FROM job_seeker_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $profile = $stmt->fetch();
        
        return [
            'success' => true,
            'verified' => (bool)($profile['nin_verified'] ?? false),
            'nin' => $profile['nin'] ?? null,
            'verified_at' => $profile['nin_verified_at'] ?? null,
            'verification_status' => $profile['verification_status'] ?? 'pending'
        ];
    }
}

// Handle API requests
$api = new NINVerificationAPI($pdo, $userId);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'verify') {
        $nin = trim($_POST['nin'] ?? '');
        $result = $api->verifyNIN($nin);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} elseif ($method === 'GET') {
    if ($action === 'status') {
        $result = $api->getVerificationStatus();
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
