-- Sample job data for FindAJob Nigeria
-- Insert sample job categories first
INSERT INTO job_categories (name, slug, description, icon, is_active) VALUES 
('Technology', 'technology', 'Software development, IT, cybersecurity, and tech roles', '💻', TRUE),
('Banking & Finance', 'banking-finance', 'Banking, accounting, financial services, and investment', '🏦', TRUE),
('Oil & Gas', 'oil-gas', 'Petroleum, energy, and oil industry positions', '⛽', TRUE),
('Healthcare', 'healthcare', 'Medical, nursing, pharmaceutical, and health services', '🏥', TRUE),
('Education', 'education', 'Teaching, training, academic, and educational roles', '🎓', TRUE),
('Engineering', 'engineering', 'Civil, mechanical, electrical, and engineering disciplines', '⚙️', TRUE),
('Sales & Marketing', 'sales-marketing', 'Sales, marketing, advertising, and business development', '📈', TRUE),
('Government', 'government', 'Public sector, civil service, and government positions', '🏛️', TRUE),
('Manufacturing', 'manufacturing', 'Production, quality control, and manufacturing roles', '🏭', TRUE),
('Agriculture', 'agriculture', 'Farming, agribusiness, and agricultural development', '🌾', TRUE)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Create sample employer users
INSERT IGNORE INTO users (id, user_type, email, password_hash, first_name, last_name, phone, email_verified, is_active) VALUES 
(100, 'employer', 'hr@techcorp.ng', '$2y$10$example_hash', 'Tech', 'Corp', '+234-800-TECH', TRUE, TRUE),
(101, 'employer', 'jobs@bankplus.ng', '$2y$10$example_hash', 'Bank', 'Plus', '+234-800-BANK', TRUE, TRUE),
(102, 'employer', 'careers@oilfield.ng', '$2y$10$example_hash', 'Oil', 'Field', '+234-800-OIL', TRUE, TRUE),
(103, 'employer', 'hr@healthcorp.ng', '$2y$10$example_hash', 'Health', 'Corp', '+234-800-HEAL', TRUE, TRUE),
(104, 'employer', 'talent@startup.ng', '$2y$10$example_hash', 'Start', 'Up', '+234-800-START', TRUE, TRUE);

-- Create sample employer profiles
INSERT IGNORE INTO employer_profiles (id, user_id, company_name, industry, company_size, website, description, address, state, city, is_verified, verification_status, subscription_type) VALUES 
(100, 100, 'TechCorp Nigeria', 'Technology', '201-500', 'https://techcorp.ng', 'Leading software development company in Nigeria specializing in fintech and e-commerce solutions.', 'Plot 15, Admiralty Way, Lekki Phase 1', 'Lagos', 'Lagos', TRUE, 'verified', 'pro'),
(101, 101, 'BankPlus Limited', 'Banking & Finance', '500+', 'https://bankplus.ng', 'Premier commercial bank offering comprehensive financial services across Nigeria.', '23 Marina Street, Lagos Island', 'Lagos', 'Lagos', TRUE, 'verified', 'pro'),
(102, 102, 'OilField Services Ltd', 'Oil & Gas', '201-500', 'https://oilfield.ng', 'Providing drilling and petroleum engineering services to major oil companies in Nigeria.', 'Port Harcourt Industrial Layout', 'Rivers', 'Port Harcourt', TRUE, 'verified', 'free'),
(103, 103, 'HealthCorp Medical', 'Healthcare', '51-200', 'https://healthcorp.ng', 'Modern healthcare facility providing quality medical services and equipment.', 'Wuse 2, Central Business District', 'Abuja', 'Abuja', TRUE, 'verified', 'pro'),
(104, 104, 'StartUp Innovations', 'Technology', '11-50', 'https://startup.ng', 'Fast-growing startup focused on mobile app development and digital marketing.', '45 Allen Avenue, Ikeja', 'Lagos', 'Ikeja', FALSE, 'pending', 'free');

-- Insert sample jobs
INSERT INTO jobs (
    employer_id, title, slug, category_id, job_type, employment_type,
    description, requirements, responsibilities, benefits,
    salary_min, salary_max, salary_currency, salary_period,
    location_type, state, city, address,
    experience_level, education_level, application_deadline,
    application_email, company_name,
    is_featured, is_urgent, is_remote_friendly, status, created_at
) VALUES 

-- Technology Jobs
(100, 'Senior Full Stack Developer', 'senior-full-stack-developer-1697132800', 1, 'permanent', 'full_time',
'We are seeking an experienced Full Stack Developer to join our growing team. You will work on cutting-edge fintech applications using modern technologies like React, Node.js, and cloud platforms.',
'• 5+ years experience in full stack development
• Proficiency in React, Node.js, TypeScript
• Experience with AWS or Azure cloud platforms
• Knowledge of database design (PostgreSQL, MongoDB)
• Experience with CI/CD pipelines
• Bachelor''s degree in Computer Science or related field',
'• Design and develop scalable web applications
• Collaborate with cross-functional teams
• Write clean, maintainable code
• Participate in code reviews and mentoring
• Troubleshoot and debug applications
• Stay updated with latest technology trends',
'• Competitive salary with performance bonuses
• Health insurance coverage
• Remote work flexibility
• Professional development opportunities
• Modern office environment
• Annual leave and sick days',
800000, 1200000, 'NGN', 'monthly',
'hybrid', 'Lagos', 'Lagos', 'Plot 15, Admiralty Way, Lekki Phase 1',
'senior', 'bsc', '2024-12-31',
'careers@techcorp.ng', 'TechCorp Nigeria',
TRUE, FALSE, TRUE, 'active', '2024-10-01 10:30:00'),

(100, 'Mobile App Developer (React Native)', 'mobile-app-developer-react-native-1697132900', 1, 'permanent', 'full_time',
'Join our mobile development team to build innovative financial applications that serve millions of Nigerian users. You''ll work with React Native and modern mobile development practices.',
'• 3+ years React Native development experience
• Strong JavaScript/TypeScript skills
• Experience with mobile app deployment (App Store, Play Store)
• Knowledge of mobile UI/UX best practices
• Experience with payment integrations
• Understanding of mobile security practices',
'• Develop and maintain mobile applications
• Collaborate with designers and backend developers
• Optimize app performance and user experience
• Implement security best practices
• Write unit and integration tests
• Participate in agile development processes',
'• Competitive salary package
• Health and dental insurance
• Flexible working hours
• Learning and development budget
• Team building activities
• Performance bonuses',
600000, 900000, 'NGN', 'monthly',
'onsite', 'Lagos', 'Lagos', 'Plot 15, Admiralty Way, Lekki Phase 1',
'mid', 'bsc', '2024-11-30',
'careers@techcorp.ng', 'TechCorp Nigeria',
FALSE, TRUE, FALSE, 'active', '2024-10-05 14:20:00'),

(104, 'Junior Frontend Developer', 'junior-frontend-developer-1697133000', 1, 'permanent', 'full_time',
'Perfect opportunity for a recent graduate or junior developer to grow their career in a dynamic startup environment. You''ll work on exciting projects and learn from experienced developers.',
'• 1-2 years experience with HTML, CSS, JavaScript
• Basic knowledge of React or Vue.js
• Understanding of responsive design principles
• Git version control experience
• Passion for learning new technologies
• Portfolio of personal or academic projects',
'• Build user interfaces for web applications
• Collaborate with senior developers
• Participate in daily standups and sprint planning
• Learn and implement new frontend technologies
• Write clean, semantic HTML and CSS
• Test applications across different browsers',
'• Competitive entry-level salary
• Mentorship from senior developers
• Growth opportunities
• Flexible work environment
• Training and certification support
• Health insurance',
300000, 450000, 'NGN', 'monthly',
'hybrid', 'Lagos', 'Ikeja', '45 Allen Avenue, Ikeja',
'entry', 'bsc', '2024-12-15',
'talent@startup.ng', 'StartUp Innovations',
FALSE, FALSE, TRUE, 'active', '2024-10-08 09:15:00'),

-- Banking & Finance Jobs
(101, 'Senior Business Analyst', 'senior-business-analyst-1697133100', 2, 'permanent', 'full_time',
'We are looking for an experienced Business Analyst to drive digital transformation initiatives and improve our banking processes through data-driven insights and strategic recommendations.',
'• 5+ years business analysis experience in banking
• Strong analytical and problem-solving skills
• Experience with process improvement methodologies
• Proficiency in SQL and data analysis tools
• Knowledge of banking regulations and compliance
• Excellent communication and presentation skills',
'• Analyze business processes and identify improvement opportunities
• Gather and document business requirements
• Work with stakeholders to define project scope
• Create detailed process maps and workflows
• Develop business cases and ROI analysis
• Coordinate with IT teams for system implementations',
'• Attractive salary package
• Performance-based bonuses
• Comprehensive health insurance
• Pension contribution
• Professional development opportunities
• Banking industry benefits',
750000, 1100000, 'NGN', 'monthly',
'onsite', 'Lagos', 'Lagos', '23 Marina Street, Lagos Island',
'senior', 'bsc', '2024-11-20',
'jobs@bankplus.ng', 'BankPlus Limited',
TRUE, FALSE, FALSE, 'active', '2024-10-03 11:45:00'),

(101, 'Credit Risk Officer', 'credit-risk-officer-1697133200', 2, 'permanent', 'full_time',
'Join our risk management team to assess and monitor credit risk across our loan portfolio. This role is critical to maintaining our bank''s financial health and regulatory compliance.',
'• Bachelor''s degree in Finance, Economics, or related field
• 3+ years experience in credit risk assessment
• Strong understanding of financial statements
• Knowledge of Basel III requirements
• Proficiency in risk management software
• Professional certification (FRM, CFA) preferred',
'• Assess credit applications and loan proposals
• Monitor existing loan portfolio performance
• Develop and maintain risk assessment models
• Prepare risk reports for management
• Ensure compliance with regulatory requirements
• Collaborate with business units on risk matters',
'• Competitive salary with annual reviews
• Health insurance for employee and family
• Pension scheme contribution
• Professional certification support
• Career advancement opportunities
• Performance incentives',
500000, 750000, 'NGN', 'monthly',
'onsite', 'Lagos', 'Lagos', '23 Marina Street, Lagos Island',
'mid', 'bsc', '2024-12-10',
'jobs@bankplus.ng', 'BankPlus Limited',
FALSE, FALSE, FALSE, 'active', '2024-10-07 16:30:00'),

-- Oil & Gas Jobs
(102, 'Drilling Engineer', 'drilling-engineer-1697133300', 3, 'contract', 'full_time',
'Experienced Drilling Engineer needed for offshore drilling operations. This is a 2-year contract position with rotation schedule and competitive compensation package.',
'• Bachelor''s degree in Petroleum Engineering
• 5+ years offshore drilling experience
• Knowledge of drilling software (Wellplan, DrillWorks)
• Understanding of HSE regulations
• Ability to work rotation schedules
• Valid offshore survival certification',
'• Plan and supervise drilling operations
• Optimize drilling parameters for efficiency
• Ensure compliance with safety regulations
• Coordinate with offshore drilling teams
• Prepare daily drilling reports
• Troubleshoot drilling problems',
'• Competitive day rate
• Rotation schedule (28 days on/28 days off)
• Comprehensive insurance coverage
• Transportation and accommodation provided
• Performance bonuses
• Professional development opportunities',
1200000, 1800000, 'NGN', 'monthly',
'onsite', 'Rivers', 'Port Harcourt', 'Port Harcourt Industrial Layout',
'senior', 'bsc', '2024-11-15',
'careers@oilfield.ng', 'OilField Services Ltd',
FALSE, TRUE, FALSE, 'active', '2024-10-04 13:20:00'),

-- Healthcare Jobs
(103, 'Registered Nurse - ICU', 'registered-nurse-icu-1697133400', 4, 'permanent', 'full_time',
'We are seeking a dedicated ICU Nurse to join our critical care team. You will provide specialized nursing care to critically ill patients in a state-of-the-art facility.',
'• Bachelor''s degree in Nursing (BSN)
• Current RN license in Nigeria
• 2+ years ICU experience
• BLS and ACLS certification
• Experience with ventilators and monitoring equipment
• Strong critical thinking skills',
'• Provide direct patient care in ICU setting
• Monitor patient vital signs and conditions
• Administer medications and treatments
• Collaborate with physicians and healthcare team
• Document patient care activities
• Support patient families during critical times',
'• Competitive nursing salary
• Health insurance and life insurance
• Continuing education support
• Shift differentials for night/weekend work
• Annual leave and sick days
• Professional growth opportunities',
400000, 600000, 'NGN', 'monthly',
'onsite', 'Abuja', 'Abuja', 'Wuse 2, Central Business District',
'mid', 'bsc', '2024-12-05',
'hr@healthcorp.ng', 'HealthCorp Medical',
FALSE, FALSE, FALSE, 'active', '2024-10-06 08:45:00'),

-- Entry Level / Graduate Jobs
(100, 'Graduate Software Developer', 'graduate-software-developer-1697133500', 1, 'permanent', 'full_time',
'Excellent opportunity for recent Computer Science graduates to start their career with a leading tech company. Comprehensive training program and mentorship included.',
'• Bachelor''s degree in Computer Science or related field
• Strong programming fundamentals (Python, Java, or JavaScript)
• Understanding of software development lifecycle
• Basic knowledge of databases and web technologies
• Good problem-solving and analytical skills
• Passion for technology and continuous learning',
'• Participate in graduate training program
• Work on real projects under senior developer guidance
• Learn company technologies and best practices
• Contribute to team projects and code reviews
• Attend workshops and technical training sessions
• Develop both technical and soft skills',
'• Competitive graduate salary
• Comprehensive training program
• Mentorship from senior developers
• Health insurance
• Career development pathway
• Modern work environment',
350000, 500000, 'NGN', 'monthly',
'hybrid', 'Lagos', 'Lagos', 'Plot 15, Admiralty Way, Lekki Phase 1',
'entry', 'bsc', '2024-12-20',
'careers@techcorp.ng', 'TechCorp Nigeria',
FALSE, FALSE, TRUE, 'active', '2024-10-09 12:10:00'),

-- NYSC/Internship Opportunities
(101, 'NYSC Banking Trainee', 'nysc-banking-trainee-1697133600', 2, 'nysc', 'full_time',
'Structured NYSC program for fresh graduates interested in banking career. Comprehensive training across all banking operations with potential for permanent employment.',
'• NYSC certificate or in progress
• Bachelor''s degree in any discipline
• Strong analytical and numerical skills
• Excellent communication skills
• Interest in banking and financial services
• Professional attitude and appearance',
'• Rotate through different banking departments
• Learn banking operations and procedures
• Assist with customer service activities
• Participate in training programs
• Work on assigned projects
• Prepare reports and presentations',
'• NYSC allowance plus additional stipend
• Comprehensive banking training
• Potential for permanent employment
• Professional development
• Networking opportunities
• Certificate upon completion',
50000, 80000, 'NGN', 'monthly',
'onsite', 'Lagos', 'Lagos', '23 Marina Street, Lagos Island',
'entry', 'bsc', '2024-11-25',
'jobs@bankplus.ng', 'BankPlus Limited',
FALSE, FALSE, FALSE, 'active', '2024-10-10 10:25:00'),

(104, 'Digital Marketing Intern', 'digital-marketing-intern-1697133700', 7, 'internship', 'full_time',
'3-month internship program for students or recent graduates interested in digital marketing. Hands-on experience with real client projects and comprehensive mentorship.',
'• Currently pursuing or completed degree in Marketing, Communications, or related field
• Basic understanding of social media platforms
• Interest in digital marketing and content creation
• Good writing and communication skills
• Creativity and attention to detail
• Basic knowledge of design tools (Canva, Photoshop) preferred',
'• Assist with social media content creation
• Help with email marketing campaigns
• Support SEO and content marketing efforts
• Analyze digital marketing metrics
• Participate in client meetings and presentations
• Learn industry best practices and tools',
'• Internship allowance
• Hands-on digital marketing experience
• Mentorship from experienced marketers
• Certificate of completion
• Networking opportunities
• Potential for full-time offer',
40000, 60000, 'NGN', 'monthly',
'hybrid', 'Lagos', 'Ikeja', '45 Allen Avenue, Ikeja',
'entry', 'bsc', '2024-11-30',
'talent@startup.ng', 'StartUp Innovations',
FALSE, FALSE, TRUE, 'active', '2024-10-11 15:40:00'),

-- Remote Jobs
(100, 'Remote Frontend Developer', 'remote-frontend-developer-1697133800', 1, 'contract', 'full_time',
'Fully remote contract position for an experienced Frontend Developer. Work with international clients while living anywhere in Nigeria. 6-month contract with extension possibilities.',
'• 4+ years frontend development experience
• Expert level React.js and TypeScript
• Experience with modern CSS frameworks (Tailwind, Styled Components)
• Strong portfolio of web applications
• Excellent English communication skills
• Self-motivated and able to work independently',
'• Develop responsive web applications
• Collaborate with international team members
• Participate in daily video standups
• Deliver high-quality code on schedule
• Communicate progress and challenges effectively
• Stay updated with latest frontend technologies',
'• Competitive USD-based compensation
• Fully remote work
• Flexible working hours
• International project exposure
• Professional development opportunities
• Performance bonuses',
700000, 1000000, 'NGN', 'monthly',
'remote', 'Lagos', 'Remote', 'Work from anywhere in Nigeria',
'mid', 'bsc', '2024-12-31',
'careers@techcorp.ng', 'TechCorp Nigeria',
TRUE, FALSE, TRUE, 'active', '2024-10-12 09:30:00');

-- Update job counts
UPDATE jobs SET views_count = FLOOR(RAND() * 500) + 50, applications_count = FLOOR(RAND() * 25) + 1 WHERE id > 0;

-- Set some jobs as featured
UPDATE jobs SET is_featured = TRUE WHERE id IN (
    SELECT id FROM (
        SELECT id FROM jobs ORDER BY RAND() LIMIT 3
    ) AS temp
);