<?php
/**
 * Employer NIN Verification API
 * Handles National Identification Number verification for Company Representatives using Dojah API
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

// Only employers can verify provider NIN
if (!isEmployer()) {
    echo json_encode(['success' => false, 'error' => 'Only employers can verify representative NIN.']);
    exit;
}

$userId = getCurrentUserId();

class EmployerNINVerificationAPI {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    /**
     * Verify Provider/Representative NIN using Dojah API
     */
    public function verifyProviderNIN($nin) {
        try {
            // Validate NIN format
            if (!$this->validateNIN($nin)) {
                return [
                    'success' => false, 
                    'error' => 'Invalid NIN format. NIN must be 11 digits.'
                ];
            }
            
            // Check if NIN is already verified for this employer
            if ($this->isNINAlreadyVerified($nin)) {
                return [
                    'success' => false,
                    'error' => 'This NIN has already been verified for your representative.'
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
                // Update employer profile with verification data
                $this->updateProfileWithVerificationData($nin, $apiResponse['data']);
                
                // Update transaction as completed
                $this->updateTransaction($transactionRef, 'completed', $apiResponse['data']);
                
                // Log successful verification
                $this->updateVerificationLog($logId, 'success', $apiResponse['data']);
                
                return [
                    'success' => true,
                    'message' => 'Representative NIN verified successfully!',
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
            error_log("Employer NIN Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during verification. Please try again later.'
            ];
        }
    }
    
    /**
     * Call Dojah API to verify NIN (using same endpoint as job seeker verification)
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
            error_log("Dojah API cURL Error (Employer): " . $curlError);
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
            error_log("Dojah API Error (Employer): HTTP $httpCode - " . json_encode($data));
            return [
                'success' => false,
                'error' => $errorMessage
            ];
        }
    }
    
    private function validateNIN($nin) {
        return preg_match('/^\d{11}$/', $nin);
    }
    
    private function isNINAlreadyVerified($nin) {
        $stmt = $this->pdo->prepare("
            SELECT provider_nin_verified 
            FROM employer_profiles 
            WHERE user_id = ? AND provider_nin = ? AND provider_nin_verified = 1
        ");
        $stmt->execute([$this->userId, $nin]);
        return $stmt->fetch() !== false;
    }
    
    private function isNINUsedByAnotherUser($nin) {
        // Check in job seeker profiles
        $stmt = $this->pdo->prepare("
            SELECT id FROM job_seeker_profiles 
            WHERE nin = ? AND nin_verified = 1
        ");
        $stmt->execute([$nin]);
        
        if ($stmt->fetch()) {
            return true;
        }
        
        // Check in other employer profiles
        $stmt = $this->pdo->prepare("
            SELECT id FROM employer_profiles 
            WHERE provider_nin = ? AND user_id != ? AND provider_nin_verified = 1
        ");
        $stmt->execute([$nin, $this->userId]);
        
        return $stmt->fetch() !== false;
    }
    
    private function createTransaction() {
        $reference = 'NIN_EMP_' . time() . '_' . $this->userId . '_' . bin2hex(random_bytes(4));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO verification_transactions 
            (user_id, transaction_type, amount, currency, status, reference)
            VALUES (?, 'nin_verification', ?, 'NGN', 'pending', ?)
        ");
        $stmt->execute([$this->userId, NIN_VERIFICATION_FEE, $reference]);
        
        return $reference;
    }
    
    private function updateTransaction($reference, $status, $metadata = null, $error = null) {
        $stmt = $this->pdo->prepare("
            UPDATE verification_transactions 
            SET status = ?, metadata = ?
            WHERE reference = ?
        ");
        
        $metadataJson = $metadata ? json_encode($metadata) : null;
        if ($error) {
            $metadataJson = json_encode(['error' => $error]);
        }
        
        $stmt->execute([$status, $metadataJson, $reference]);
    }
    
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
    
    private function updateVerificationLog($logId, $status, $apiResponse = null, $error = null) {
        $stmt = $this->pdo->prepare("
            UPDATE verification_audit_log 
            SET status = ?, api_response = ?, error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $status,
            $apiResponse ? json_encode($apiResponse) : null,
            $error,
            $logId
        ]);
    }
    
    private function updateProfileWithVerificationData($nin, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE employer_profiles 
            SET provider_nin = ?, 
                provider_nin_verified = 1, 
                provider_nin_verified_at = CURRENT_TIMESTAMP,
                provider_nin_data = ?
            WHERE user_id = ?
        ");
        
        $stmt->execute([
            $nin,
            json_encode($data),
            $this->userId
        ]);
        
        // Update provider's basic info and apply all NIN data
        $this->updateProviderBasicInfo($data);
        $this->applyNINDataToProfile($data);
    }
    
    /**
     * Apply NIN data to provider profile fields
     */
    private function applyNINDataToProfile($data) {
        try {
            // Update users table with NIN data
            $userUpdates = [];
            $userParams = [];
            
            if (!empty($data['first_name'])) {
                $userUpdates[] = 'first_name = ?';
                $userParams[] = $data['first_name'];
                $_SESSION['first_name'] = $data['first_name'];
            }
            
            if (!empty($data['last_name'])) {
                $userUpdates[] = 'last_name = ?';
                $userParams[] = $data['last_name'];
                $_SESSION['last_name'] = $data['last_name'];
            }
            
            if (!empty($userUpdates)) {
                $userParams[] = $this->userId;
                $sql = 'UPDATE users SET ' . implode(', ', $userUpdates) . ' WHERE id = ?';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($userParams);
                error_log("Employer NIN: updated users table for user {$this->userId}");
            }

            // Update provider profile fields
            $profileUpdates = [];
            $profileParams = [];

            if (!empty($data['first_name'])) {
                $profileUpdates[] = 'provider_first_name = ?';
                $profileParams[] = $data['first_name'];
            }

            if (!empty($data['last_name'])) {
                $profileUpdates[] = 'provider_last_name = ?';
                $profileParams[] = $data['last_name'];
            }

            if (!empty($data['date_of_birth'])) {
                $profileUpdates[] = 'provider_date_of_birth = ?';
                $profileParams[] = $data['date_of_birth'];
            }

            if (!empty($data['gender'])) {
                $gender = strtolower($data['gender']) === 'm' ? 'male' : (strtolower($data['gender']) === 'f' ? 'female' : strtolower($data['gender']));
                $profileUpdates[] = 'provider_gender = ?';
                $profileParams[] = $gender;
            }

            if (!empty($data['phone_number'])) {
                $profileUpdates[] = 'provider_phone = ?';
                $profileParams[] = $data['phone_number'];
            }

            // State of origin - map from origin_state or fallback to residence_state
            if (!empty($data['origin_state'])) {
                $profileUpdates[] = 'provider_state_of_origin = ?';
                $profileParams[] = $data['origin_state'];
                error_log("Employer NIN: State of origin set to: " . $data['origin_state']);
            } elseif (!empty($data['residence_state'])) {
                $profileUpdates[] = 'provider_state_of_origin = ?';
                $profileParams[] = $data['residence_state'];
                error_log("Employer NIN: State of origin set from residence: " . $data['residence_state']);
            }

            // LGA of origin - map from origin_lga or fallback to residence_lga
            if (!empty($data['origin_lga'])) {
                $profileUpdates[] = 'provider_lga_of_origin = ?';
                $profileParams[] = $data['origin_lga'];
                error_log("Employer NIN: LGA of origin set to: " . $data['origin_lga']);
            } elseif (!empty($data['residence_lga'])) {
                $profileUpdates[] = 'provider_lga_of_origin = ?';
                $profileParams[] = $data['residence_lga'];
                error_log("Employer NIN: LGA of origin set from residence: " . $data['residence_lga']);
            }

            // City of birth - prioritize birth_lga, then origin_lga, then place_of_birth
            $cityOfBirth = $data['birth_lga'] ?? $data['birthlga'] ?? $data['origin_lga'] ?? $data['place_of_birth'] ?? null;
            if (!empty($cityOfBirth)) {
                $profileUpdates[] = 'provider_city_of_birth = ?';
                $profileParams[] = $cityOfBirth;
                error_log("Employer NIN: City of birth set to: " . $cityOfBirth);
            }

            // Religion - check multiple possible field names
            $religion = $data['religion'] ?? $data['Religion'] ?? null;
            if (!empty($religion)) {
                $profileUpdates[] = 'provider_religion = ?';
                $profileParams[] = $religion;
                error_log("Employer NIN: Religion set to: " . $religion);
            } else {
                error_log("Employer NIN: Religion field is empty or not present in NIN data");
            }

            if (!empty($profileUpdates)) {
                $profileParams[] = $this->userId;
                $sql = 'UPDATE employer_profiles SET ' . implode(', ', $profileUpdates) . ' WHERE user_id = ?';
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($profileParams);
                error_log("Employer NIN: applied " . count($profileUpdates) . " provider field updates");
            }
        } catch (Exception $e) {
            error_log('Error applying NIN data to employer profile: ' . $e->getMessage());
        }
    }
    
    /**
     * Update provider's basic information from NIN data
     */
    private function updateProviderBasicInfo($data) {
        // Get current user data
        $stmt = $this->pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->execute([$this->userId]);
        $user = $stmt->fetch();
        
        // Update phone in users table if not set
        if (empty($user['phone']) && !empty($data['phone_number'])) {
            $stmt = $this->pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmt->execute([$data['phone_number'], $this->userId]);
        }
        
        // Update profile with additional data including photo
        $updates = [];
        $params = [];
        
        if (!empty($data['date_of_birth'])) {
            $updates[] = "provider_date_of_birth = ?";
            $params[] = $data['date_of_birth'];
        }
        
        if (!empty($data['gender'])) {
            $gender = strtolower($data['gender']) === 'm' ? 'male' : 'female';
            $updates[] = "provider_gender = ?";
            $params[] = $gender;
        }
        
        // Always update profile picture from NIN photo if available
        if (!empty($data['photo'])) {
            error_log("Employer NIN Photo data found, attempting to save...");
            $photoPath = $this->saveNINPhoto($data['photo']);
            if ($photoPath) {
                error_log("Employer NIN Photo saved successfully: " . $photoPath);
                $updates[] = "provider_profile_picture = ?";
                $params[] = $photoPath;
            } else {
                error_log("Failed to save employer NIN photo");
            }
        } else {
            error_log("No photo data in employer NIN response");
        }
        
        if (!empty($updates)) {
            $params[] = $this->userId;
            $sql = "UPDATE employer_profiles SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            error_log("Employer profile updated with " . count($updates) . " fields");
        }
    }
    
    /**
     * Save NIN photo as provider profile picture
     */
    private function saveNINPhoto($photoData) {
        try {
            // Create uploads directory if it doesn't exist
            $uploadDir = dirname(__DIR__) . '/uploads/nin-photos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                error_log("Created directory: " . $uploadDir);
            }
            
            // Check if photo is base64 encoded or URL
            if (filter_var($photoData, FILTER_VALIDATE_URL)) {
                error_log("Employer photo is URL: " . $photoData);
                // Download photo from URL
                $imageContent = @file_get_contents($photoData);
                if ($imageContent === false) {
                    error_log("Failed to download employer photo from URL");
                    return null;
                }
            } else {
                error_log("Employer photo is base64 encoded, length: " . strlen($photoData));
                // Assume it's base64 encoded - remove data URI scheme if present
                if (strpos($photoData, 'data:image') === 0) {
                    $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
                }
                $imageContent = base64_decode($photoData, true);
                if ($imageContent === false) {
                    error_log("Failed to decode base64 employer photo");
                    return null;
                }
            }
            
            // Generate unique filename
            $filename = 'provider_nin_' . $this->userId . '_' . time() . '.jpg';
            $filePath = $uploadDir . $filename;
            
            error_log("Attempting to save employer photo to: " . $filePath);
            
            // Save the file
            if (file_put_contents($filePath, $imageContent)) {
                error_log("Employer file saved successfully, size: " . filesize($filePath) . " bytes");
                // Return relative path for database
                return 'uploads/nin-photos/' . $filename;
            } else {
                error_log("Failed to write employer file to disk");
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error saving employer NIN photo: " . $e->getMessage());
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
            'phone_number' => $data['phone_number'] ?? '',
            'origin_state' => $data['origin_state'] ?? ($data['residence_state'] ?? ''),
            'origin_lga' => $data['origin_lga'] ?? ($data['residence_lga'] ?? ''),
            'city_of_birth' => $data['birth_lga'] ?? $data['birthlga'] ?? $data['origin_lga'] ?? $data['place_of_birth'] ?? '',
            'religion' => $data['religion'] ?? $data['Religion'] ?? ''
        ];
    }
    
    public function getVerificationStatus() {
        $stmt = $this->pdo->prepare("
            SELECT provider_nin, provider_nin_verified, provider_nin_verified_at 
            FROM employer_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch();
        
        return [
            'verified' => (bool)($result['provider_nin_verified'] ?? false),
            'nin' => $result['provider_nin'] ?? null,
            'verified_at' => $result['provider_nin_verified_at'] ?? null
        ];
    }
}

// Handle the request
$api = new EmployerNINVerificationAPI($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['nin'])) {
        echo json_encode(['success' => false, 'error' => 'NIN is required']);
        exit;
    }
    
    $result = $api->verifyProviderNIN($data['nin']);
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $api->getVerificationStatus();
    echo json_encode(['success' => true, 'data' => $status]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
