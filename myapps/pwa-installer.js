// PWA Installer Script
let deferredPrompt;
const installBtn = document.getElementById('pwa-install-hint');

// Untuk animasi butang install (pulse effect)
if (installBtn) {
  installBtn.classList.add('animate-pulse');
}

console.log('[PWA] pwa-installer.js loaded');

window.addEventListener('beforeinstallprompt', (e) => {
  console.log('[PWA] beforeinstallprompt event fired');
  e.preventDefault();
  deferredPrompt = e;
  if (installBtn) {
    installBtn.classList.remove('hidden');
    console.log('[PWA] Install button shown');
  }
});

if (installBtn) {
  installBtn.addEventListener('click', async () => {
    if (!deferredPrompt) {
      console.log('[PWA] No deferredPrompt');
      return;
    }
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('[PWA] User choice:', outcome);
    installBtn.classList.add('hidden');
    deferredPrompt = null;
  });
}

window.addEventListener('appinstalled', () => {
  if (installBtn) installBtn.classList.add('hidden');
  console.log('[PWA] App installed');
});

// iOS detection & manual install hint
function isIos() {
  return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}
function isInStandaloneMode() {
  return ('standalone' in window.navigator) && window.navigator.standalone;
}
window.addEventListener('DOMContentLoaded', function() {
  if (isIos() && !isInStandaloneMode()) {
    var iosHint = document.getElementById('ios-install-hint');
    if (iosHint) iosHint.classList.remove('hidden');
    if (installBtn) installBtn.classList.add('hidden');
    console.log('[PWA] iOS detected, show manual install hint');
  }
});
