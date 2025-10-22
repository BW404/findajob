-- Create saved_jobs table for job seeker favorites
CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_job_unique` (`user_id`, `job_id`),
  KEY `user_id` (`user_id`),
  KEY `job_id` (`job_id`),
  KEY `saved_at` (`saved_at`),
  CONSTRAINT `saved_jobs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_jobs_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add saved_jobs_count to jobs table (optional, for performance)
ALTER TABLE `jobs` 
ADD COLUMN IF NOT EXISTS `saved_count` int(11) DEFAULT 0 AFTER `applications_count`;
