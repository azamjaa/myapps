/**
 * PWA Installation Script
 * Handles service worker registration and install prompt
 */

let deferredPrompt;
let isInstalled = false;

// Check if app is already installed
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    isInstalled = true;
    console.log('[PWA] App is running in standalone mode');
}

// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/myapps/service-worker.js')
            .then((registration) => {
                console.log('[PWA] Service Worker registered:', registration.scope);
                
                // Check for updates every hour
                setInterval(() => {
                    registration.update();
                }, 60 * 60 * 1000);
            })
            .catch((error) => {
                console.error('[PWA] Service Worker registration failed:', error);
            });
    });
}

// Listen for beforeinstallprompt event
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('[PWA] beforeinstallprompt event fired');
    
    // Prevent the mini-infobar from appearing
    e.preventDefault();
    
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    
    // Show install button
    showInstallButton();
});

// Listen for app installed event
window.addEventListener('appinstalled', () => {
    console.log('[PWA] App was installed');
    isInstalled = true;
    hideInstallButton();
    
    // Show success message
    showToast('MyApps KEDA berjaya dipasang! 🎉', 'success');
});

// Show install button
function showInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn && !isInstalled) {
        installBtn.classList.remove('hidden');
    }
}

// Hide install button
function hideInstallButton() {
    const installBtn = document.getElementById('pwa-install-btn');
    if (installBtn) {
        installBtn.classList.add('hidden');
    }
}

// Install PWA function
async function installPWA() {
    if (!deferredPrompt) {
        console.log('[PWA] Install prompt not available');
        return;
    }
    
    // Show the install prompt
    deferredPrompt.prompt();
    
    // Wait for the user to respond
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`[PWA] User response: ${outcome}`);
    
    if (outcome === 'accepted') {
        console.log('[PWA] User accepted the install prompt');
    } else {
        console.log('[PWA] User dismissed the install prompt');
    }
    
    // Clear the deferredPrompt
    deferredPrompt = null;
    hideInstallButton();
}

// Show toast notification
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-full ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Check for updates
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.ready.then((registration) => {
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    // New service worker available
                    showUpdateNotification();
                }
            });
        });
    });
}

// Show update notification
function showUpdateNotification() {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-white rounded-lg shadow-2xl p-4 z-50 max-w-md';
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-sync-alt text-blue-600 text-2xl mr-3"></i>
                <div>
                    <h4 class="font-semibold text-gray-800">Kemas Kini Tersedia</h4>
                    <p class="text-sm text-gray-600">Versi baru MyApps KEDA tersedia</p>
                </div>
            </div>
            <button onclick="window.location.reload()" 
                    class="ml-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Muat Semula
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
}

