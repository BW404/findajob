<?php
// Creative Template
$p = $data['personal'];
$summary = $data['summary'];
$skills = $data['skills'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { 
            margin: 10mm;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        html, body {
            height: auto;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: "Montserrat", "Trebuchet MS", sans-serif;
            font-size: 10pt;
            color: #2d3748;
            line-height: 1.5;
        }
        .layout {
            display: flex;
            min-height: 100vh;
            max-height: 100vh;
            page-break-after: avoid;
        }
        .sidebar {
            width: 35%;
            background: linear-gradient(180deg, #8b5cf6 0%, #6d28d9 100%);
            color: white;
            padding: 30px 20px;
            page-break-inside: avoid;
        }
        .main {
            width: 65%;
            padding: 30px;
            page-break-inside: avoid;
        }
        .profile-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .name {
            font-size: 24pt;
            font-weight: 700;
            margin: 0 0 8px 0;
            letter-spacing: 1px;
        }
        .title {
            font-size: 12pt;
            font-weight: 300;
            margin: 0 0 20px 0;
            opacity: 0.9;
        }
        .contact-section {
            margin-bottom: 25px;
        }
        .sidebar .section-title {
            font-size: 11pt;
            font-weight: 700;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .contact-item {
            margin-bottom: 10px;
            font-size: 9pt;
            display: flex;
            align-items: flex-start;
            line-height: 1.4;
        }
        .contact-icon {
            margin-right: 10px;
            font-size: 11pt;
            min-width: 20px;
        }
        .contact-item a {
            color: white;
            text-decoration: none;
            word-break: break-all;
        }
        .skills-sidebar {
            margin-top: 25px;
        }
        .skill-item {
            margin-bottom: 8px;
            font-size: 9pt;
        }
        .skill-bar {
            background: rgba(255,255,255,0.2);
            height: 6px;
            border-radius: 3px;
            margin-top: 4px;
            overflow: hidden;
        }
        .skill-fill {
            background: #fbbf24;
            height: 100%;
            border-radius: 3px;
        }
        .main .section {
            margin-bottom: 30px;
        }
        .main .section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #8b5cf6;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 3px solid #8b5cf6;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-text {
            line-height: 1.7;
            color: #4a5568;
            text-align: justify;
        }
        .experience-item, .education-item {
            margin-bottom: 20px;
            position: relative;
            padding-left: 20px;
            border-left: 2px solid #e5e7eb;
        }
        .experience-item::before, .education-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #8b5cf6;
        }
        .job-title, .degree {
            color: #1a202c;
            font-size: 12pt;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .company, .institution {
            color: #8b5cf6;
            font-weight: 600;
            font-size: 10pt;
        }
        .dates-location {
            color: #718096;
            font-size: 9pt;
            margin-top: 2px;
            font-style: italic;
        }
        .description {
            margin-top: 8px;
        }
        .description ul {
            margin: 5px 0;
            padding-left: 18px;
        }
        .description li {
            margin-bottom: 4px;
            color: #4a5568;
            font-size: 9pt;
        }
        .description li::marker {
            color: #8b5cf6;
        }
        .skill-tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .skill-tag {
            background: #f3f4f6;
            color: #6d28d9;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 9pt;
            font-weight: 600;
            border: 1px solid #e5e7eb;
        }
        
        /* Print button - hidden in print */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .print-button:hover {
            background: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }
        
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        📄 Save as PDF
    </button>
    
    <div class="layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="profile-section">
                <h1 class="name"><?= strtoupper(htmlspecialchars($p['first_name'] . ' ' . $p['last_name'])) ?></h1>
                <div class="title"><?= htmlspecialchars($p['professional_title']) ?></div>
            </div>

            <!-- Contact -->
            <div class="contact-section">
                <h3 class="section-title">Contact</h3>
                <div class="contact-item">
                    <span class="contact-icon">✉</span>
                    <span><?= htmlspecialchars($p['email']) ?></span>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">📞</span>
                    <span><?= htmlspecialchars($p['phone']) ?></span>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">📍</span>
                    <span><?= htmlspecialchars($p['location']) ?></span>
                </div>
                <?php if ($p['linkedin']): ?>
                <div class="contact-item">
                    <span class="contact-icon">🔗</span>
                    <a href="<?= htmlspecialchars($p['linkedin']) ?>">LinkedIn Profile</a>
                </div>
                <?php endif; ?>
                <?php if ($p['website']): ?>
                <div class="contact-item">
                    <span class="contact-icon">🌐</span>
                    <a href="<?= htmlspecialchars($p['website']) ?>">Portfolio</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Skills in Sidebar -->
            <?php if (!empty($skills['technical'])): ?>
            <div class="skills-sidebar">
                <h3 class="section-title">Technical</h3>
                <?php foreach (array_slice($skills['technical'], 0, 8) as $skill): ?>
                <div class="skill-item">
                    <div><?= htmlspecialchars($skill) ?></div>
                    <div class="skill-bar">
                        <div class="skill-fill" style="width: <?= rand(70, 100) ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Languages -->
            <?php if (!empty($skills['languages'])): ?>
            <div class="skills-sidebar">
                <h3 class="section-title">Languages</h3>
                <?php foreach ($skills['languages'] as $lang): ?>
                <div style="font-size: 9pt; margin-bottom: 8px;">• <?= htmlspecialchars($lang) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main">
            <!-- Professional Summary -->
            <?php if (!empty($summary['text'])): ?>
            <div class="section">
                <h2 class="section-title">About Me</h2>
                <p class="summary-text"><?= nl2br(htmlspecialchars($summary['text'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Work Experience -->
            <?php if (!empty($data['experience'])): ?>
            <div class="section">
                <h2 class="section-title">Experience</h2>
                <?php foreach ($data['experience'] as $exp): ?>
                    <?php if (empty($exp['title']) || empty($exp['company'])) continue; ?>
                    <div class="experience-item">
                        <div class="job-title"><?= htmlspecialchars($exp['title']) ?></div>
                        <div class="company"><?= htmlspecialchars($exp['company']) ?></div>
                        <div class="dates-location">
                            <?php
                            $start = !empty($exp['start_date']) ? date('M Y', strtotime($exp['start_date'] . '-01')) : '';
                            $end = !empty($exp['end_date']) ? date('M Y', strtotime($exp['end_date'] . '-01')) : 'Present';
                            echo $start . ' - ' . $end . ' | ' . htmlspecialchars($exp['location']);
                            ?>
                        </div>
                        <?php if (!empty($exp['description'])): ?>
                        <div class="description">
                            <ul>
                                <?php
                                $lines = explode("\n", $exp['description']);
                                foreach ($lines as $line) {
                                    $line = trim($line);
                                    if (!empty($line)) {
                                        $line = ltrim($line, '•-*');
                                        echo '<li>' . htmlspecialchars(trim($line)) . '</li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Education -->
            <?php if (!empty($data['education'])): ?>
            <div class="section">
                <h2 class="section-title">Education</h2>
                <?php foreach ($data['education'] as $edu): ?>
                    <?php if (empty($edu['degree']) || empty($edu['institution'])) continue; ?>
                    <div class="education-item">
                        <div class="degree"><?= htmlspecialchars($edu['degree']) ?></div>
                        <div class="institution"><?= htmlspecialchars($edu['institution']) ?></div>
                        <div class="dates-location">
                            <?= htmlspecialchars($edu['start_year']) ?> - <?= !empty($edu['end_year']) ? htmlspecialchars($edu['end_year']) : 'Present' ?> | <?= htmlspecialchars($edu['location']) ?>
                            <?php if (!empty($edu['gpa'])): ?>
                                | GPA: <?= htmlspecialchars($edu['gpa']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($edu['description'])): ?>
                            <div class="description"><?= nl2br(htmlspecialchars($edu['description'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Additional Skills -->
            <?php if (!empty($skills['soft']) || !empty($skills['certifications'])): ?>
            <div class="section">
                <h2 class="section-title">Additional Skills</h2>
                
                <?php if (!empty($skills['soft'])): ?>
                <div style="margin-bottom: 15px;">
                    <strong style="color: #6d28d9;">Soft Skills:</strong>
                    <div class="skill-tags-container">
                        <?php foreach ($skills['soft'] as $skill): ?>
                            <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($skills['certifications'])): ?>
                <div>
                    <strong style="color: #6d28d9;">Certifications:</strong>
                    <div class="skill-tags-container">
                        <?php foreach ($skills['certifications'] as $cert): ?>
                            <span class="skill-tag"><?= htmlspecialchars($cert) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
