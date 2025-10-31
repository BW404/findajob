<?php
// Technical Template - Skills-focused for developers
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
            margin: 15mm;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        html {
            height: 100%;
        }
        body {
            font-family: "Consolas", "Monaco", "Courier New", monospace;
            font-size: 9.5pt;
            color: #1f2937;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
            height: auto;
            page-break-after: avoid;
        }
        .header {
            border: 2px solid #ef4444;
            padding: 20px;
            margin-bottom: 20px;
            background: #fef2f2;
        }
        .name {
            font-size: 28pt;
            font-weight: 700;
            color: #dc2626;
            margin: 0;
            font-family: "Courier New", monospace;
        }
        .name::before {
            content: '> ';
            color: #ef4444;
        }
        .title {
            font-size: 13pt;
            color: #991b1b;
            margin: 5px 0 15px 20px;
            font-weight: 600;
        }
        .contact {
            margin-left: 20px;
            font-size: 9pt;
            color: #4b5563;
        }
        .contact-line {
            margin: 3px 0;
        }
        .contact-line::before {
            content: '// ';
            color: #9ca3af;
        }
        .contact a {
            color: #ef4444;
            text-decoration: none;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 12pt;
            font-weight: 700;
            color: #dc2626;
            margin: 0 0 12px 0;
            padding: 8px 12px;
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            font-family: "Courier New", monospace;
        }
        .section-title::before {
            content: '# ';
            color: #ef4444;
        }
        .summary-text {
            padding: 12px;
            background: #f9fafb;
            border-left: 3px solid #9ca3af;
            line-height: 1.7;
            font-family: Arial, sans-serif;
        }
        .code-block {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: "Consolas", monospace;
            font-size: 9pt;
        }
        .code-comment {
            color: #9ca3af;
        }
        .code-key {
            color: #fbbf24;
        }
        .code-value {
            color: #34d399;
        }
        .code-string {
            color: #60a5fa;
        }
        .experience-item, .education-item {
            margin-bottom: 20px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
        }
        .job-title, .degree {
            color: #dc2626;
            font-size: 11pt;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .company, .institution {
            color: #4b5563;
            font-weight: 600;
        }
        .meta-info {
            color: #6b7280;
            font-size: 8.5pt;
            margin-top: 3px;
        }
        .description {
            margin-top: 10px;
            font-family: Arial, sans-serif;
        }
        .description ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .description li {
            margin-bottom: 4px;
            color: #374151;
        }
        .description li::marker {
            content: '▸ ';
            color: #ef4444;
        }
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }
        .tech-badge {
            background: #1f2937;
            color: #34d399;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 8pt;
            border: 1px solid #374151;
            font-family: "Courier New", monospace;
        }
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        .skill-category {
            background: #f9fafb;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-top: 3px solid #ef4444;
        }
        .skill-category-title {
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 8px;
            font-size: 9pt;
        }
        .skill-category-title::before {
            content: 'const ';
            color: #9ca3af;
            font-weight: 400;
        }
        .skill-category-title::after {
            content: ' = [';
            color: #9ca3af;
            font-weight: 400;
        }
        .skill-list {
            font-size: 8.5pt;
            line-height: 1.8;
        }
        .skill-list div::before {
            content: '  "';
            color: #9ca3af;
        }
        .skill-list div::after {
            content: '",';
            color: #9ca3af;
        }
        .skill-list div:last-child::after {
            content: '"';
        }
        
        /* Print button - hidden in print */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .print-button:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
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
    
    <!-- Header -->
    <div class="header">
        <h1 class="name"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></h1>
        <div class="title"><?= htmlspecialchars($p['professional_title']) ?></div>
        <div class="contact">
            <div class="contact-line">Email: <?= htmlspecialchars($p['email']) ?></div>
            <div class="contact-line">Phone: <?= htmlspecialchars($p['phone']) ?></div>
            <div class="contact-line">Location: <?= htmlspecialchars($p['location']) ?></div>
            <?php if ($p['linkedin']): ?>
                <div class="contact-line">LinkedIn: <a href="<?= htmlspecialchars($p['linkedin']) ?>">Profile</a></div>
            <?php endif; ?>
            <?php if ($p['website']): ?>
                <div class="contact-line">Portfolio: <a href="<?= htmlspecialchars($p['website']) ?>"><?= htmlspecialchars(parse_url($p['website'], PHP_URL_HOST)) ?></a></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Professional Summary -->
    <?php if (!empty($summary['text'])): ?>
    <div class="section">
        <h2 class="section-title">ABOUT</h2>
        <div class="summary-text"><?= nl2br(htmlspecialchars($summary['text'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Technical Skills -->
    <?php if (!empty($skills['technical']) || !empty($skills['soft'])): ?>
    <div class="section">
        <h2 class="section-title">TECH STACK</h2>
        <div class="skills-grid">
            <?php if (!empty($skills['technical'])): ?>
            <div class="skill-category">
                <div class="skill-category-title">TECHNICAL</div>
                <div class="skill-list">
                    <?php foreach ($skills['technical'] as $skill): ?>
                        <div><?= htmlspecialchars($skill) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($skills['soft'])): ?>
            <div class="skill-category">
                <div class="skill-category-title">SOFT_SKILLS</div>
                <div class="skill-list">
                    <?php foreach ($skills['soft'] as $skill): ?>
                        <div><?= htmlspecialchars($skill) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($skills['languages']) || !empty($skills['certifications'])): ?>
            <div class="skill-category">
                <div class="skill-category-title">MISC</div>
                <div class="skill-list">
                    <?php foreach (array_merge($skills['languages'], $skills['certifications']) as $item): ?>
                        <div><?= htmlspecialchars($item) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Work Experience -->
    <?php if (!empty($data['experience'])): ?>
    <div class="section">
        <h2 class="section-title">WORK EXPERIENCE</h2>
        <?php foreach ($data['experience'] as $index => $exp): ?>
            <?php if (empty($exp['title']) || empty($exp['company'])) continue; ?>
            <div class="code-block">
                <div><span class="code-comment">// Position <?= $index + 1 ?></span></div>
                <div>
                    <span class="code-key">role</span>: <span class="code-string">"<?= htmlspecialchars($exp['title']) ?>"</span>,
                </div>
                <div>
                    <span class="code-key">company</span>: <span class="code-string">"<?= htmlspecialchars($exp['company']) ?>"</span>,
                </div>
                <div>
                    <span class="code-key">location</span>: <span class="code-string">"<?= htmlspecialchars($exp['location']) ?>"</span>,
                </div>
                <div>
                    <span class="code-key">period</span>: <span class="code-string">"<?php
                    $start = !empty($exp['start_date']) ? date('M Y', strtotime($exp['start_date'] . '-01')) : '';
                    $end = !empty($exp['end_date']) ? date('M Y', strtotime($exp['end_date'] . '-01')) : 'Present';
                    echo $start . ' - ' . $end;
                    ?>"</span>
                </div>
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
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Education -->
    <?php if (!empty($data['education'])): ?>
    <div class="section">
        <h2 class="section-title">EDUCATION</h2>
        <?php foreach ($data['education'] as $edu): ?>
            <?php if (empty($edu['degree']) || empty($edu['institution'])) continue; ?>
            <div class="education-item">
                <div class="degree"><?= htmlspecialchars($edu['degree']) ?></div>
                <div class="company"><?= htmlspecialchars($edu['institution']) ?></div>
                <div class="meta-info">
                    <?= htmlspecialchars($edu['location']) ?> | 
                    <?= htmlspecialchars($edu['start_year']) ?> - <?= !empty($edu['end_year']) ? htmlspecialchars($edu['end_year']) : 'Present' ?>
                    <?php if (!empty($edu['gpa'])): ?>
                        | GPA: <?= htmlspecialchars($edu['gpa']) ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($edu['description'])): ?>
                    <div class="description" style="margin-top: 8px;"><?= nl2br(htmlspecialchars($edu['description'])) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 8pt;">
        <div>// Generated with AI-Powered CV Generator</div>
        <div>// <?= htmlspecialchars($p['email']) ?> | <?= htmlspecialchars($p['phone']) ?></div>
    </div>
</body>
</html>
