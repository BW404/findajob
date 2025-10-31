<?php
// Executive Template - uses modern template as base with executive styling
$p = $data['personal'];
$summary = $data['summary'];
$skills = $data['skills'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 20mm; }
        body {
            font-family: "Georgia", "Times New Roman", serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.7;
        }
        .header {
            text-align: center;
            border-bottom: 4px double #d4af37;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .name {
            font-size: 32pt;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 8px 0;
            letter-spacing: 2px;
        }
        .title {
            font-size: 15pt;
            color: #d4af37;
            font-weight: 600;
            margin: 0 0 15px 0;
            font-style: italic;
        }
        .contact {
            font-size: 10pt;
            color: #555;
        }
        .contact a { color: #d4af37; text-decoration: none; }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 14pt;
            font-weight: 700;
            color: #2c3e50;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .summary-text {
            text-align: justify;
            font-style: italic;
            line-height: 1.8;
            color: #333;
        }
        .experience-item, .education-item {
            margin-bottom: 25px;
        }
        .job-title, .degree {
            font-size: 13pt;
            font-weight: 700;
            color: #2c3e50;
        }
        .company, .institution {
            color: #d4af37;
            font-weight: 600;
            font-size: 11pt;
        }
        .meta {
            color: #666;
            font-size: 10pt;
            font-style: italic;
            margin-top: 3px;
        }
        .description ul {
            line-height: 1.8;
        }
        .description li::marker {
            color: #d4af37;
        }
        .skill-tag {
            background: #f5f5f5;
            color: #2c3e50;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 10pt;
            border: 1px solid #d4af37;
            display: inline-block;
            margin: 4px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/modern.php'; ?>
</body>
</html>
