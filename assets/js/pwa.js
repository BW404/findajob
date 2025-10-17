/**
 * FindAJob Nigeria - PWA Features
 * Progressive Web App functionality
 */

class FindAJobPWA {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.init();
    }

    init() {
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupNotifications();
        this.setupBackgroundSync();
        this.addMobileAppBehaviors();
        this.detectInstallation();
    }

    // Register service worker
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/findajob/sw.js');
                console.log('PWA: Service Worker registered successfully:', registration);
                
                // Update service worker when new version is available
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateAvailable();
                        }
                    });
                });
            } catch (error) {
                console.error('PWA: Service Worker registration failed:', error);
            }
        }
    }

    // Setup install prompt
    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: Install prompt triggered');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallBanner();
        });

        window.addEventListener('appinstalled', () => {
            console.log('PWA: App installed successfully');
            this.isInstalled = true;
            this.hideInstallBanner();
            this.showWelcomeMessage();
        });
    }

    // Show install banner
    showInstallBanner() {
        // Check if banner was recently dismissed
        const dismissed = localStorage.getItem('installBannerDismissed');
        if (dismissed && Date.now() - parseInt(dismissed) < 7 * 24 * 60 * 60 * 1000) {
            return;
        }

        const banner = document.createElement('div');
        banner.id = 'installBanner';
        banner.className = 'install-banner install-banner-top';
        banner.innerHTML = `
            <div class="install-banner-content">
                <div class="install-banner-icon">
                    <img src="/findajob/assets/images/icons/icon-72x72.png" alt="FindAJob">
                </div>
                <div class="install-banner-text">
                    <h4>Install FindAJob App</h4>
                    <p>Get quick access and offline browsing</p>
                </div>
                <div class="install-banner-actions">
                    <button id="installBtn" class="btn btn-primary btn-sm">Install</button>
                    <button id="dismissBtn" class="btn btn-secondary btn-sm">Later</button>
                </div>
                <button class="install-banner-close" id="closeBtn">×</button>
            </div>
        `;

        document.body.appendChild(banner);

        // Add event listeners
        document.getElementById('installBtn').addEventListener('click', () => {
            this.promptInstall();
        });

        document.getElementById('dismissBtn').addEventListener('click', () => {
            this.dismissInstallBanner();
        });

        document.getElementById('closeBtn').addEventListener('click', () => {
            this.dismissInstallBanner();
        });

        // Auto show with animation
        setTimeout(() => banner.classList.add('show'), 100);
    }

    // Prompt install
    async promptInstall() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log(`PWA: Install prompt outcome: ${outcome}`);
            
            if (outcome === 'accepted') {
                this.hideInstallBanner();
            }
            
            this.deferredPrompt = null;
        }
    }

    // Dismiss install banner
    dismissInstallBanner() {
        this.hideInstallBanner();
        localStorage.setItem('installBannerDismissed', Date.now().toString());
    }

    // Hide install banner
    hideInstallBanner() {
        const banner = document.getElementById('installBanner');
        if (banner) {
            banner.classList.add('hiding');
            setTimeout(() => banner.remove(), 300);
        }
    }

    // Setup push notifications
    async setupNotifications() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            // Request permission if not granted
            if (Notification.permission === 'default') {
                setTimeout(() => this.requestNotificationPermission(), 3000);
            }
        }
    }

    // Request notification permission
    async requestNotificationPermission() {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            console.log('PWA: Notification permission granted');
            this.subscribeToNotifications();
        }
    }

    // Subscribe to push notifications
    async subscribeToNotifications() {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array('YOUR_VAPID_PUBLIC_KEY') // Replace with actual VAPID key
            });
            
            console.log('PWA: Push subscription:', subscription);
            // Send subscription to server
            // await this.sendSubscriptionToServer(subscription);
        } catch (error) {
            console.error('PWA: Push subscription failed:', error);
        }
    }

    // Setup background sync
    setupBackgroundSync() {
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            console.log('PWA: Background sync supported');
            
            // Register for background sync when going offline
            window.addEventListener('offline', () => {
                this.registerBackgroundSync();
            });
        }
    }

    // Register background sync
    async registerBackgroundSync() {
        try {
            const registration = await navigator.serviceWorker.ready;
            await registration.sync.register('background-sync');
            console.log('PWA: Background sync registered');
        } catch (error) {
            console.error('PWA: Background sync registration failed:', error);
        }
    }

    // Add mobile app behaviors
    addMobileAppBehaviors() {
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (event) => {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Add pull-to-refresh
        this.setupPullToRefresh();

        // Add swipe gestures
        this.setupSwipeGestures();

        // Add haptic feedback
        this.setupHapticFeedback();

        // Hide address bar on scroll
        let hideTimer;
        window.addEventListener('scroll', () => {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => {
                window.scrollTo(window.scrollX, window.scrollY + 1);
                window.scrollTo(window.scrollX, window.scrollY - 1);
            }, 200);
        });
    }

    // Setup pull to refresh
    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let pulling = false;

        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                pulling = true;
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (pulling) {
                currentY = e.touches[0].clientY;
                const pullDistance = currentY - startY;
                
                if (pullDistance > 100) {
                    this.showPullToRefreshIndicator();
                }
            }
        });

        document.addEventListener('touchend', (e) => {
            if (pulling && currentY - startY > 100) {
                this.refreshPage();
            }
            pulling = false;
            this.hidePullToRefreshIndicator();
        });
    }

    // Setup swipe gestures
    setupSwipeGestures() {
        let startX = 0;
        let startY = 0;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });

        document.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            
            // Swipe left/right for navigation
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 100) {
                if (deltaX > 0) {
                    this.handleSwipeRight();
                } else {
                    this.handleSwipeLeft();
                }
            }
        });
    }

    // Setup haptic feedback
    setupHapticFeedback() {
        if ('vibrate' in navigator) {
            // Add haptic feedback to buttons
            document.addEventListener('click', (e) => {
                if (e.target.matches('button, .btn, a[href]')) {
                    navigator.vibrate(10);
                }
            });
        }
    }

    // Detect if app is installed
    detectInstallation() {
        // Check if running in standalone mode
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
            document.body.classList.add('pwa-installed');
            console.log('PWA: App is running in standalone mode');
        }
    }

    // Show update available notification
    showUpdateAvailable() {
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.innerHTML = `
            <div class="update-content">
                <span>New version available!</span>
                <button id="updateBtn" class="btn btn-sm btn-primary">Update</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        document.getElementById('updateBtn').addEventListener('click', () => {
            window.location.reload();
        });
    }

    // Show welcome message after installation
    showWelcomeMessage() {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Welcome to FindAJob!', {
                body: 'Thanks for installing our app. Find your dream job today!',
                icon: '/findajob/assets/images/icons/icon-192x192.png'
            });
        }
    }

    // Utility functions
    showPullToRefreshIndicator() {
        document.body.classList.add('pull-to-refresh');
    }

    hidePullToRefreshIndicator() {
        document.body.classList.remove('pull-to-refresh');
    }

    refreshPage() {
        window.location.reload();
    }

    handleSwipeLeft() {
        // Handle left swipe (next page, etc.)
        console.log('PWA: Swipe left detected');
    }

    handleSwipeRight() {
        // Handle right swipe (back, etc.)
        console.log('PWA: Swipe right detected');
        if (history.length > 1) {
            history.back();
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
            
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

// Initialize PWA when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.findAJobPWA = new FindAJobPWA();
});