<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Clear any opcode cache
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Require authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Get user profile with all relevant data
    $stmt = $pdo->prepare("
        SELECT 
            u.first_name, u.last_name,
            jsp.skills, jsp.years_of_experience, jsp.education_level,
            jsp.current_state, jsp.current_city, jsp.job_status,
            jsp.salary_expectation_min, jsp.salary_expectation_max, jsp.bio
        FROM users u
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        echo json_encode(['error' => 'Profile not found']);
        exit;
    }
    
    // Parse user skills
    $userSkills = [];
    if (!empty($profile['skills'])) {
        $userSkills = array_map('trim', array_map('strtolower', explode(',', $profile['skills'])));
    }
    
    // Get user's recent job applications to understand preferences
    $stmt = $pdo->prepare("
        SELECT DISTINCT j.category_id as category, j.employment_type, j.location_type
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        WHERE ja.job_seeker_id = ?
        ORDER BY ja.applied_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $applicationHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build AI recommendation query
    $recommendations = getAIRecommendations($pdo, $profile, $userSkills, $applicationHistory, $userId);
    
    // Calculate profile completeness
    $profileCompleteness = calculateProfileCompleteness($profile);
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'profile_completeness' => $profileCompleteness,
        'total_matches' => count($recommendations),
        'has_sufficient_profile' => $profileCompleteness >= 40 // At least 40% complete to show jobs
    ]);
    
} catch (Exception $e) {
    error_log("AI Recommendations Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate recommendations: ' . $e->getMessage()
    ]);
}

/**
 * Generate AI-powered job recommendations
 */
function getAIRecommendations($pdo, $profile, $userSkills, $applicationHistory, $userId) {
    $recommendations = [];
    
    // Get jobs the user hasn't applied to yet
    $appliedJobs = [];
    try {
        $excludedJobsQuery = "SELECT job_id FROM job_applications WHERE job_seeker_id = ?";
        $stmt = $pdo->prepare($excludedJobsQuery);
        $stmt->execute([$userId]);
        $appliedJobs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Could not fetch applied jobs: " . $e->getMessage());
    }
    
    // Also exclude saved jobs to show fresh recommendations
    $savedJobs = [];
    try {
        $savedJobsQuery = "SELECT job_id FROM saved_jobs WHERE user_id = ?";
        $stmt = $pdo->prepare($savedJobsQuery);
        $stmt->execute([$userId]);
        $savedJobs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Could not fetch saved jobs: " . $e->getMessage());
    }
    
    $excludedJobs = array_merge($appliedJobs, $savedJobs);
    
    // Build base query
    $baseQuery = "
        SELECT 
            j.id, j.title, COALESCE(j.slug, CONCAT('job-', j.id)) as slug, 
            j.description, j.requirements, COALESCE(j.responsibilities, '') as responsibilities,
            COALESCE(j.category_id, 0) as category, 
            j.employment_type, j.location_type,
            COALESCE(j.salary_min, 0) as salary_min, 
            COALESCE(j.salary_max, 0) as salary_max, 
            COALESCE(j.salary_period, 'monthly') as salary_period,
            COALESCE(j.experience_level, 'entry') as experience_level, 
            COALESCE(j.education_level, 'secondary') as education_level,
            COALESCE(j.state, '') as location, COALESCE(j.city, '') as city,
            COALESCE(j.is_urgent, 0) as is_urgent, 
            COALESCE(j.is_remote_friendly, 0) as remote_friendly,
            j.created_at, j.application_deadline,
            COALESCE(j.views_count, 0) as views_count, 
            COALESCE(j.applications_count, 0) as applications_count,
            COALESCE(j.company_name, ep.company_name, 'Company') as company_name, 
            u.profile_picture as company_logo,
            0 as match_score,
            '' as match_reasons
        FROM jobs j
        LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
        LEFT JOIN users u ON j.employer_id = u.id
        WHERE j.status = 'active'
        AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())
    ";
    
    if (!empty($excludedJobs)) {
        $placeholders = str_repeat('?,', count($excludedJobs) - 1) . '?';
        $baseQuery .= " AND j.id NOT IN ($placeholders)";
    }
    
    // Strategy 1: Perfect Skills Match (Weight: 40%)
    $skillsMatchJobs = [];
    if (!empty($userSkills)) {
        $skillsQuery = $baseQuery . " AND (";
        $skillConditions = [];
        foreach ($userSkills as $skill) {
            $skillConditions[] = "LOWER(j.requirements) LIKE ?";
            $skillConditions[] = "LOWER(j.responsibilities) LIKE ?";
            $skillConditions[] = "LOWER(j.title) LIKE ?";
        }
        $skillsQuery .= implode(' OR ', $skillConditions) . ") LIMIT 20";
        
        $stmt = $pdo->prepare($skillsQuery);
        $paramIndex = 1;
        
        if (!empty($excludedJobs)) {
            foreach ($excludedJobs as $jobId) {
                $stmt->bindValue($paramIndex++, $jobId);
            }
        }
        
        foreach ($userSkills as $skill) {
            $stmt->bindValue($paramIndex++, "%$skill%");
            $stmt->bindValue($paramIndex++, "%$skill%");
            $stmt->bindValue($paramIndex++, "%$skill%");
        }
        
        $stmt->execute();
        $skillsMatchJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate match scores
        foreach ($skillsMatchJobs as &$job) {
            $score = 0;
            $reasons = [];
            
            // Skills matching - search in requirements and responsibilities
            $matchingSkills = [];
            $jobText = strtolower($job['requirements'] . ' ' . $job['responsibilities'] . ' ' . $job['title']);
            
            foreach ($userSkills as $skill) {
                if (strpos($jobText, $skill) !== false) {
                    $matchingSkills[] = $skill;
                }
            }
            
            if (count($matchingSkills) > 0) {
                $skillMatchPercent = (count($matchingSkills) / count($userSkills)) * 100;
                $score += min($skillMatchPercent * 0.4, 40); // Max 40 points for skills
                $reasons[] = count($matchingSkills) . " matching skill" . (count($matchingSkills) > 1 ? 's' : '');
            }
            
            // Experience level match
            if (matchesExperienceLevel($profile['years_of_experience'], $job['experience_level'])) {
                $score += 20; // 20% weight
                $reasons[] = "Experience level match";
            }
            
            // Education level match
            if (matchesEducationLevel($profile['education_level'], $job['education_level'])) {
                $score += 15; // 15% weight
                $reasons[] = "Education requirement met";
            }
            
            // Location preference
            if (!empty($profile['current_state'])) {
                if (stripos($job['location'], $profile['current_state']) !== false || $job['remote_friendly']) {
                    $score += 15; // 15% weight
                    $reasons[] = $job['remote_friendly'] ? "Remote work available" : "Location match";
                }
            }
            
            // Salary match
            if (!empty($profile['salary_expectation_min']) && !empty($job['salary_min'])) {
                if ($job['salary_min'] >= $profile['salary_expectation_min'] * 0.8) {
                    $score += 10; // 10% weight
                    $reasons[] = "Salary meets expectations";
                }
            }
            
            $job['match_score'] = round($score);
            $job['match_reasons'] = implode(' • ', $reasons);
        }
    }
    
    // Strategy 2: Location-based recommendations (Weight: 25%)
    $locationJobs = [];
    if (!empty($profile['current_state'])) {
        $locationQuery = $baseQuery . " AND (
            j.state LIKE ? OR 
            j.city LIKE ? OR 
            j.is_remote_friendly = 1
        ) LIMIT 15";
        
        $params = [];
        if (!empty($excludedJobs)) {
            $params = $excludedJobs;
        }
        $params[] = "%{$profile['current_state']}%";
        $params[] = "%{$profile['current_city']}%";
        
        $stmt = $pdo->prepare($locationQuery);
        $stmt->execute($params);
        $locationJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($locationJobs as &$job) {
            if ($job['match_score'] == 0) {
                $job['match_score'] = 25;
                $job['match_reasons'] = "Location preference";
            }
        }
    }
    
    // Strategy 3: Experience & Education Match (Weight: 20%)
    $experienceQuery = $baseQuery;
    $experienceConditions = [];
    $params = [];
    
    if (!empty($excludedJobs)) {
        $params = $excludedJobs;
    }
    
    if (!empty($profile['education_level'])) {
        $experienceConditions[] = "j.education_level IN ('any', ?)";
        $params[] = $profile['education_level'];
    }
    
    if (!empty($profile['years_of_experience'])) {
        $expLevel = getExperienceLevelFromYears($profile['years_of_experience']);
        $experienceConditions[] = "j.experience_level = ?";
        $params[] = $expLevel;
    }
    
    if (!empty($experienceConditions)) {
        $experienceQuery .= " AND (" . implode(' OR ', $experienceConditions) . ") LIMIT 15";
        $stmt = $pdo->prepare($experienceQuery);
        $stmt->execute($params);
        $experienceJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($experienceJobs as &$job) {
            if ($job['match_score'] == 0) {
                $job['match_score'] = 20;
                $job['match_reasons'] = "Experience & education match";
            }
        }
    } else {
        $experienceJobs = [];
    }
    
    // Strategy 4: Fresh & Trending jobs (Weight: 15%)
    $trendingQuery = $baseQuery . " ORDER BY j.views_count DESC, j.created_at DESC LIMIT 10";
    $params = !empty($excludedJobs) ? $excludedJobs : [];
    $stmt = $pdo->prepare($trendingQuery);
    $stmt->execute($params);
    $trendingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($trendingJobs as &$job) {
        if ($job['match_score'] == 0) {
            $job['match_score'] = 15;
            $job['match_reasons'] = "Trending opportunity";
        }
    }
    
    // Merge all recommendations and remove duplicates
    $allRecommendations = array_merge($skillsMatchJobs, $locationJobs, $experienceJobs, $trendingJobs);
    
    // Remove duplicates by job ID
    $uniqueJobs = [];
    foreach ($allRecommendations as $job) {
        if (!isset($uniqueJobs[$job['id']])) {
            $uniqueJobs[$job['id']] = $job;
        } else {
            // Keep the one with higher match score
            if ($job['match_score'] > $uniqueJobs[$job['id']]['match_score']) {
                $uniqueJobs[$job['id']] = $job;
            }
        }
    }
    
    // Sort by match score
    $recommendations = array_values($uniqueJobs);
    usort($recommendations, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    // Limit to top 20 recommendations
    $recommendations = array_slice($recommendations, 0, 20);
    
    // Format recommendations
    foreach ($recommendations as &$job) {
        $job['formatted_salary'] = formatSalary($job['salary_min'], $job['salary_max'], $job['salary_period']);
        $job['time_ago'] = timeAgo($job['created_at']);
        $job['days_left'] = daysUntilDeadline($job['application_deadline']);
        $job['match_level'] = getMatchLevel($job['match_score']);
        
        // Use slug if available, otherwise use ID
        if (!empty($job['slug'])) {
            $job['job_url'] = "/findajob/pages/jobs/job-details.php?slug=" . $job['slug'];
        } else {
            $job['job_url'] = "/findajob/pages/jobs/details.php?id=" . $job['id'];
        }
    }
    
    return $recommendations;
}

/**
 * Helper functions
 */
function matchesExperienceLevel($userYears, $jobLevel) {
    if ($jobLevel === 'any') return true;
    
    $userLevel = getExperienceLevelFromYears($userYears);
    return $userLevel === $jobLevel;
}

function getExperienceLevelFromYears($years) {
    if ($years === null || $years === '') return 'entry';
    if ($years < 2) return 'entry';
    if ($years < 5) return 'mid';
    if ($years < 10) return 'senior';
    return 'executive';
}

function matchesEducationLevel($userEdu, $jobEdu) {
    if ($jobEdu === 'any') return true;
    
    $eduLevels = ['ssce' => 1, 'ond' => 2, 'hnd' => 3, 'bsc' => 4, 'msc' => 5, 'phd' => 6];
    
    $userLevel = $eduLevels[strtolower($userEdu)] ?? 0;
    $jobLevel = $eduLevels[strtolower($jobEdu)] ?? 0;
    
    return $userLevel >= $jobLevel;
}

function calculateProfileCompleteness($profile) {
    $totalFields = 9;
    $filledFields = 0;
    
    if (!empty($profile['skills'])) $filledFields++;
    if (!empty($profile['years_of_experience']) && $profile['years_of_experience'] != '0') $filledFields++;
    if (!empty($profile['education_level'])) $filledFields++;
    if (!empty($profile['current_state'])) $filledFields++;
    if (!empty($profile['current_city'])) $filledFields++;
    if (!empty($profile['job_status'])) $filledFields++;
    if (!empty($profile['salary_expectation_min']) && $profile['salary_expectation_min'] > 0) $filledFields++;
    if (!empty($profile['salary_expectation_max']) && $profile['salary_expectation_max'] > 0) $filledFields++;
    if (!empty($profile['bio'])) $filledFields++;
    
    return round(($filledFields / $totalFields) * 100);
}

function formatSalary($min, $max, $period) {
    if (empty($min) && empty($max)) {
        return 'Negotiable';
    }
    
    $formatted = '₦' . number_format($min);
    if (!empty($max) && $max != $min) {
        $formatted .= ' - ₦' . number_format($max);
    }
    $formatted .= '/' . ($period === 'monthly' ? 'month' : $period);
    
    return $formatted;
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    
    return date('M j, Y', $time);
}

function daysUntilDeadline($deadline) {
    if (empty($deadline)) return null;
    
    $today = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $diff = $today->diff($deadlineDate);
    
    if ($diff->invert) return 0; // Deadline passed
    
    return $diff->days;
}

function getMatchLevel($score) {
    if ($score >= 80) return 'excellent';
    if ($score >= 60) return 'good';
    if ($score >= 40) return 'fair';
    return 'basic';
}
