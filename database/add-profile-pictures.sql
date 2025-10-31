-- Add profile picture column to users table for easy access
-- This mirrors what's in the profile tables but makes it easier to access

ALTER TABLE users 
ADD COLUMN profile_picture VARCHAR(255) AFTER phone;

-- Update existing records from job_seeker_profiles
UPDATE users u
INNER JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
SET u.profile_picture = jsp.profile_picture
WHERE jsp.profile_picture IS NOT NULL;

-- Update existing records from employer_profiles (using logo as profile picture)
UPDATE users u
INNER JOIN employer_profiles ep ON u.id = ep.user_id
SET u.profile_picture = ep.logo
WHERE ep.logo IS NOT NULL;
