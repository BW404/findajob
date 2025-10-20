-- Optional: Create LGAs and States tables for location data
-- This is optional - the system will work without these tables using the 'location' field in jobs table

-- Create states table
CREATE TABLE IF NOT EXISTS `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lgas table (Local Government Areas)
CREATE TABLE IF NOT EXISTS `lgas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state_id` (`state_id`),
  CONSTRAINT `lgas_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for Nigerian states (optional)
INSERT IGNORE INTO `states` (`name`, `code`) VALUES
('Lagos', 'LA'),
('Abuja FCT', 'FC'),
('Kano', 'KN'),
('Rivers', 'RI'),
('Oyo', 'OY'),
('Kaduna', 'KD'),
('Ogun', 'OG'),
('Katsina', 'KT'),
('Anambra', 'AN'),
('Borno', 'BO');

-- Sample LGAs for Lagos (optional)
INSERT IGNORE INTO `lgas` (`state_id`, `name`) 
SELECT id, 'Ikeja' FROM states WHERE name = 'Lagos' LIMIT 1;
INSERT IGNORE INTO `lgas` (`state_id`, `name`) 
SELECT id, 'Lagos Island' FROM states WHERE name = 'Lagos' LIMIT 1;
INSERT IGNORE INTO `lgas` (`state_id`, `name`) 
SELECT id, 'Lagos Mainland' FROM states WHERE name = 'Lagos' LIMIT 1;
INSERT IGNORE INTO `lgas` (`state_id`, `name`) 
SELECT id, 'Surulere' FROM states WHERE name = 'Lagos' LIMIT 1;
INSERT IGNORE INTO `lgas` (`state_id`, `name`) 
SELECT id, 'Alimosho' FROM states WHERE name = 'Lagos' LIMIT 1;

-- Note: If you don't create these tables, the system will use the 'location' field from jobs table instead
-- To use full location data, you need to:
-- 1. Run this migration
-- 2. Import the full nigeria-locations.sql file from the database folder
-- 3. Update job posting forms to use state_id and lga_id fields
