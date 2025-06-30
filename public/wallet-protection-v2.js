// ============================================================================
// ENHANCED WALLET PROTECTION V2 - TRUST WALLET POPUP BLOCKER
// ============================================================================
// This script prevents Trust Wallet and other unwanted wallet extensions
// from showing popups and injecting providers across ALL pages
// ============================================================================

(function() {
    'use strict';
    
    // Silent initialization - no console spam
    
    // Configuration
    const config = {
        allowedWallets: ['safepal'], // Only SafePal is allowed
        blockedWallets: [
            'trustwallet', 'trust', 'metamask', 'coinbase', 'binance',
            'okx', 'phantom', 'solflare', 'tronlink', 'walletconnect',
            'rainbow', 'argent', 'imtoken', 'tokenpocket', 'mathwallet'
        ],
        blockedProviders: [
            'ethereum', 'web3', 'tronWeb', 'solana', 'near', 'algorand',
            'trustWallet', 'isTrust', 'isMetaMask', 'isCoinbaseWallet'
        ],
        debug: false // Disabled to prevent console spam
    };
    
    // Logging utility - completely disabled to prevent console spam
    function log(message, type = 'info') {
        // Silent - no logging to prevent console spam
        return;
    }
    
    // Store original values for SafePal restoration
    const originalValues = new Map();
    
    // ========================================================================
    // PROVIDER INJECTION PREVENTION
    // ========================================================================
    
    function blockProvider(providerName) {
        if (window[providerName]) {
            originalValues.set(providerName, window[providerName]);
        }

        // Check if property is already defined and configurable
        const descriptor = Object.getOwnPropertyDescriptor(window, providerName);
        if (descriptor && !descriptor.configurable) {
            console.warn(`Cannot redefine non-configurable property: ${providerName}`);
            return;
        }

        try {
            Object.defineProperty(window, providerName, {
            get: function() {
                // Allow SafePal on investment pages
                if (providerName === 'ethereum' && isSafePalAllowed()) {
                    const original = originalValues.get(providerName);
                    if (original && original.isSafePal) {
                        log(`Allowing SafePal ethereum provider`, 'allow');
                        return original;
                    }
                }
                
                log(`Blocked access to ${providerName}`, 'block');
                return undefined;
            },
            set: function(value) {
                // Store the value but check if it's allowed
                originalValues.set(providerName, value);
                
                if (value && typeof value === 'object') {
                    // Block Trust Wallet specifically
                    if (value.isTrust || value.trustWallet) {
                        log(`Blocked Trust Wallet injection on ${providerName}`, 'block');
                        return;
                    }
                    
                    // Allow SafePal
                    if (value.isSafePal && isSafePalAllowed()) {
                        log(`Allowing SafePal injection on ${providerName}`, 'allow');
                        // Don't block SafePal, but still store it
                        return;
                    }
                    
                    // Block other wallets
                    log(`Blocked wallet injection on ${providerName}`, 'block');
                }
            },
            configurable: true,
            enumerable: false
        });
        } catch (error) {
            console.warn(`Failed to define property ${providerName}:`, error.message);
        }
    }
    
    // Block all known providers
    config.blockedProviders.forEach(blockProvider);
    
    // ========================================================================
    // TRUST WALLET SPECIFIC BLOCKING
    // ========================================================================
    
    function blockTrustWallet() {
        // Block Trust Wallet global objects
        const trustWalletProps = [
            'trustwallet', 'TrustWallet', 'isTrust', 'trustWallet',
            '__TRUST_WALLET__', 'trust', 'Trust'
        ];
        
        trustWalletProps.forEach(prop => {
            Object.defineProperty(window, prop, {
                get: function() {
                    log(`Blocked Trust Wallet property: ${prop}`, 'block');
                    return undefined;
                },
                set: function(value) {
                    log(`Blocked Trust Wallet property assignment: ${prop}`, 'block');
                    // Don't actually set the value
                },
                configurable: true,
                enumerable: false
            });
        });
        
        // Block Trust Wallet detection methods
        if (window.navigator) {
            const originalUserAgent = window.navigator.userAgent;
            Object.defineProperty(window.navigator, 'userAgent', {
                get: function() {
                    // Remove Trust Wallet indicators from user agent
                    return originalUserAgent.replace(/Trust|TrustWallet/gi, '');
                },
                configurable: true
            });
        }
    }
    
    // ========================================================================
    // EVENT LISTENER BLOCKING
    // ========================================================================
    
    function blockWalletEvents() {
        const originalAddEventListener = EventTarget.prototype.addEventListener;
        const originalRemoveEventListener = EventTarget.prototype.removeEventListener;
        
        EventTarget.prototype.addEventListener = function(type, listener, options) {
            // Block wallet-related events
            const blockedEvents = [
                'ethereum#initialized', 'trust#initialized', 'wallet#initialized',
                'web3Ready', 'trustReady', 'walletReady'
            ];
            
            if (blockedEvents.includes(type)) {
                log(`Blocked event listener: ${type}`, 'block');
                return;
            }
            
            // Check listener function for wallet-related code
            if (typeof listener === 'function') {
                const listenerStr = listener.toString();
                if (listenerStr.includes('trustwallet') || 
                    listenerStr.includes('isTrust') ||
                    listenerStr.includes('Trust Wallet')) {
                    log(`Blocked Trust Wallet event listener`, 'block');
                    return;
                }
            }
            
            return originalAddEventListener.call(this, type, listener, options);
        };
        
        EventTarget.prototype.removeEventListener = function(type, listener, options) {
            return originalRemoveEventListener.call(this, type, listener, options);
        };
    }
    
    // ========================================================================
    // DOM MANIPULATION BLOCKING
    // ========================================================================
    
    function blockWalletDOM() {
        const originalAppendChild = Node.prototype.appendChild;
        const originalInsertBefore = Node.prototype.insertBefore;
        const originalReplaceChild = Node.prototype.replaceChild;
        
        function isWalletElement(element) {
            if (!element || !element.tagName) return false;
            
            const tagName = element.tagName.toLowerCase();
            const className = element.className || '';
            const id = element.id || '';
            
            // Check for Trust Wallet specific elements
            const trustWalletIndicators = [
                'trust-wallet', 'trustwallet', 'trust_wallet',
                'tw-modal', 'tw-popup', 'trust-modal'
            ];
            
            return trustWalletIndicators.some(indicator => 
                className.includes(indicator) || 
                id.includes(indicator) ||
                (element.textContent && element.textContent.includes('Trust Wallet'))
            );
        }
        
        Node.prototype.appendChild = function(child) {
            if (isWalletElement(child)) {
                log(`Blocked Trust Wallet DOM element insertion`, 'block');
                return child; // Return the element but don't append it
            }
            return originalAppendChild.call(this, child);
        };
        
        Node.prototype.insertBefore = function(child, referenceNode) {
            if (isWalletElement(child)) {
                log(`Blocked Trust Wallet DOM element insertion`, 'block');
                return child;
            }
            return originalInsertBefore.call(this, child, referenceNode);
        };
        
        Node.prototype.replaceChild = function(newChild, oldChild) {
            if (isWalletElement(newChild)) {
                log(`Blocked Trust Wallet DOM element replacement`, 'block');
                return oldChild;
            }
            return originalReplaceChild.call(this, newChild, oldChild);
        };
    }
    
    // ========================================================================
    // POPUP AND MODAL BLOCKING
    // ========================================================================
    
    function blockWalletPopups() {
        // Override window.open to block wallet popups
        const originalWindowOpen = window.open;
        window.open = function(url, name, features) {
            if (url && typeof url === 'string') {
                const lowerUrl = url.toLowerCase();
                if (lowerUrl.includes('trust') || 
                    lowerUrl.includes('wallet') ||
                    lowerUrl.includes('connect')) {
                    log(`Blocked wallet popup: ${url}`, 'block');
                    return null;
                }
            }
            return originalWindowOpen.call(this, url, name, features);
        };
        
        // Block modal creation
        const originalCreateElement = document.createElement;
        document.createElement = function(tagName) {
            const element = originalCreateElement.call(this, tagName);
            
            // Monitor for modal/popup creation
            if (tagName.toLowerCase() === 'div') {
                const originalSetAttribute = element.setAttribute;
                element.setAttribute = function(name, value) {
                    if (name === 'class' && typeof value === 'string') {
                        const lowerValue = value.toLowerCase();
                        if (lowerValue.includes('trust') || 
                            lowerValue.includes('wallet-modal') ||
                            lowerValue.includes('connect-modal')) {
                            log(`Blocked wallet modal class: ${value}`, 'block');
                            return;
                        }
                    }
                    return originalSetAttribute.call(this, name, value);
                };
            }
            
            return element;
        };
    }
    
    // ========================================================================
    // SAFEPAL ALLOWLIST
    // ========================================================================
    
    function isSafePalAllowed() {
        // Allow SafePal on investment and dashboard pages
        const allowedPaths = [
            '/investment', '/dashboard', '/admin', '/kyc'
        ];
        
        return allowedPaths.some(path => 
            window.location.pathname.startsWith(path)
        );
    }
    
    function restoreSafePal() {
        if (!isSafePalAllowed()) return;
        
        // Restore SafePal ethereum provider if it exists
        const safePalProvider = originalValues.get('ethereum');
        if (safePalProvider && safePalProvider.isSafePal) {
            log('Restoring SafePal provider for allowed page', 'allow');
            Object.defineProperty(window, 'ethereum', {
                value: safePalProvider,
                writable: true,
                configurable: true,
                enumerable: true
            });
        }
    }
    
    // ========================================================================
    // INITIALIZATION
    // ========================================================================
    
    function initialize() {
        log('Initializing enhanced wallet protection...');
        
        // Apply all blocking mechanisms
        blockTrustWallet();
        blockWalletEvents();
        blockWalletDOM();
        blockWalletPopups();
        
        // Restore SafePal if on allowed page
        restoreSafePal();
        
        // Monitor for page changes (SPA navigation)
        let currentPath = window.location.pathname;
        setInterval(() => {
            if (window.location.pathname !== currentPath) {
                currentPath = window.location.pathname;
                log(`Page changed to: ${currentPath}`);
                restoreSafePal();
            }
        }, 1000);
        
        log('Enhanced wallet protection active');
    }
    
    // ========================================================================
    // CLEANUP AND MONITORING
    // ========================================================================
    
    function monitorAndCleanup() {
        // Periodically check for and remove Trust Wallet elements
        setInterval(() => {
            const trustElements = document.querySelectorAll('[class*="trust"], [id*="trust"], [class*="Trust"], [id*="Trust"]');
            trustElements.forEach(element => {
                if (element.textContent && element.textContent.includes('Trust Wallet')) {
                    log('Removing Trust Wallet element from DOM', 'block');
                    element.remove();
                }
            });
        }, 2000);
        
        // Monitor for Trust Wallet property injection attempts
        setInterval(() => {
            if (window.trustwallet || window.isTrust || window.TrustWallet) {
                log('Detected Trust Wallet property injection attempt', 'block');
                delete window.trustwallet;
                delete window.isTrust;
                delete window.TrustWallet;
            }
        }, 1000);
    }
    
    // ========================================================================
    // START PROTECTION
    // ========================================================================
    
    // Initialize immediately
    initialize();
    
    // Start monitoring
    monitorAndCleanup();
    
    // Re-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    }
    
    // Re-initialize on window load
    window.addEventListener('load', initialize);
    
    log('Enhanced Wallet Protection V2 fully loaded and active');
    
})();
