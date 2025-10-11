-- FindAJob Nigeria Database Schema
-- Authentication and Core Tables

CREATE DATABASE IF NOT EXISTS findajob_ng CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE findajob_ng;

-- Users table (unified for job seekers, employers, and admins)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('job_seeker', 'employer', 'admin') NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    email_verification_expires DATETIME,
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_email_verified (email_verified)
);

-- Job seeker profiles
CREATE TABLE job_seeker_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    state_of_origin VARCHAR(100),
    lga_of_origin VARCHAR(100),
    current_state VARCHAR(100),
    current_city VARCHAR(100),
    education_level ENUM('ssce', 'ond', 'hnd', 'bsc', 'msc', 'phd', 'other'),
    years_of_experience INT DEFAULT 0,
    job_status ENUM('looking', 'not_looking', 'employed_but_looking') DEFAULT 'looking',
    salary_expectation_min INT,
    salary_expectation_max INT,
    skills TEXT,
    bio TEXT,
    profile_picture VARCHAR(255),
    nin VARCHAR(11),
    bvn VARCHAR(11),
    is_verified BOOLEAN DEFAULT FALSE,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    subscription_type ENUM('free', 'pro') DEFAULT 'free',
    subscription_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_job_status (job_status),
    INDEX idx_verification_status (verification_status)
);

-- Employer profiles
CREATE TABLE employer_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    company_registration_number VARCHAR(100),
    industry VARCHAR(100),
    company_size ENUM('1-10', '11-50', '51-200', '201-500', '500+'),
    website VARCHAR(255),
    description TEXT,
    address TEXT,
    state VARCHAR(100),
    city VARCHAR(100),
    logo VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    subscription_type ENUM('free', 'pro') DEFAULT 'free',
    subscription_expires DATETIME,
    mini_site_enabled BOOLEAN DEFAULT FALSE,
    mini_site_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_company_name (company_name),
    INDEX idx_verification_status (verification_status)
);

-- Email verification logs
CREATE TABLE email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
);

-- Password reset logs
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
);

-- Login attempts (for security)
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success BOOLEAN NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
);

-- Nigerian states and LGAs
CREATE TABLE nigeria_states (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL UNIQUE,
    region ENUM('north_central', 'north_east', 'north_west', 'south_east', 'south_south', 'south_west') NOT NULL
);

CREATE TABLE nigeria_lgas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    state_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (state_id) REFERENCES nigeria_states(id) ON DELETE CASCADE,
    INDEX idx_state_id (state_id)
);

-- Insert sample Nigerian states
INSERT INTO nigeria_states (name, code, region) VALUES 
('Lagos', 'LA', 'south_west'),
('Abuja', 'FC', 'north_central'),
('Kano', 'KN', 'north_west'),
('Rivers', 'RI', 'south_south'),
('Oyo', 'OY', 'south_west'),
('Kaduna', 'KD', 'north_west'),
('Anambra', 'AN', 'south_east'),
('Delta', 'DE', 'south_south'),
('Ogun', 'OG', 'south_west'),
('Plateau', 'PL', 'north_central');

-- Insert sample LGAs for Lagos
INSERT INTO nigeria_lgas (state_id, name) VALUES 
(1, 'Ikeja'),
(1, 'Lagos Island'),
(1, 'Lagos Mainland'),
(1, 'Surulere'),
(1, 'Yaba'),
(1, 'Victoria Island'),
(1, 'Ikoyi'),
(1, 'Alimosho'),
(1, 'Agege'),
(1, 'Ifako-Ijaiye');

-- Job categories
CREATE TABLE job_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
);

-- Jobs table
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    category_id INT,
    job_type ENUM('permanent', 'contract', 'temporary', 'internship', 'nysc', 'part_time') NOT NULL,
    employment_type ENUM('full_time', 'part_time', 'contract', 'freelance', 'internship') NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    benefits TEXT,
    salary_min INT,
    salary_max INT,
    salary_currency VARCHAR(10) DEFAULT 'NGN',
    salary_period ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'monthly',
    location_type ENUM('onsite', 'remote', 'hybrid') DEFAULT 'onsite',
    state VARCHAR(100),
    city VARCHAR(100),
    address TEXT,
    experience_level ENUM('entry', 'mid', 'senior', 'executive') DEFAULT 'entry',
    education_level ENUM('ssce', 'ond', 'hnd', 'bsc', 'msc', 'phd', 'any') DEFAULT 'any',
    application_deadline DATE,
    application_email VARCHAR(255),
    application_url VARCHAR(500),
    company_name VARCHAR(255),
    company_logo VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    is_urgent BOOLEAN DEFAULT FALSE,
    is_remote_friendly BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    status ENUM('draft', 'active', 'paused', 'closed', 'expired') DEFAULT 'draft',
    featured_until DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE SET NULL,
    INDEX idx_employer_id (employer_id),
    INDEX idx_category_id (category_id),
    INDEX idx_job_type (job_type),
    INDEX idx_status (status),
    INDEX idx_location (state, city),
    INDEX idx_salary (salary_min, salary_max),
    INDEX idx_created_at (created_at),
    INDEX idx_featured (is_featured, featured_until),
    FULLTEXT KEY ft_search (title, description, requirements)
);

-- Job applications
CREATE TABLE job_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    job_seeker_id INT NOT NULL,
    cv_id INT,
    cover_letter TEXT,
    application_status ENUM('applied', 'viewed', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected') DEFAULT 'applied',
    employer_notes TEXT,
    interview_date DATETIME,
    interview_type ENUM('phone', 'video', 'in_person', 'online') DEFAULT 'video',
    interview_link VARCHAR(500),
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_at DATETIME,
    responded_at DATETIME,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (job_seeker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, job_seeker_id),
    INDEX idx_job_id (job_id),
    INDEX idx_job_seeker_id (job_seeker_id),
    INDEX idx_status (application_status),
    INDEX idx_applied_at (applied_at)
);

-- CVs table
CREATE TABLE cvs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content JSON,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT,
    is_primary BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    template_id VARCHAR(50),
    views_count INT DEFAULT 0,
    downloads_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_primary (is_primary),
    INDEX idx_is_public (is_public)
);

-- Insert sample job categories
INSERT INTO job_categories (name, slug, description, icon) VALUES 
('Technology', 'technology', 'Software development, IT, cybersecurity, and tech roles', '💻'),
('Banking & Finance', 'banking-finance', 'Banking, accounting, financial services, and investment', '🏦'),
('Oil & Gas', 'oil-gas', 'Petroleum, energy, and oil industry positions', '⛽'),
('Healthcare', 'healthcare', 'Medical, nursing, pharmaceutical, and health services', '🏥'),
('Education', 'education', 'Teaching, training, academic, and educational roles', '🎓'),
('Engineering', 'engineering', 'Civil, mechanical, electrical, and engineering disciplines', '⚙️'),
('Sales & Marketing', 'sales-marketing', 'Sales, marketing, advertising, and business development', '📈'),
('Government', 'government', 'Public sector, civil service, and government positions', '🏛️'),
('Manufacturing', 'manufacturing', 'Production, quality control, and manufacturing roles', '🏭'),
('Agriculture', 'agriculture', 'Farming, agribusiness, and agricultural development', '🌾'),
('Telecommunications', 'telecommunications', 'Telecom, networking, and communication services', '📱'),
('Construction', 'construction', 'Building, architecture, and construction industry', '🏗️'),
('Transportation', 'transportation', 'Logistics, shipping, and transportation services', '🚛'),
('Hospitality', 'hospitality', 'Hotels, restaurants, tourism, and hospitality services', '🏨'),
('Media & Entertainment', 'media-entertainment', 'Broadcasting, journalism, and entertainment industry', '🎬'),
('Legal', 'legal', 'Law, legal services, and judicial positions', '⚖️'),
('Human Resources', 'human-resources', 'HR, recruitment, and people management', '👥'),
('Customer Service', 'customer-service', 'Support, customer relations, and service roles', '📞'),
('Security', 'security', 'Safety, security services, and protection roles', '🔒'),
('Non-Profit', 'non-profit', 'NGO, charity, and social impact organizations', '🤝');

-- Transactions table for tracking payments
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'NGN',
    service_type ENUM('nin_verification', 'subscription', 'job_booster', 'cv_service') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_reference VARCHAR(255) UNIQUE NOT NULL,
    payment_method VARCHAR(50),
    payment_gateway VARCHAR(50),
    gateway_reference VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_service_type (service_type),
    INDEX idx_transaction_reference (transaction_reference)
);

-- Education history table
CREATE TABLE user_education (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    education_level ENUM('ssce', 'ond', 'hnd', 'bsc', 'msc', 'phd', 'other') NOT NULL,
    institution_name VARCHAR(255) NOT NULL,
    field_of_study VARCHAR(255),
    start_year YEAR,
    end_year YEAR,
    grade_result VARCHAR(100),
    is_current BOOLEAN DEFAULT FALSE,
    additional_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_education_level (education_level)
);

-- Work experience table
CREATE TABLE user_work_experience (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    industry VARCHAR(100),
    employment_type ENUM('full_time', 'part_time', 'contract', 'freelance', 'internship') DEFAULT 'full_time',
    location VARCHAR(255),
    start_date DATE,
    end_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    job_description TEXT,
    key_achievements TEXT,
    skills_used TEXT,
    salary_range VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_employment_type (employment_type),
    INDEX idx_is_current (is_current)
);