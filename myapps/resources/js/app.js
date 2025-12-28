import './bootstrap';
import 'alpinejs';

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then(registration => {
            console.log('SW registered: ', registration);
        }).catch(registrationError => {
            console.log('SW registration failed: ', registrationError);
        });
    });
}

// PWA Install Prompt
let deferredPrompt;
const installButton = document.getElementById('pwa-install-btn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    if (installButton) {
        installButton.style.display = 'block';
        
        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
                installButton.style.display = 'none';
            }
        });
    }
});

// Check if PWA is already installed
window.addEventListener('appinstalled', () => {
    console.log('MyApps KEDA PWA was installed');
    deferredPrompt = null;
    if (installButton) {
        installButton.style.display = 'none';
    }
});

// Offline/Online Status
window.addEventListener('online', () => {
    console.log('Online');
    // You can show a notification here
});

window.addEventListener('offline', () => {
    console.log('Offline');
    // You can show a notification here
});
