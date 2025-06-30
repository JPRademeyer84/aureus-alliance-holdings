import { useEffect, useCallback, useRef } from 'react';

interface WalletProtectionConfig {
  allowedWallets: string[];
  blockedWallets: string[];
  allowedPaths: string[];
  debug?: boolean;
}

const defaultConfig: WalletProtectionConfig = {
  allowedWallets: ['safepal'],
  blockedWallets: [
    'trustwallet', 'trust', 'metamask', 'coinbase', 'binance',
    'okx', 'phantom', 'solflare', 'tronlink', 'walletconnect',
    'rainbow', 'argent', 'imtoken', 'tokenpocket', 'mathwallet'
  ],
  allowedPaths: ['/investment', '/dashboard', '/admin', '/kyc'],
  debug: false // Disabled to prevent console spam
};

export const useWalletProtection = (config: Partial<WalletProtectionConfig> = {}) => {
  const finalConfig = { ...defaultConfig, ...config };
  const protectionActive = useRef(false);
  const originalProviders = useRef(new Map());

  const log = useCallback((message: string, type: 'info' | 'block' | 'allow' = 'info') => {
    if (!finalConfig.debug) return;
    const prefix = type === 'block' ? '🚫' : type === 'allow' ? '✅' : '🛡️';
    console.log(`${prefix} Wallet Protection Hook: ${message}`);
  }, [finalConfig.debug]);

  const isCurrentPathAllowed = useCallback(() => {
    return finalConfig.allowedPaths.some(path => 
      window.location.pathname.startsWith(path)
    );
  }, [finalConfig.allowedPaths]);

  const blockTrustWalletProvider = useCallback(() => {
    // Block Trust Wallet specific properties
    const trustWalletProps = [
      'trustwallet', 'TrustWallet', 'isTrust', 'trustWallet',
      '__TRUST_WALLET__', 'trust', 'Trust'
    ];

    trustWalletProps.forEach(prop => {
      if (window[prop as keyof Window]) {
        originalProviders.current.set(prop, window[prop as keyof Window]);
      }

      Object.defineProperty(window, prop, {
        get: function() {
          log(`Blocked Trust Wallet property access: ${prop}`, 'block');
          return undefined;
        },
        set: function(value) {
          log(`Blocked Trust Wallet property assignment: ${prop}`, 'block');
          // Store but don't actually set
          originalProviders.current.set(prop, value);
        },
        configurable: true,
        enumerable: false
      });
    });
  }, [log]);

  const blockEthereumProvider = useCallback(() => {
    if (window.ethereum) {
      originalProviders.current.set('ethereum', window.ethereum);
    }

    Object.defineProperty(window, 'ethereum', {
      get: function() {
        const original = originalProviders.current.get('ethereum');
        
        // Allow SafePal on allowed paths
        if (isCurrentPathAllowed() && original && original.isSafePal) {
          log('Allowing SafePal ethereum provider', 'allow');
          return original;
        }
        
        // Block Trust Wallet
        if (original && (original.isTrust || original.trustWallet)) {
          log('Blocked Trust Wallet ethereum provider', 'block');
          return undefined;
        }
        
        // Block other wallets on non-allowed paths
        if (!isCurrentPathAllowed()) {
          log('Blocked ethereum provider on non-allowed path', 'block');
          return undefined;
        }
        
        return original;
      },
      set: function(value) {
        originalProviders.current.set('ethereum', value);
        
        if (value && typeof value === 'object') {
          // Always block Trust Wallet
          if (value.isTrust || value.trustWallet) {
            log('Blocked Trust Wallet ethereum provider injection', 'block');
            return;
          }
          
          // Allow SafePal
          if (value.isSafePal) {
            log('Allowing SafePal ethereum provider injection', 'allow');
            return;
          }
        }
      },
      configurable: true,
      enumerable: false
    });
  }, [log, isCurrentPathAllowed]);

  const blockWalletEvents = useCallback(() => {
    const originalAddEventListener = EventTarget.prototype.addEventListener;
    
    EventTarget.prototype.addEventListener = function(type, listener, options) {
      // Block Trust Wallet specific events
      const blockedEvents = [
        'trust#initialized', 'trustwallet#initialized', 'Trust#initialized'
      ];
      
      if (blockedEvents.includes(type)) {
        log(`Blocked Trust Wallet event listener: ${type}`, 'block');
        return;
      }
      
      // Check listener function for Trust Wallet code
      if (typeof listener === 'function') {
        const listenerStr = listener.toString();
        if (listenerStr.includes('trustwallet') || 
            listenerStr.includes('isTrust') ||
            listenerStr.includes('Trust Wallet')) {
          log('Blocked Trust Wallet event listener', 'block');
          return;
        }
      }
      
      return originalAddEventListener.call(this, type, listener, options);
    };
  }, [log]);

  const removeTrustWalletElements = useCallback(() => {
    // Remove Trust Wallet modal/popup elements
    const trustSelectors = [
      '[class*="trust-wallet"]',
      '[class*="trustwallet"]',
      '[class*="Trust"]',
      '[id*="trust-wallet"]',
      '[id*="trustwallet"]',
      '[data-testid*="trust"]'
    ];

    trustSelectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach(element => {
        if (element.textContent && 
            (element.textContent.includes('Trust Wallet') || 
             element.textContent.includes('Connect Wallet'))) {
          log('Removing Trust Wallet DOM element', 'block');
          element.remove();
        }
      });
    });
  }, [log]);

  const preventTrustWalletPopups = useCallback(() => {
    // Override window.open to block Trust Wallet popups
    const originalWindowOpen = window.open;
    
    window.open = function(url, name, features) {
      if (url && typeof url === 'string') {
        const lowerUrl = url.toLowerCase();
        if (lowerUrl.includes('trust') || 
            lowerUrl.includes('trustwallet') ||
            (lowerUrl.includes('wallet') && lowerUrl.includes('connect'))) {
          log(`Blocked Trust Wallet popup: ${url}`, 'block');
          return null;
        }
      }
      return originalWindowOpen.call(this, url, name, features);
    };
  }, [log]);

  const activateProtection = useCallback(() => {
    if (protectionActive.current) return;
    
    log('Activating wallet protection...');
    
    blockTrustWalletProvider();
    blockEthereumProvider();
    blockWalletEvents();
    preventTrustWalletPopups();
    
    protectionActive.current = true;
    log('Wallet protection activated');
  }, [blockTrustWalletProvider, blockEthereumProvider, blockWalletEvents, preventTrustWalletPopups, log]);

  const deactivateProtection = useCallback(() => {
    if (!protectionActive.current) return;
    
    log('Deactivating wallet protection...');
    
    // Restore original providers if on allowed path
    if (isCurrentPathAllowed()) {
      const originalEthereum = originalProviders.current.get('ethereum');
      if (originalEthereum && originalEthereum.isSafePal) {
        Object.defineProperty(window, 'ethereum', {
          value: originalEthereum,
          writable: true,
          configurable: true,
          enumerable: true
        });
        log('Restored SafePal ethereum provider', 'allow');
      }
    }
    
    protectionActive.current = false;
    log('Wallet protection deactivated');
  }, [log, isCurrentPathAllowed]);

  const monitorAndCleanup = useCallback(() => {
    // Periodically remove Trust Wallet elements
    const cleanupInterval = setInterval(() => {
      removeTrustWalletElements();
      
      // Check for Trust Wallet property injection
      if (window.trustwallet || window.isTrust || window.TrustWallet) {
        log('Detected Trust Wallet property injection, removing...', 'block');
        delete (window as any).trustwallet;
        delete (window as any).isTrust;
        delete (window as any).TrustWallet;
      }
    }, 2000);

    return () => clearInterval(cleanupInterval);
  }, [removeTrustWalletElements, log]);

  useEffect(() => {
    // Always activate protection on mount
    activateProtection();
    
    // Start monitoring
    const cleanup = monitorAndCleanup();
    
    // Monitor path changes
    let currentPath = window.location.pathname;
    const pathMonitor = setInterval(() => {
      if (window.location.pathname !== currentPath) {
        currentPath = window.location.pathname;
        log(`Path changed to: ${currentPath}`);
        
        // Reactivate protection for new path
        deactivateProtection();
        activateProtection();
      }
    }, 1000);

    return () => {
      cleanup();
      clearInterval(pathMonitor);
      deactivateProtection();
    };
  }, [activateProtection, deactivateProtection, monitorAndCleanup, log]);

  return {
    isProtectionActive: protectionActive.current,
    isCurrentPathAllowed: isCurrentPathAllowed(),
    activateProtection,
    deactivateProtection,
    removeTrustWalletElements
  };
};

export default useWalletProtection;
