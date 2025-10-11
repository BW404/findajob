<?php
/**
 * FindAJob Nigeria - Form Validators
 * Form validation helpers and rules
 */

class FormValidator {
    private $errors = [];
    
    /**
     * Validate registration data
     */
    public function validateRegistration($data) {
        $this->errors = [];
        
        // Required fields
        $required = ['user_type', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Email validation
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->errors['email'] = 'Please enter a valid email address';
            }
        }
        
        // Password validation
        if (!empty($data['password'])) {
            $passwordCheck = $this->validatePassword($data['password']);
            if (!$passwordCheck['is_valid']) {
                $this->errors['password'] = implode(', ', $passwordCheck['errors']);
            }
        }
        
        // User type validation
        if (!empty($data['user_type'])) {
            if (!in_array($data['user_type'], ['job_seeker', 'employer'])) {
                $this->errors['user_type'] = 'Invalid user type';
            }
        }
        
        // Phone validation (if provided)
        if (!empty($data['phone'])) {
            if (!$this->validatePhone($data['phone'])) {
                $this->errors['phone'] = 'Please enter a valid Nigerian phone number';
            }
        }
        
        // Name validation
        if (!empty($data['first_name'])) {
            if (strlen($data['first_name']) < 2) {
                $this->errors['first_name'] = 'First name must be at least 2 characters';
            }
            if (!preg_match('/^[a-zA-Z\s\'.-]+$/', $data['first_name'])) {
                $this->errors['first_name'] = 'First name contains invalid characters';
            }
        }
        
        if (!empty($data['last_name'])) {
            if (strlen($data['last_name']) < 2) {
                $this->errors['last_name'] = 'Last name must be at least 2 characters';
            }
            if (!preg_match('/^[a-zA-Z\s\'.-]+$/', $data['last_name'])) {
                $this->errors['last_name'] = 'Last name contains invalid characters';
            }
        }
        
        // Company specific validations for employers
        if (!empty($data['user_type']) && $data['user_type'] === 'employer') {
            if (empty($data['company_name'])) {
                $this->errors['company_name'] = 'Company name is required for employers';
            } elseif (strlen($data['company_name']) < 2) {
                $this->errors['company_name'] = 'Company name must be at least 2 characters';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate login data
     */
    public function validateLogin($data) {
        $this->errors = [];
        
        if (empty($data['email'])) {
            $this->errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Please enter a valid email address';
        }
        
        if (empty($data['password'])) {
            $this->errors['password'] = 'Password is required';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
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
        
        // Optional: Special characters
        // if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        //     $errors[] = 'Password must contain at least one special character';
        // }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate phone number (Nigerian format)
     */
    public function validatePhone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check various Nigerian phone number formats
        $patterns = [
            '/^0[789][01]\d{8}$/',      // 0803xxxxxxx, 0901xxxxxxx etc
            '/^234[789][01]\d{8}$/',    // 2348031234567
            '/^[789][01]\d{8}$/'        // 8031234567
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate email verification token
     */
    public function validateToken($token) {
        $this->errors = [];
        
        if (empty($token)) {
            $this->errors['token'] = 'Verification token is required';
        } elseif (strlen($token) !== 64) {
            $this->errors['token'] = 'Invalid verification token format';
        } elseif (!ctype_xdigit($token)) {
            $this->errors['token'] = 'Invalid verification token characters';
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate password reset data
     */
    public function validatePasswordReset($data) {
        $this->errors = [];
        
        if (empty($data['token'])) {
            $this->errors['token'] = 'Reset token is required';
        }
        
        if (empty($data['password'])) {
            $this->errors['password'] = 'New password is required';
        } else {
            $passwordCheck = $this->validatePassword($data['password']);
            if (!$passwordCheck['is_valid']) {
                $this->errors['password'] = implode(', ', $passwordCheck['errors']);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate job posting data
     */
    public function validateJobPost($data) {
        $this->errors = [];
        
        // Required fields
        $required = ['title', 'description', 'location', 'job_type', 'category'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Title validation
        if (!empty($data['title'])) {
            if (strlen($data['title']) < 5) {
                $this->errors['title'] = 'Job title must be at least 5 characters';
            }
            if (strlen($data['title']) > 100) {
                $this->errors['title'] = 'Job title must not exceed 100 characters';
            }
        }
        
        // Description validation
        if (!empty($data['description'])) {
            if (strlen($data['description']) < 50) {
                $this->errors['description'] = 'Job description must be at least 50 characters';
            }
        }
        
        // Salary validation
        if (!empty($data['salary_min']) && !empty($data['salary_max'])) {
            if (!is_numeric($data['salary_min']) || !is_numeric($data['salary_max'])) {
                $this->errors['salary'] = 'Salary must be numeric';
            } elseif ($data['salary_min'] > $data['salary_max']) {
                $this->errors['salary'] = 'Minimum salary cannot be greater than maximum salary';
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Sanitize input data
     */
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if there are validation errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Add custom error
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
}

// Convenience functions
function validateRegistration($data) {
    $validator = new FormValidator();
    $isValid = $validator->validateRegistration($data);
    return [
        'is_valid' => $isValid,
        'errors' => $validator->getErrors()
    ];
}

function validateLogin($data) {
    $validator = new FormValidator();
    $isValid = $validator->validateLogin($data);
    return [
        'is_valid' => $isValid,
        'errors' => $validator->getErrors()
    ];
}

function validatePassword($password) {
    $validator = new FormValidator();
    return $validator->validatePassword($password);
}

function validatePhone($phone) {
    $validator = new FormValidator();
    return $validator->validatePhone($phone);
}
?>