<?php
/**
 * FindAJob Nigeria - Common Footer
 * Shared footer component for all pages
 */

// Check if we're on an auth page
$is_auth_page = strpos($_SERVER['REQUEST_URI'], '/auth/') !== false;
$base_path = $is_auth_page ? '../../' : '/findajob/';
?>
<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-brand">
                    <img src="<?php echo $base_path; ?>assets/images/icons/icon-192x192.svg" alt="FindAJob Nigeria" class="footer-logo">
                    <h3>FindAJob Nigeria</h3>
                    <p>Connecting talent with opportunity across Nigeria. Find your dream job or hire the best talent.</p>
                </div>
                
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Facebook">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Twitter">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="LinkedIn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>For Job Seekers</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $base_path; ?>pages/jobs/browse.php">Browse Jobs</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/services/cv-creator.php">CV Builder</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/services/training.php">Career Training</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/user/subscription.php">Upgrade to Pro</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/auth/register-jobseeker.php">Create Account</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>For Employers</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo $base_path; ?>pages/company/post-job.php">Post a Job</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/company/resume-search.php">Search CVs</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/company/mini-site.php">Company Page</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/company/subscription.php">Employer Plans</a></li>
                    <li><a href="<?php echo $base_path; ?>pages/auth/register-employer.php">Create Account</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Support</h4>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">About Us</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> FindAJob Nigeria. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Security</a>
                    <?php if (isDevelopmentMode()): ?>
                        <a href="<?php echo $base_path; ?>temp_mail.php" style="color: #f59e0b; font-weight: bold;" title="Development Email Inbox">📧 Dev Emails</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PWA Install Prompt -->
    <div id="pwaInstallPrompt" class="pwa-prompt" style="display: none;">
        <div class="pwa-prompt-content">
            <div class="pwa-prompt-icon">
                <img src="<?php echo $base_path; ?>assets/images/icons/icon-72x72.png" alt="FindAJob">
            </div>
            <div class="pwa-prompt-text">
                <h4>Install FindAJob App</h4>
                <p>Get quick access to jobs on your phone</p>
            </div>
            <div class="pwa-prompt-actions">
                <button id="pwaInstallBtn" class="btn btn-primary btn-sm">Install</button>
                <button id="pwaDismissBtn" class="btn btn-secondary btn-sm">Not Now</button>
            </div>
        </div>
    </div>
</footer>

<style>
.main-footer {
    background: var(--text-primary);
    color: white;
    margin-top: auto;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 2rem;
    padding: 3rem 0 2rem;
}

.footer-section h3,
.footer-section h4 {
    margin-bottom: 1rem;
    color: white;
}

.footer-brand p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.footer-logo {
    width: 40px;
    height: 40px;
    margin-bottom: 0.5rem;
}

.footer-social {
    display: flex;
    gap: 1rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: all 0.3s;
}

.social-link:hover {
    background: var(--primary);
    transform: translateY(-2px);
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s;
}

.footer-links a:hover {
    color: white;
}

.footer-contact {
    margin-bottom: 1.5rem;
}

.contact-item {
    margin-bottom: 1rem;
}

.contact-item strong {
    display: block;
    color: white;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.contact-item p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.4;
    margin: 0;
    font-size: 0.9rem;
}

.contact-item a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.3s;
}

.contact-item a:hover {
    color: white;
}

.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem 0;
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-bottom-content p {
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
}

.footer-bottom-links {
    display: flex;
    gap: 1.5rem;
}

.footer-bottom-links a {
    color: rgba(255, 255, 255, 0.6);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s;
}

.footer-bottom-links a:hover {
    color: white;
}

.pwa-prompt {
    position: fixed;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    animation: slideUp 0.3s ease;
}

.pwa-prompt-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
}

.pwa-prompt-icon img {
    width: 48px;
    height: 48px;
    border-radius: 8px;
}

.pwa-prompt-text {
    flex: 1;
}

.pwa-prompt-text h4 {
    margin: 0 0 0.25rem;
    color: var(--text-primary);
    font-size: 1rem;
}

.pwa-prompt-text p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.pwa-prompt-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 2rem;
    }
    
    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
    }
    
    .pwa-prompt {
        left: 0.5rem;
        right: 0.5rem;
    }
    
    .pwa-prompt-content {
        flex-direction: column;
        text-align: center;
    }
    
    .pwa-prompt-actions {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// PWA Install Prompt
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show custom install prompt
    const installPrompt = document.getElementById('pwaInstallPrompt');
    if (installPrompt) {
        installPrompt.style.display = 'block';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const installBtn = document.getElementById('pwaInstallBtn');
    const dismissBtn = document.getElementById('pwaDismissBtn');
    const installPrompt = document.getElementById('pwaInstallPrompt');
    
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
            }
            installPrompt.style.display = 'none';
        });
    }
    
    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            installPrompt.style.display = 'none';
            // Set a flag to not show again for 7 days
            localStorage.setItem('pwaPromptDismissed', Date.now() + (7 * 24 * 60 * 60 * 1000));
        });
    }
    
    // Check if prompt was recently dismissed
    const dismissedUntil = localStorage.getItem('pwaPromptDismissed');
    if (dismissedUntil && Date.now() < parseInt(dismissedUntil)) {
        if (installPrompt) {
            installPrompt.style.display = 'none';
        }
    }
});
</script>