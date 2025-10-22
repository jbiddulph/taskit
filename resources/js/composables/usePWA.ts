import { ref, computed, onMounted } from 'vue';

export interface PWAInstallPrompt {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

export function usePWA() {
  const isInstalled = ref(false);
  const isInstallable = ref(false);
  const isOnline = ref(navigator.onLine);
  const installPrompt = ref<PWAInstallPrompt | null>(null);
  const isStandalone = ref(false);
  const isSupported = ref(false);

  const canInstall = computed(() => isInstallable.value && !isInstalled.value);
  const isOffline = computed(() => !isOnline.value);

  // Check if PWA is supported
  const checkSupport = () => {
    isSupported.value = 'serviceWorker' in navigator && 'PushManager' in window;
  };

  // Check if app is running in standalone mode
  const checkStandalone = () => {
    isStandalone.value = window.matchMedia('(display-mode: standalone)').matches ||
                        (window.navigator as any).standalone === true;
  };

  // Check if app is already installed
  const checkInstalled = () => {
    isInstalled.value = isStandalone.value || 
                       localStorage.getItem('pwa-installed') === 'true';
  };

  // Register service worker
  const registerServiceWorker = async () => {
    if (!isSupported.value) {
      console.log('PWA not supported');
      return;
    }

    try {
      const registration = await navigator.serviceWorker.register('/sw.js');
      console.log('Service Worker registered:', registration);
      
      // Check for updates
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        if (newWorker) {
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              // New content is available, show update notification
              showUpdateNotification();
            }
          });
        }
      });
      
    } catch (error) {
      console.error('Service Worker registration failed:', error);
    }
  };

  // Handle install prompt
  const handleInstallPrompt = (event: Event) => {
    event.preventDefault();
    installPrompt.value = event as any;
    isInstallable.value = true;
  };

  // Install PWA
  const installPWA = async () => {
    if (!installPrompt.value) return;

    try {
      await installPrompt.value.prompt();
      const choiceResult = await installPrompt.value.userChoice;
      
      if (choiceResult.outcome === 'accepted') {
        console.log('PWA installation accepted');
        isInstalled.value = true;
        localStorage.setItem('pwa-installed', 'true');
      } else {
        console.log('PWA installation dismissed');
      }
      
      installPrompt.value = null;
      isInstallable.value = false;
    } catch (error) {
      console.error('PWA installation failed:', error);
    }
  };

  // Handle online/offline status
  const handleOnlineStatus = () => {
    isOnline.value = navigator.onLine;
  };

  // Request notification permission
  const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
      console.log('Notifications not supported');
      return false;
    }

    if (Notification.permission === 'granted') {
      return true;
    }

    if (Notification.permission === 'denied') {
      console.log('Notification permission denied');
      return false;
    }

    const permission = await Notification.requestPermission();
    return permission === 'granted';
  };

  // Show notification
  const showNotification = (title: string, options?: NotificationOptions) => {
    if (Notification.permission === 'granted') {
      new Notification(title, {
        icon: '/zap_icon.png',
        badge: '/zap_icon.png',
        ...options
      });
    }
  };

  // Show update notification
  const showUpdateNotification = () => {
    if (confirm('A new version of ZapTask is available. Would you like to update?')) {
      window.location.reload();
    }
  };

  // Add to home screen (iOS)
  const addToHomeScreen = () => {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isInStandaloneMode = (window.navigator as any).standalone === true;
    
    if (isIOS && !isInStandaloneMode) {
      // Show iOS install instructions
      showIOSInstallInstructions();
    }
  };

  // Show iOS install instructions
  const showIOSInstallInstructions = () => {
    const instructions = `
      To install ZapTask on your iOS device:
      1. Tap the Share button in Safari
      2. Scroll down and tap "Add to Home Screen"
      3. Tap "Add" to confirm
    `;
    
    alert(instructions);
  };

  // Check for updates
  const checkForUpdates = async () => {
    if ('serviceWorker' in navigator) {
      const registration = await navigator.serviceWorker.getRegistration();
      if (registration) {
        await registration.update();
      }
    }
  };

  // Clear cache
  const clearCache = async () => {
    if ('caches' in window) {
      const cacheNames = await caches.keys();
      await Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
      );
      console.log('Cache cleared');
    }
  };

  // Get app info
  const getAppInfo = () => {
    return {
      isInstalled: isInstalled.value,
      isInstallable: isInstallable.value,
      isOnline: isOnline.value,
      isStandalone: isStandalone.value,
      isSupported: isSupported.value,
      userAgent: navigator.userAgent,
      platform: navigator.platform
    };
  };

  // Initialize PWA
  const initializePWA = async () => {
    checkSupport();
    checkStandalone();
    checkInstalled();
    
    if (isSupported.value) {
      await registerServiceWorker();
      await requestNotificationPermission();
    }
  };

  onMounted(() => {
    // Listen for install prompt
    window.addEventListener('beforeinstallprompt', handleInstallPrompt);
    
    // Listen for online/offline status
    window.addEventListener('online', handleOnlineStatus);
    window.addEventListener('offline', handleOnlineStatus);
    
    // Listen for app installed
    window.addEventListener('appinstalled', () => {
      console.log('PWA was installed');
      isInstalled.value = true;
      localStorage.setItem('pwa-installed', 'true');
    });
    
    // Initialize PWA
    initializePWA();
  });

  return {
    // State
    isInstalled,
    isInstallable,
    isOnline,
    isOffline,
    isStandalone,
    isSupported,
    canInstall,
    
    // Actions
    installPWA,
    addToHomeScreen,
    requestNotificationPermission,
    showNotification,
    checkForUpdates,
    clearCache,
    getAppInfo
  };
}
