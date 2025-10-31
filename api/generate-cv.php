<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isJobSeeker()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $userId = getCurrentUserId();
    
    // Collect all form data
    $cvData = [
        'personal' => [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'professional_title' => $_POST['professional_title'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'location' => $_POST['location'] ?? '',
            'linkedin' => $_POST['linkedin'] ?? '',
            'website' => $_POST['website'] ?? ''
        ],
        'summary' => [
            'years_experience' => $_POST['years_experience'] ?? '',
            'industry' => $_POST['industry'] ?? '',
            'text' => $_POST['professional_summary'] ?? ''
        ],
        'experience' => $_POST['experience'] ?? [],
        'education' => $_POST['education'] ?? [],
        'skills' => [
            'technical' => array_filter(array_map('trim', explode(',', $_POST['technical_skills'] ?? ''))),
            'soft' => array_filter(array_map('trim', explode(',', $_POST['soft_skills'] ?? ''))),
            'languages' => array_filter(array_map('trim', explode(',', $_POST['languages'] ?? ''))),
            'certifications' => array_filter(array_map('trim', explode(',', $_POST['certifications'] ?? '')))
        ],
        'references' => $_POST['references'] ?? [],
        'include_references' => isset($_POST['include_references']),
        'template' => $_POST['template'] ?? 'modern'
    ];
    
    $cvTitle = $_POST['cv_title'] ?? 'My CV';
    
    // Generate HTML CV based on template
    $htmlContent = generateHTMLCV($cvData);
    
    // Store CV data in session for preview (don't save to database)
    $_SESSION['generated_cv'] = [
        'title' => $cvTitle,
        'html' => $htmlContent,
        'data' => $cvData,
        'generated_at' => time()
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'CV generated successfully',
        'preview' => true
    ]);
    
} catch (Exception $e) {
    error_log("CV Generation Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while generating your CV',
        'details' => $e->getMessage() // Include error details for debugging
    ]);
}

// Generate HTML CV from data
function generateHTMLCV($data) {
    $template = $data['template'] ?? 'modern';
    
    // Load template file
    $templatePath = __DIR__ . "/../templates/cv/{$template}.php";
    
    if (file_exists($templatePath)) {
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
    
    // Fallback to default template
    return generateDefaultTemplate($data);
}

// Generate default HTML template
function generateDefaultTemplate($data) {
    $p = $data['personal'];
    $summary = $data['summary'];
    $skills = $data['skills'];
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20mm; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .name {
            font-size: 28pt;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
        }
        .title {
            font-size: 14pt;
            color: #6b7280;
            margin: 5px 0;
        }
        .contact {
            font-size: 10pt;
            color: #6b7280;
            margin-top: 10px;
        }
        .contact a {
            color: #3b82f6;
            text-decoration: none;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #1e40af;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .experience-item, .education-item {
            margin-bottom: 15px;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            margin-bottom: 3px;
        }
        .job-title, .degree {
            color: #111827;
            font-size: 12pt;
        }
        .company, .institution {
            color: #3b82f6;
            font-weight: 600;
        }
        .location, .dates {
            color: #6b7280;
            font-size: 10pt;
        }
        .description {
            margin-top: 8px;
            line-height: 1.5;
        }
        .description ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .description li {
            margin-bottom: 3px;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .skill-category {
            margin-bottom: 10px;
        }
        .skill-category-title {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .skill-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .skill-tag {
            background: #eff6ff;
            color: #1e40af;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 10pt;
        }
    </style>
</head>
<body>';
    
    // Header
    $html .= '<div class="header">';
    $html .= '<h1 class="name">' . htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) . '</h1>';
    $html .= '<div class="title">' . htmlspecialchars($p['professional_title']) . '</div>';
    $html .= '<div class="contact">';
    $html .= htmlspecialchars($p['email']) . ' | ' . htmlspecialchars($p['phone']) . ' | ' . htmlspecialchars($p['location']);
    if ($p['linkedin']) {
        $html .= ' | <a href="' . htmlspecialchars($p['linkedin']) . '">LinkedIn</a>';
    }
    if ($p['website']) {
        $html .= ' | <a href="' . htmlspecialchars($p['website']) . '">Website</a>';
    }
    $html .= '</div>';
    $html .= '</div>';
    
    // Professional Summary
    if (!empty($summary['text'])) {
        $html .= '<div class="section">';
        $html .= '<h2 class="section-title">Professional Summary</h2>';
        $html .= '<p>' . nl2br(htmlspecialchars($summary['text'])) . '</p>';
        $html .= '</div>';
    }
    
    // Work Experience
    if (!empty($data['experience'])) {
        $html .= '<div class="section">';
        $html .= '<h2 class="section-title">Work Experience</h2>';
        
        foreach ($data['experience'] as $exp) {
            if (empty($exp['title']) || empty($exp['company'])) continue;
            
            $html .= '<div class="experience-item">';
            $html .= '<div class="item-header">';
            $html .= '<div>';
            $html .= '<div class="job-title">' . htmlspecialchars($exp['title']) . '</div>';
            $html .= '<div class="company">' . htmlspecialchars($exp['company']) . ' • ' . htmlspecialchars($exp['location']) . '</div>';
            $html .= '</div>';
            $html .= '<div class="dates">';
            $startDate = !empty($exp['start_date']) ? date('M Y', strtotime($exp['start_date'] . '-01')) : '';
            $endDate = !empty($exp['end_date']) ? date('M Y', strtotime($exp['end_date'] . '-01')) : 'Present';
            $html .= $startDate . ' - ' . $endDate;
            $html .= '</div>';
            $html .= '</div>';
            
            if (!empty($exp['description'])) {
                $html .= '<div class="description">';
                $lines = explode("\n", $exp['description']);
                $html .= '<ul>';
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $line = ltrim($line, '•-*');
                        $html .= '<li>' . htmlspecialchars(trim($line)) . '</li>';
                    }
                }
                $html .= '</ul>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    // Education
    if (!empty($data['education'])) {
        $html .= '<div class="section">';
        $html .= '<h2 class="section-title">Education</h2>';
        
        foreach ($data['education'] as $edu) {
            if (empty($edu['degree']) || empty($edu['institution'])) continue;
            
            $html .= '<div class="education-item">';
            $html .= '<div class="item-header">';
            $html .= '<div>';
            $html .= '<div class="degree">' . htmlspecialchars($edu['degree']) . '</div>';
            $html .= '<div class="institution">' . htmlspecialchars($edu['institution']) . ' • ' . htmlspecialchars($edu['location']) . '</div>';
            $html .= '</div>';
            $html .= '<div class="dates">' . htmlspecialchars($edu['start_year']) . ' - ' . htmlspecialchars($edu['end_year']) . '</div>';
            $html .= '</div>';
            
            if (!empty($edu['gpa'])) {
                $html .= '<div style="margin-top: 5px; color: #6b7280;">GPA: ' . htmlspecialchars($edu['gpa']) . '</div>';
            }
            
            if (!empty($edu['description'])) {
                $html .= '<div class="description">' . nl2br(htmlspecialchars($edu['description'])) . '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    // Skills
    $html .= '<div class="section">';
    $html .= '<h2 class="section-title">Skills & Competencies</h2>';
    $html .= '<div class="skills-grid">';
    
    if (!empty($skills['technical'])) {
        $html .= '<div class="skill-category">';
        $html .= '<div class="skill-category-title">Technical Skills</div>';
        $html .= '<div class="skill-tags">';
        foreach ($skills['technical'] as $skill) {
            $html .= '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    
    if (!empty($skills['soft'])) {
        $html .= '<div class="skill-category">';
        $html .= '<div class="skill-category-title">Soft Skills</div>';
        $html .= '<div class="skill-tags">';
        foreach ($skills['soft'] as $skill) {
            $html .= '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    
    if (!empty($skills['languages'])) {
        $html .= '<div class="skill-category">';
        $html .= '<div class="skill-category-title">Languages</div>';
        $html .= '<div class="skill-tags">';
        foreach ($skills['languages'] as $skill) {
            $html .= '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    
    if (!empty($skills['certifications'])) {
        $html .= '<div class="skill-category">';
        $html .= '<div class="skill-category-title">Certifications</div>';
        $html .= '<div class="skill-tags">';
        foreach ($skills['certifications'] as $skill) {
            $html .= '<span class="skill-tag">' . htmlspecialchars($skill) . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    // References
    if (!empty($data['include_references']) && !empty($data['references'])) {
        $hasValidReference = false;
        foreach ($data['references'] as $ref) {
            if (!empty($ref['name']) && !empty($ref['title'])) {
                $hasValidReference = true;
                break;
            }
        }
        
        if ($hasValidReference) {
            $html .= '<div class="section">';
            $html .= '<h2 class="section-title">References</h2>';
            
            foreach ($data['references'] as $ref) {
                if (empty($ref['name']) || empty($ref['title'])) continue;
                
                $html .= '<div class="education-item" style="margin-bottom: 12px;">';
                $html .= '<div style="font-weight: 600; color: #111827; font-size: 11pt; margin-bottom: 2px;">' . htmlspecialchars($ref['name']) . '</div>';
                $html .= '<div style="color: #3b82f6; font-weight: 600; font-size: 10pt;">' . htmlspecialchars($ref['title']);
                
                if (!empty($ref['company'])) {
                    $html .= ' • ' . htmlspecialchars($ref['company']);
                }
                
                $html .= '</div>';
                
                if (!empty($ref['relationship'])) {
                    $html .= '<div style="color: #6b7280; font-size: 9pt; margin-top: 2px;">Relationship: ' . htmlspecialchars($ref['relationship']) . '</div>';
                }
                
                $contactInfo = [];
                if (!empty($ref['phone'])) {
                    $contactInfo[] = htmlspecialchars($ref['phone']);
                }
                if (!empty($ref['email'])) {
                    $contactInfo[] = htmlspecialchars($ref['email']);
                }
                
                if (!empty($contactInfo)) {
                    $html .= '<div style="color: #6b7280; font-size: 9pt; margin-top: 2px;">' . implode(' • ', $contactInfo) . '</div>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= '</body></html>';
    
    return $html;
}

// Generate PDF from HTML
function generatePDFFromHTML($html) {
    // Check if DomPDF is available
    if (class_exists('Dompdf\Dompdf')) {
        try {
            require_once '../vendor/autoload.php';
            
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            return $dompdf->output();
        } catch (Exception $e) {
            error_log("DomPDF Error: " . $e->getMessage());
            // Fall through to next method
        }
    }
    
    // Fallback: Use wkhtmltopdf if available
    if (isCommandAvailable('wkhtmltopdf')) {
        try {
            $tempHtml = sys_get_temp_dir() . '/cv_' . uniqid() . '.html';
            $tempPdf = sys_get_temp_dir() . '/cv_' . uniqid() . '.pdf';
            
            file_put_contents($tempHtml, $html);
            exec("wkhtmltopdf $tempHtml $tempPdf");
            
            $pdf = file_get_contents($tempPdf);
            
            unlink($tempHtml);
            unlink($tempPdf);
            
            return $pdf;
        } catch (Exception $e) {
            error_log("wkhtmltopdf Error: " . $e->getMessage());
            // Fall through to HTML fallback
        }
    }
    
    // Last resort: Return HTML (browsers can print to PDF)
    error_log("Using HTML fallback - no PDF generator available");
    return $html;
}

function isCommandAvailable($command) {
    $return = shell_exec(sprintf("which %s", escapeshellarg($command)));
    return !empty($return);
}

?>
