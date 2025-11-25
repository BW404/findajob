<?php
/**
 * CAC (Corporate Affairs Commission) Verification API
 * Verifies Nigerian company registration details using Dojah API
 * For employers/companies only
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/constants.php';

header('Content-Type: application/json');

// Ensure user is logged in and is an employer
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isEmployer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only employers can verify CAC']);
    exit;
}

$userId = getCurrentUserId();

class CACVerificationAPI {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    /**
     * Verify CAC details
     */
    public function verifyCACDetails($rcNumber, $companyType, $companyName) {
        try {
            // Validate inputs
            if (empty($rcNumber)) {
                return [
                    'success' => false,
                    'error' => 'RC Number is required'
                ];
            }
            
            if (empty($companyType)) {
                return [
                    'success' => false,
                    'error' => 'Company Type is required'
                ];
            }
            
            if (empty($companyName)) {
                return [
                    'success' => false,
                    'error' => 'Company Name is required'
                ];
            }
            
            // Validate company type
            $validTypes = ['BUSINESS_NAME', 'COMPANY', 'INCORPORATED_TRUSTEES', 'LIMITED_PARTNERSHIP', 'LIMITED_LIABILITY_PARTNERSHIP'];
            if (!in_array($companyType, $validTypes)) {
                return [
                    'success' => false,
                    'error' => 'Invalid company type. Must be one of: ' . implode(', ', $validTypes)
                ];
            }
            
            // Clean RC number (remove spaces, special characters)
            $rcNumber = preg_replace('/[^0-9A-Za-z]/', '', $rcNumber);
            
            // Call Dojah API
            $result = $this->callDojahCACVerification($rcNumber, $companyType);
            
            if (!$result['success']) {
                return $result;
            }
            
            // Get official company name from CAC
            $cacCompanyName = trim($result['data']['company_name'] ?? '');
            
            // Always use CAC's official company name - no name matching required
            // This ensures the profile uses the exact registered name
            
            // Store verification data in database and update company name to CAC's official name
            $this->storeCACVerification($rcNumber, $companyType, $result['data'], $cacCompanyName);
            
            return [
                'success' => true,
                'message' => 'CAC verification successful. Company name updated to match CAC records.',
                'data' => [
                    'company_name' => $result['data']['company_name'],
                    'rc_number' => $result['data']['rc_number'],
                    'type_of_company' => $result['data']['type_of_company'],
                    'status' => $result['data']['status'],
                    'date_of_registration' => $result['data']['date_of_registration'],
                    'address' => $result['data']['address'],
                    'state' => $result['data']['state'],
                    'city' => $result['data']['city'],
                    'lga' => $result['data']['lga'],
                    'email' => $result['data']['email']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("CAC verification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to verify CAC details. Please try again.'
            ];
        }
    }
    
    /**
     * Call Dojah CAC Verification API
     */
    private function callDojahCACVerification($rcNumber, $companyType) {
        $url = DOJAH_API_BASE_URL . '/kyc/cac/basic?' . http_build_query([
            'rc_number' => $rcNumber,
            'company_type' => $companyType
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
            error_log("Dojah CAC API cURL error: " . $error);
            return [
                'success' => false,
                'error' => 'Network error. Please try again.'
            ];
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // Log the full response for debugging
        error_log("Dojah CAC API Response (HTTP $httpCode): " . $response);
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'CAC verification failed. Please check your RC Number and Company Type.'
            ];
        }
        
        if (!isset($result['entity'])) {
            return [
                'success' => false,
                'error' => 'Invalid response from CAC verification service'
            ];
        }
        
        return [
            'success' => true,
            'data' => $result['entity']
        ];
    }
    
    /**
     * Store CAC verification data in database
     */
    private function storeCACVerification($rcNumber, $companyType, $cacData, $cacCompanyName = null) {
        $this->pdo->beginTransaction();
        
        try {
            // Update employer profile with CAC verification
            // Also update company_name to match CAC records exactly
            if ($cacCompanyName) {
                $stmt = $this->pdo->prepare("
                    UPDATE employer_profiles 
                    SET company_name = ?,
                        company_cac_number = ?,
                        company_type = ?,
                        company_cac_verified = 1,
                        company_cac_verified_at = NOW(),
                        company_cac_data = ?
                    WHERE user_id = ?
                ");
                
                $stmt->execute([
                    $cacCompanyName,
                    $rcNumber,
                    $companyType,
                    json_encode($cacData),
                    $this->userId
                ]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE employer_profiles 
                    SET company_cac_number = ?,
                        company_type = ?,
                        company_cac_verified = 1,
                        company_cac_verified_at = NOW(),
                        company_cac_data = ?
                    WHERE user_id = ?
                ");
                
                $stmt->execute([
                    $rcNumber,
                    $companyType,
                    json_encode($cacData),
                    $this->userId
                ]);
            }
            
            // Log verification attempt
            $stmt = $this->pdo->prepare("
                INSERT INTO verification_logs 
                (user_id, verification_type, verification_data, status, created_at)
                VALUES (?, 'CAC', ?, 'success', NOW())
            ");
            
            $logData = [
                'rc_number' => $rcNumber,
                'company_type' => $companyType,
                'company_name' => $cacData['company_name'],
                'status' => $cacData['status'],
                'verified_at' => date('Y-m-d H:i:s')
            ];
            
            $stmt->execute([
                $this->userId,
                json_encode($logData)
            ]);
            
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

// Handle API requests
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    $api = new CACVerificationAPI($pdo, $userId);
    
    switch ($action) {
        case 'verify_cac':
            $rcNumber = $input['rc_number'] ?? '';
            $companyType = $input['company_type'] ?? '';
            $companyName = $input['company_name'] ?? '';
            $result = $api->verifyCACDetails($rcNumber, $companyType, $companyName);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("CAC verification API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
