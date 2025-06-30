import { useState, useEffect, useCallback, useRef } from 'react';

interface PerformanceMetrics {
  loadTime: number;
  renderTime: number;
  memoryUsage: number;
  networkRequests: number;
  cacheHitRate: number;
  fps: number;
  bundleSize: number;
}

interface OptimizationSettings {
  enableImageLazyLoading: boolean;
  enableCodeSplitting: boolean;
  enableCaching: boolean;
  enableCompression: boolean;
  enablePreloading: boolean;
  enableServiceWorker: boolean;
}

export const usePerformanceOptimization = () => {
  const [metrics, setMetrics] = useState<PerformanceMetrics>({
    loadTime: 0,
    renderTime: 0,
    memoryUsage: 0,
    networkRequests: 0,
    cacheHitRate: 0,
    fps: 0,
    bundleSize: 0
  });

  const [settings, setSettings] = useState<OptimizationSettings>({
    enableImageLazyLoading: true,
    enableCodeSplitting: true,
    enableCaching: true,
    enableCompression: true,
    enablePreloading: true,
    enableServiceWorker: true
  });

  const [isOptimizing, setIsOptimizing] = useState(false);
  const performanceObserver = useRef<PerformanceObserver | null>(null);
  const frameCount = useRef(0);
  const lastTime = useRef(performance.now());

  // Measure page load performance
  const measureLoadPerformance = useCallback(() => {
    if (typeof window !== 'undefined' && 'performance' in window) {
      const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
      const loadTime = navigation.loadEventEnd - navigation.fetchStart;
      
      setMetrics(prev => ({
        ...prev,
        loadTime: Math.round(loadTime)
      }));
    }
  }, []);

  // Measure render performance
  const measureRenderPerformance = useCallback(() => {
    if (typeof window !== 'undefined' && 'performance' in window) {
      const paintEntries = performance.getEntriesByType('paint');
      const firstContentfulPaint = paintEntries.find(entry => entry.name === 'first-contentful-paint');
      
      if (firstContentfulPaint) {
        setMetrics(prev => ({
          ...prev,
          renderTime: Math.round(firstContentfulPaint.startTime)
        }));
      }
    }
  }, []);

  // Measure memory usage
  const measureMemoryUsage = useCallback(() => {
    if (typeof window !== 'undefined' && 'performance' in window && 'memory' in performance) {
      const memory = (performance as any).memory;
      const usedMemory = memory.usedJSHeapSize / 1024 / 1024; // Convert to MB
      
      setMetrics(prev => ({
        ...prev,
        memoryUsage: Math.round(usedMemory * 100) / 100
      }));
    }
  }, []);

  // Measure FPS
  const measureFPS = useCallback(() => {
    const now = performance.now();
    frameCount.current++;
    
    if (now >= lastTime.current + 1000) {
      const fps = Math.round((frameCount.current * 1000) / (now - lastTime.current));
      
      setMetrics(prev => ({
        ...prev,
        fps
      }));
      
      frameCount.current = 0;
      lastTime.current = now;
    }
    
    requestAnimationFrame(measureFPS);
  }, []);

  // Optimize images with lazy loading
  const optimizeImages = useCallback(() => {
    if (!settings.enableImageLazyLoading) return;

    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target as HTMLImageElement;
          const src = img.dataset.src;
          
          if (src) {
            img.src = src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
          }
        }
      });
    }, {
      rootMargin: '50px'
    });

    images.forEach(img => imageObserver.observe(img));
  }, [settings.enableImageLazyLoading]);

  // Preload critical resources
  const preloadCriticalResources = useCallback(() => {
    if (!settings.enablePreloading) return;

    const criticalResources = [
      '/api/auth/profile.php',
      '/api/investments/packages.php',
      '/fonts/inter-var.woff2'
    ];

    criticalResources.forEach(resource => {
      const link = document.createElement('link');
      link.rel = 'preload';
      link.href = resource;
      
      if (resource.includes('.woff2')) {
        link.as = 'font';
        link.type = 'font/woff2';
        link.crossOrigin = 'anonymous';
      } else if (resource.includes('.php')) {
        link.as = 'fetch';
        link.crossOrigin = 'anonymous';
      }
      
      document.head.appendChild(link);
    });
  }, [settings.enablePreloading]);

  // Enable service worker for caching
  const enableServiceWorker = useCallback(async () => {
    if (!settings.enableServiceWorker || !('serviceWorker' in navigator)) return;

    try {
      const registration = await navigator.serviceWorker.register('/sw.js');
      console.log('Service Worker registered:', registration);
    } catch (error) {
      console.error('Service Worker registration failed:', error);
    }
  }, [settings.enableServiceWorker]);

  // Optimize bundle size with code splitting
  const optimizeBundleSize = useCallback(() => {
    if (!settings.enableCodeSplitting) return;

    // Dynamic imports for heavy components
    const heavyComponents = [
      () => import('@/components/admin/AdminDashboard'),
      () => import('@/components/leaderboard/GoldDiggersClub')
    ];

    // Preload components that are likely to be used
    heavyComponents.forEach(importFn => {
      setTimeout(() => {
        importFn().catch(console.error);
      }, 2000);
    });
  }, [settings.enableCodeSplitting]);

  // Cache API responses
  const enableAPICache = useCallback(() => {
    if (!settings.enableCaching) return;

    const originalFetch = window.fetch;
    const cache = new Map();

    window.fetch = async (input: RequestInfo | URL, init?: RequestInit) => {
      let url: string;

      if (typeof input === 'string') {
        url = input;
      } else if (input instanceof URL) {
        url = input.href;
      } else if (input instanceof Request) {
        url = input.url;
      } else {
        // Fallback for any other case
        url = String(input);
      }

      const method = init?.method || 'GET';
      
      // Only cache GET requests
      if (method !== 'GET') {
        return originalFetch(input, init);
      }

      // Check cache first
      const cacheKey = `${method}:${url}`;
      const cached = cache.get(cacheKey);
      
      if (cached && Date.now() - cached.timestamp < 300000) { // 5 minutes
        setMetrics(prev => ({
          ...prev,
          cacheHitRate: prev.cacheHitRate + 1
        }));
        return Promise.resolve(new Response(cached.data));
      }

      // Fetch and cache
      const response = await originalFetch(input, init);
      const clonedResponse = response.clone();
      const data = await clonedResponse.text();
      
      cache.set(cacheKey, {
        data,
        timestamp: Date.now()
      });

      setMetrics(prev => ({
        ...prev,
        networkRequests: prev.networkRequests + 1
      }));

      return response;
    };
  }, [settings.enableCaching]);

  // Debounce function for performance
  const debounce = useCallback((func: Function, wait: number) => {
    let timeout: NodeJS.Timeout;
    return function executedFunction(...args: any[]) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }, []);

  // Throttle function for performance
  const throttle = useCallback((func: Function, limit: number) => {
    let inThrottle: boolean;
    return function executedFunction(...args: any[]) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }, []);

  // Run all optimizations
  const runOptimizations = useCallback(async () => {
    setIsOptimizing(true);
    
    try {
      optimizeImages();
      preloadCriticalResources();
      await enableServiceWorker();
      optimizeBundleSize();
      enableAPICache();
      
      // Start performance monitoring
      measureLoadPerformance();
      measureRenderPerformance();
      measureMemoryUsage();
      requestAnimationFrame(measureFPS);
      
    } catch (error) {
      console.error('Performance optimization failed:', error);
    } finally {
      setIsOptimizing(false);
    }
  }, [
    optimizeImages,
    preloadCriticalResources,
    enableServiceWorker,
    optimizeBundleSize,
    enableAPICache,
    measureLoadPerformance,
    measureRenderPerformance,
    measureMemoryUsage,
    measureFPS
  ]);

  // Performance monitoring
  useEffect(() => {
    if (typeof window !== 'undefined' && 'PerformanceObserver' in window) {
      performanceObserver.current = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        entries.forEach(entry => {
          if (entry.entryType === 'navigation') {
            measureLoadPerformance();
          } else if (entry.entryType === 'paint') {
            measureRenderPerformance();
          }
        });
      });

      performanceObserver.current.observe({ entryTypes: ['navigation', 'paint', 'measure'] });
    }

    return () => {
      if (performanceObserver.current) {
        performanceObserver.current.disconnect();
      }
    };
  }, [measureLoadPerformance, measureRenderPerformance]);

  // Auto-run optimizations on mount
  useEffect(() => {
    runOptimizations();
  }, [runOptimizations]);

  // Periodic memory monitoring
  useEffect(() => {
    const interval = setInterval(measureMemoryUsage, 5000);
    return () => clearInterval(interval);
  }, [measureMemoryUsage]);

  return {
    // Performance metrics
    metrics,
    
    // Optimization settings
    settings,
    setSettings,
    
    // Optimization functions
    runOptimizations,
    optimizeImages,
    preloadCriticalResources,
    enableServiceWorker,
    optimizeBundleSize,
    enableAPICache,
    
    // Utility functions
    debounce,
    throttle,
    
    // State
    isOptimizing,
    
    // Computed values
    performanceScore: Math.round(
      (100 - Math.min(metrics.loadTime / 50, 100)) * 0.3 +
      (100 - Math.min(metrics.renderTime / 30, 100)) * 0.3 +
      Math.min(metrics.fps / 60 * 100, 100) * 0.2 +
      (100 - Math.min(metrics.memoryUsage / 100, 100)) * 0.2
    ),
    
    isPerformant: metrics.loadTime < 3000 && metrics.renderTime < 1500 && metrics.fps > 30
  };
};

export default usePerformanceOptimization;
