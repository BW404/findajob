<?php
// Modern Professional Template
$p = $data['personal'];
$summary = $data['summary'];
$skills = $data['skills'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { 
            margin: 15mm;
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
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #2d3748;
            line-height: 1.6;
        }
        .container {
            max-width: 210mm;
            margin: 0 auto;
            page-break-after: avoid;
            min-height: 100vh;
            height: auto;
            padding-bottom: 50px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin: -15mm -15mm 20px -15mm;
            page-break-after: avoid;
        }
        .name {
            font-size: 32pt;
            font-weight: 700;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
        }
        .title {
            font-size: 16pt;
            font-weight: 300;
            margin: 0 0 15px 0;
            opacity: 0.95;
        }
        .contact {
            font-size: 9pt;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            opacity: 0.9;
        }
        .contact-item {
            display: inline-flex;
            align-items: center;
        }
        .contact a {
            color: white;
            text-decoration: none;
        }
        .main-content {
            padding: 30px 40px 50px 40px;
            min-height: auto;
            height: auto;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
            width: 100%;
            overflow: visible;
            height: auto;
        }
        .section-title {
            font-size: 13pt;
            font-weight: 700;
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-text {
            text-align: justify;
            line-height: 1.7;
            color: #4a5568;
        }
        .experience-item, .education-item {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .item-header {
            margin-bottom: 5px;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .job-title, .degree {
            color: #1a202c;
            font-size: 12pt;
            font-weight: 700;
        }
        .company, .institution {
            color: #667eea;
            font-weight: 600;
            font-size: 11pt;
            margin-top: 2px;
        }
        .location {
            color: #718096;
            font-size: 9pt;
            font-style: italic;
        }
        .dates {
            color: #a0aec0;
            font-size: 9pt;
            font-weight: 600;
            white-space: nowrap;
        }
        .description {
            margin-top: 10px;
            line-height: 1.6;
        }
        .description ul {
            margin: 5px 0;
            padding-left: 18px;
        }
        .description li {
            margin-bottom: 5px;
            color: #4a5568;
        }
        .description li::marker {
            color: #667eea;
        }
        .skills-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            width: 100%;
            overflow: visible;
        }
        .skill-category {
            margin-bottom: 12px;
            width: 100%;
        }
        .skill-category-title {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
            font-size: 10pt;
        }
        .skill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            width: 100%;
            overflow: visible;
        }
        .skill-tag {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            color: #5a67d8;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 9pt;
            border: 1px solid #667eea30;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .gpa {
            color: #718096;
            font-size: 9pt;
            margin-top: 3px;
        }
        
        /* Print button - hidden in print */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .print-button:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }
        
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="openInNewTabAndPrint()">
        📄 Save as PDF
    </button>
    
    <script>
        function openInNewTabAndPrint() {
            // Open current page in new tab
            const newWindow = window.open(window.location.href, '_blank');
            
            // Wait for the new window to load, then trigger print
            if (newWindow) {
                newWindow.onload = function() {
                    setTimeout(function() {
                        newWindow.print();
                    }, 500);
                };
            } else {
                // If popup blocked, just print current page
                window.print();
            }
        }
    </script>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="name"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></h1>
            <div class="title"><?= htmlspecialchars($p['professional_title']) ?></div>
            <div class="contact">
                <span class="contact-item">✉ <?= htmlspecialchars($p['email']) ?></span>
                <span class="contact-item">📞 <?= htmlspecialchars($p['phone']) ?></span>
                <span class="contact-item">📍 <?= htmlspecialchars($p['location']) ?></span>
                <?php if ($p['linkedin']): ?>
                    <span class="contact-item">🔗 <a href="<?= htmlspecialchars($p['linkedin']) ?>">LinkedIn</a></span>
                <?php endif; ?>
                <?php if ($p['website']): ?>
                    <span class="contact-item">🌐 <a href="<?= htmlspecialchars($p['website']) ?>">Portfolio</a></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="main-content">
            <!-- Professional Summary -->
            <?php if (!empty($summary['text'])): ?>
            <div class="section">
                <h2 class="section-title">Professional Summary</h2>
                <p class="summary-text"><?= nl2br(htmlspecialchars($summary['text'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Work Experience -->
            <?php if (!empty($data['experience'])): ?>
            <div class="section">
                <h2 class="section-title">Work Experience</h2>
                <?php foreach ($data['experience'] as $exp): ?>
                    <?php if (empty($exp['title']) || empty($exp['company'])) continue; ?>
                    <div class="experience-item">
                        <div class="item-header">
                            <div class="flex-between">
                                <div class="job-title"><?= htmlspecialchars($exp['title']) ?></div>
                                <div class="dates">
                                    <?php
                                    $start = !empty($exp['start_date']) ? date('M Y', strtotime($exp['start_date'] . '-01')) : '';
                                    $end = !empty($exp['end_date']) ? date('M Y', strtotime($exp['end_date'] . '-01')) : 'Present';
                                    echo $start . ' - ' . $end;
                                    ?>
                                </div>
                            </div>
                            <div class="company"><?= htmlspecialchars($exp['company']) ?></div>
                            <div class="location"><?= htmlspecialchars($exp['location']) ?></div>
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
                        <div class="flex-between">
                            <div class="degree"><?= htmlspecialchars($edu['degree']) ?></div>
                            <div class="dates">
                                <?= htmlspecialchars($edu['start_year']) ?> - 
                                <?= !empty($edu['end_year']) ? htmlspecialchars($edu['end_year']) : 'Present' ?>
                            </div>
                        </div>
                        <div class="institution"><?= htmlspecialchars($edu['institution']) ?></div>
                        <div class="location"><?= htmlspecialchars($edu['location']) ?></div>
                        <?php if (!empty($edu['gpa'])): ?>
                            <div class="gpa">GPA: <?= htmlspecialchars($edu['gpa']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($edu['description'])): ?>
                            <div class="description"><?= nl2br(htmlspecialchars($edu['description'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Skills -->
            <div class="section">
                <h2 class="section-title">Skills & Competencies</h2>
                <div class="skills-container">
                    <?php if (!empty($skills['technical'])): ?>
                    <div class="skill-category">
                        <div class="skill-category-title">Technical Skills</div>
                        <div class="skill-list">
                            <?php foreach ($skills['technical'] as $skill): ?>
                                <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($skills['soft'])): ?>
                    <div class="skill-category">
                        <div class="skill-category-title">Soft Skills</div>
                        <div class="skill-list">
                            <?php foreach ($skills['soft'] as $skill): ?>
                                <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($skills['languages'])): ?>
                    <div class="skill-category">
                        <div class="skill-category-title">Languages</div>
                        <div class="skill-list">
                            <?php foreach ($skills['languages'] as $skill): ?>
                                <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($skills['certifications'])): ?>
                    <div class="skill-category">
                        <div class="skill-category-title">Certifications</div>
                        <div class="skill-list">
                            <?php foreach ($skills['certifications'] as $skill): ?>
                                <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
