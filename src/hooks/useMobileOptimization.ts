import { useState, useEffect, useCallback } from 'react';

interface DeviceInfo {
  isMobile: boolean;
  isTablet: boolean;
  isDesktop: boolean;
  screenWidth: number;
  screenHeight: number;
  orientation: 'portrait' | 'landscape';
  touchSupport: boolean;
  devicePixelRatio: number;
  platform: string;
  userAgent: string;
}

interface ViewportInfo {
  width: number;
  height: number;
  availableWidth: number;
  availableHeight: number;
  scrollbarWidth: number;
}

const BREAKPOINTS = {
  mobile: 768,
  tablet: 1024,
  desktop: 1280,
  large: 1536
} as const;

export const useMobileOptimization = () => {
  const [deviceInfo, setDeviceInfo] = useState<DeviceInfo>({
    isMobile: false,
    isTablet: false,
    isDesktop: true,
    screenWidth: 0,
    screenHeight: 0,
    orientation: 'landscape',
    touchSupport: false,
    devicePixelRatio: 1,
    platform: 'unknown',
    userAgent: ''
  });

  const [viewportInfo, setViewportInfo] = useState<ViewportInfo>({
    width: 0,
    height: 0,
    availableWidth: 0,
    availableHeight: 0,
    scrollbarWidth: 0
  });

  const [isKeyboardOpen, setIsKeyboardOpen] = useState(false);
  const [safeAreaInsets, setSafeAreaInsets] = useState({
    top: 0,
    right: 0,
    bottom: 0,
    left: 0
  });

  const detectDevice = useCallback(() => {
    const width = window.innerWidth;
    const height = window.innerHeight;
    const screenWidth = window.screen.width;
    const screenHeight = window.screen.height;
    const orientation = width > height ? 'landscape' : 'portrait';
    const touchSupport = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    const devicePixelRatio = window.devicePixelRatio || 1;
    const userAgent = navigator.userAgent;
    
    // Detect platform
    let platform = 'unknown';
    if (/Android/i.test(userAgent)) platform = 'android';
    else if (/iPhone|iPad|iPod/i.test(userAgent)) platform = 'ios';
    else if (/Windows/i.test(userAgent)) platform = 'windows';
    else if (/Mac/i.test(userAgent)) platform = 'mac';
    else if (/Linux/i.test(userAgent)) platform = 'linux';

    const isMobile = width < BREAKPOINTS.mobile;
    const isTablet = width >= BREAKPOINTS.mobile && width < BREAKPOINTS.desktop;
    const isDesktop = width >= BREAKPOINTS.desktop;

    setDeviceInfo({
      isMobile,
      isTablet,
      isDesktop,
      screenWidth,
      screenHeight,
      orientation,
      touchSupport,
      devicePixelRatio,
      platform,
      userAgent
    });

    // Calculate scrollbar width
    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;

    setViewportInfo({
      width,
      height,
      availableWidth: window.screen.availWidth,
      availableHeight: window.screen.availHeight,
      scrollbarWidth
    });
  }, []);

  const detectKeyboard = useCallback(() => {
    if (!deviceInfo.isMobile) return;

    const initialHeight = window.innerHeight;
    const currentHeight = window.innerHeight;
    const heightDifference = initialHeight - currentHeight;
    
    // If height decreased by more than 150px, keyboard is likely open
    const keyboardOpen = heightDifference > 150;
    setIsKeyboardOpen(keyboardOpen);
  }, [deviceInfo.isMobile]);

  const detectSafeArea = useCallback(() => {
    // Get CSS environment variables for safe area insets
    const computedStyle = getComputedStyle(document.documentElement);
    
    const top = parseInt(computedStyle.getPropertyValue('env(safe-area-inset-top)') || '0');
    const right = parseInt(computedStyle.getPropertyValue('env(safe-area-inset-right)') || '0');
    const bottom = parseInt(computedStyle.getPropertyValue('env(safe-area-inset-bottom)') || '0');
    const left = parseInt(computedStyle.getPropertyValue('env(safe-area-inset-left)') || '0');

    setSafeAreaInsets({ top, right, bottom, left });
  }, []);

  const optimizeForTouch = useCallback(() => {
    if (!deviceInfo.touchSupport) return;

    // Add touch-friendly classes to body
    document.body.classList.add('touch-device');
    
    // Prevent zoom on double tap for iOS
    if (deviceInfo.platform === 'ios') {
      document.addEventListener('touchstart', (e) => {
        if (e.touches.length > 1) {
          e.preventDefault();
        }
      }, { passive: false });
    }

    // Prevent pull-to-refresh on mobile
    document.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1 && window.scrollY === 0) {
        e.preventDefault();
      }
    }, { passive: false });

    document.addEventListener('touchmove', (e) => {
      if (e.touches.length === 1 && window.scrollY === 0) {
        e.preventDefault();
      }
    }, { passive: false });
  }, [deviceInfo.touchSupport, deviceInfo.platform]);

  const setViewportMeta = useCallback(() => {
    let viewport = document.querySelector('meta[name="viewport"]');
    
    if (!viewport) {
      viewport = document.createElement('meta');
      viewport.setAttribute('name', 'viewport');
      document.head.appendChild(viewport);
    }

    // Set appropriate viewport for different devices
    if (deviceInfo.isMobile) {
      viewport.setAttribute('content', 
        'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover'
      );
    } else {
      viewport.setAttribute('content', 
        'width=device-width, initial-scale=1.0, viewport-fit=cover'
      );
    }
  }, [deviceInfo.isMobile]);

  const addResponsiveClasses = useCallback(() => {
    const body = document.body;
    
    // Remove existing responsive classes
    body.classList.remove('mobile', 'tablet', 'desktop', 'touch', 'no-touch', 'portrait', 'landscape');
    
    // Add device type classes
    if (deviceInfo.isMobile) body.classList.add('mobile');
    if (deviceInfo.isTablet) body.classList.add('tablet');
    if (deviceInfo.isDesktop) body.classList.add('desktop');
    
    // Add touch support classes
    if (deviceInfo.touchSupport) {
      body.classList.add('touch');
    } else {
      body.classList.add('no-touch');
    }
    
    // Add orientation classes
    body.classList.add(deviceInfo.orientation);
    
    // Add platform classes
    body.classList.add(`platform-${deviceInfo.platform}`);
    
    // Add keyboard state class
    if (isKeyboardOpen) {
      body.classList.add('keyboard-open');
    } else {
      body.classList.remove('keyboard-open');
    }
  }, [deviceInfo, isKeyboardOpen]);

  const optimizeImages = useCallback(() => {
    const images = document.querySelectorAll('img[data-responsive]');
    
    images.forEach((img) => {
      const element = img as HTMLImageElement;
      const baseSrc = element.dataset.src || element.src;
      
      if (!baseSrc) return;
      
      // Choose appropriate image size based on device
      let sizeSuffix = '';
      if (deviceInfo.isMobile) {
        sizeSuffix = deviceInfo.devicePixelRatio > 1 ? '@2x-mobile' : '-mobile';
      } else if (deviceInfo.isTablet) {
        sizeSuffix = deviceInfo.devicePixelRatio > 1 ? '@2x-tablet' : '-tablet';
      } else {
        sizeSuffix = deviceInfo.devicePixelRatio > 1 ? '@2x' : '';
      }
      
      // Update src with optimized version
      const optimizedSrc = baseSrc.replace(/(\.[^.]+)$/, `${sizeSuffix}$1`);
      element.src = optimizedSrc;
    });
  }, [deviceInfo]);

  const handleResize = useCallback(() => {
    detectDevice();
    detectKeyboard();
    detectSafeArea();
  }, [detectDevice, detectKeyboard, detectSafeArea]);

  const handleOrientationChange = useCallback(() => {
    // Delay to allow for orientation change to complete
    setTimeout(() => {
      detectDevice();
      detectSafeArea();
    }, 100);
  }, [detectDevice, detectSafeArea]);

  useEffect(() => {
    // Initial detection
    detectDevice();
    detectSafeArea();
    
    // Set up event listeners
    window.addEventListener('resize', handleResize);
    window.addEventListener('orientationchange', handleOrientationChange);
    
    return () => {
      window.removeEventListener('resize', handleResize);
      window.removeEventListener('orientationchange', handleOrientationChange);
    };
  }, [handleResize, handleOrientationChange]);

  useEffect(() => {
    optimizeForTouch();
    setViewportMeta();
    addResponsiveClasses();
    optimizeImages();
  }, [optimizeForTouch, setViewportMeta, addResponsiveClasses, optimizeImages]);

  // Utility functions
  const getBreakpoint = useCallback(() => {
    const width = viewportInfo.width;
    if (width < BREAKPOINTS.mobile) return 'mobile';
    if (width < BREAKPOINTS.tablet) return 'tablet';
    if (width < BREAKPOINTS.desktop) return 'desktop';
    return 'large';
  }, [viewportInfo.width]);

  const isBreakpoint = useCallback((breakpoint: keyof typeof BREAKPOINTS) => {
    return viewportInfo.width >= BREAKPOINTS[breakpoint];
  }, [viewportInfo.width]);

  const getResponsiveValue = useCallback(<T,>(values: {
    mobile?: T;
    tablet?: T;
    desktop?: T;
    large?: T;
    default: T;
  }) => {
    const breakpoint = getBreakpoint();
    return values[breakpoint] || values.default;
  }, [getBreakpoint]);

  return {
    // Device information
    deviceInfo,
    viewportInfo,
    isKeyboardOpen,
    safeAreaInsets,
    
    // Utility functions
    getBreakpoint,
    isBreakpoint,
    getResponsiveValue,
    
    // Manual optimization triggers
    optimizeForTouch,
    optimizeImages,
    detectDevice,
    
    // Computed values
    isMobile: deviceInfo.isMobile,
    isTablet: deviceInfo.isTablet,
    isDesktop: deviceInfo.isDesktop,
    isPortrait: deviceInfo.orientation === 'portrait',
    isLandscape: deviceInfo.orientation === 'landscape',
    hasTouch: deviceInfo.touchSupport,
    isRetina: deviceInfo.devicePixelRatio > 1,
    
    // Responsive breakpoint checks
    isMobileBreakpoint: viewportInfo.width < BREAKPOINTS.mobile,
    isTabletBreakpoint: viewportInfo.width >= BREAKPOINTS.mobile && viewportInfo.width < BREAKPOINTS.desktop,
    isDesktopBreakpoint: viewportInfo.width >= BREAKPOINTS.desktop
  };
};

export default useMobileOptimization;
