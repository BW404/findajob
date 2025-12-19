-- Internship Management and Badge System
-- Date: December 16, 2025

-- Add internship tracking table
CREATE TABLE IF NOT EXISTS `internships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `job_seeker_id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `status` enum('pending','active','completed','terminated') DEFAULT 'pending',
  `completion_confirmed_by_employer` tinyint(1) DEFAULT 0,
  `completion_confirmed_at` timestamp NULL DEFAULT NULL,
  `employer_feedback` text,
  `performance_rating` tinyint(1) DEFAULT NULL COMMENT '1-5 rating',
  `badge_awarded` tinyint(1) DEFAULT 0,
  `badge_awarded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `application_id` (`application_id`),
  KEY `job_seeker_id` (`job_seeker_id`),
  KEY `employer_id` (`employer_id`),
  KEY `status` (`status`),
  UNIQUE KEY `unique_application_internship` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add internship badges table
CREATE TABLE IF NOT EXISTS `internship_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_seeker_id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration_months` int(11) NOT NULL,
  `performance_rating` tinyint(1) DEFAULT NULL,
  `employer_feedback` text,
  `is_visible` tinyint(1) DEFAULT 1,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_seeker_id` (`job_seeker_id`),
  KEY `internship_id` (`internship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints
ALTER TABLE `internships`
  ADD CONSTRAINT `internships_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internships_application_fk` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internships_job_seeker_fk` FOREIGN KEY (`job_seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internships_employer_fk` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `internship_badges`
  ADD CONSTRAINT `badges_job_seeker_fk` FOREIGN KEY (`job_seeker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `badges_internship_fk` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE;

-- Add index for faster badge retrieval on profiles
CREATE INDEX idx_badges_visible ON internship_badges(job_seeker_id, is_visible);
CREATE INDEX idx_internship_status ON internships(employer_id, status);
