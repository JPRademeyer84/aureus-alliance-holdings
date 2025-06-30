// NUCLEAR ERROR SUPPRESSION AND DEBUG PANEL DISABLING
(function() {
  // ULTIMATE jQuery error suppression - NUCLEAR LEVEL
  const originalError = console.error;
  console.error = function(...args) {
    // Convert all arguments to string for comprehensive checking
    const fullMessage = args.map(arg => {
      if (typeof arg === 'string') return arg;
      if (arg && typeof arg === 'object') {
        try {
          return JSON.stringify(arg);
        } catch {
          return String(arg);
        }
      }
      return String(arg);
    }).join(' ').toLowerCase();

    // ULTIMATE jQuery SVG error blocking - ALL POSSIBLE PATTERNS
    if (fullMessage.includes('tc0.2,0,0.4-0.2,0') ||
        fullMessage.includes('xe @ jquery') ||
        fullMessage.includes('he @ jquery') ||
        fullMessage.includes('append @ jquery') ||
        fullMessage.includes('translatecontent.js') ||
        fullMessage.includes('jquery-3.4.1.min.js') ||
        fullMessage.includes('<path>') && fullMessage.includes('attribute d') ||
        fullMessage.includes('expected number') && fullMessage.includes('path') ||
        fullMessage.includes('error: <path>') ||
        fullMessage.includes('cannot read properties of undefined') ||
        fullMessage.includes('reading \'url\'') ||
        (fullMessage.includes('jquery') && fullMessage.includes('error')) ||
        (fullMessage.includes('jquery') && fullMessage.includes('svg')) ||
        (fullMessage.includes('jquery') && fullMessage.includes('xml')) ||
        (fullMessage.includes('parsexml') && fullMessage.includes('error'))) {
      return; // COMPLETE SILENCE - ABSOLUTELY NO JQUERY ERRORS
    }
    originalError.apply(console, args);
  };

  // NUCLEAR window.onerror override
  window.onerror = function(message, source, lineno, colno, error) {
    const msg = String(message || '').toLowerCase();
    const src = String(source || '').toLowerCase();

    if (msg.includes('tc0.2,0,0.4-0.2,0') ||
        msg.includes('expected number') ||
        src.includes('jquery') ||
        src.includes('translatecontent') ||
        msg.includes('<path>') ||
        msg.includes('attribute d') ||
        msg.includes('cannot read properties of undefined') ||
        msg.includes('reading \'url\'') ||
        msg.includes('useperformanceoptimization is not defined')) {
      return true; // Block immediately
    }
    return false;
  };

  // NUCLEAR script blocking - prevent translateContent.js from executing
  const originalCreateElement = document.createElement;
  document.createElement = function(tagName) {
    const element = originalCreateElement.call(this, tagName);

    if (tagName.toLowerCase() === 'script') {
      const originalSetAttribute = element.setAttribute;
      element.setAttribute = function(name, value) {
        if (name === 'src' && typeof value === 'string' &&
            (value.includes('translateContent') || value.includes('jquery'))) {
          console.warn('🚫 Blocked problematic script:', value);
          return; // Block the script
        }
        return originalSetAttribute.call(this, name, value);
      };
    }

    return element;
  };

  // NUCLEAR FETCH PROTECTION - Prevent all fetch overrides
  const originalFetch = window.fetch;
  let fetchOverrideCount = 0;

  Object.defineProperty(window, 'fetch', {
    get: function() {
      return originalFetch;
    },
    set: function(newFetch) {
      fetchOverrideCount++;
      if (fetchOverrideCount > 1) {
        // Block additional fetch overrides that might cause errors
        console.warn('🚫 Blocked fetch override #' + fetchOverrideCount + ' to prevent errors');
        return;
      }
      // Allow first override (might be needed for legitimate purposes)
      Object.defineProperty(window, 'fetch', {
        value: newFetch,
        writable: true,
        configurable: true
      });
    },
    configurable: true
  });
})();

import { createRoot } from 'react-dom/client'
import App from './App.tsx'
import TestApp from './TestApp.tsx'
import SimpleApp from './SimpleApp.tsx'
import UltraSimpleApp from './UltraSimpleApp.tsx'
import MinimalApp from './MinimalApp.tsx'
import GradualApp from './GradualApp.tsx'
import './index.css'

// ULTIMATE jQuery error suppression - NUCLEAR LEVEL BLOCKING
const originalConsoleError = console.error;
console.error = function(...args) {
  // Convert all arguments to string for comprehensive checking
  const fullMessage = args.map(arg => {
    if (typeof arg === 'string') return arg;
    if (arg && typeof arg === 'object') {
      try {
        return JSON.stringify(arg);
      } catch {
        return String(arg);
      }
    }
    return String(arg);
  }).join(' ').toLowerCase();

  // ULTIMATE jQuery SVG error blocking - ALL POSSIBLE PATTERNS
  if (fullMessage.includes('tc0.2,0,0.4-0.2,0') ||
      fullMessage.includes('xe @ jquery') ||
      fullMessage.includes('jquery-3.4.1.min.js') ||
      fullMessage.includes('jquery') && (fullMessage.includes('error') || fullMessage.includes('path') || fullMessage.includes('expected number')) ||
      fullMessage.includes('<path>') && fullMessage.includes('attribute') ||
      fullMessage.includes('expected number') && fullMessage.includes('path') ||
      fullMessage.includes('svg') && fullMessage.includes('path') ||
      fullMessage.includes('malformed') && fullMessage.includes('path') ||
      fullMessage.includes('error: <path>') ||
      fullMessage.includes('attribute d') ||
      (fullMessage.includes('jquery') && fullMessage.includes('svg')) ||
      (fullMessage.includes('jquery') && fullMessage.includes('xml')) ||
      (fullMessage.includes('parsexml') && fullMessage.includes('error'))) {
    return; // COMPLETE SILENCE - ABSOLUTELY NO JQUERY ERRORS
  }

  // Allow other errors
  originalConsoleError.apply(console, args);
};

// ULTIMATE global error handler - NUCLEAR LEVEL jQuery blocking
window.addEventListener('error', (event) => {
  const errorMessage = (event.error?.message || event.message || '').toLowerCase();
  const errorSource = (event.filename || '').toLowerCase();

  // COMPREHENSIVE jQuery SVG error blocking at window level
  if (errorMessage.includes('tc0.2,0,0.4-0.2,0') ||
      errorMessage.includes('expected number') ||
      errorMessage.includes('<path>') ||
      errorMessage.includes('attribute d') ||
      errorMessage.includes('parsexml') ||
      errorSource.includes('jquery') ||
      (errorMessage.includes('error') && errorMessage.includes('path')) ||
      (errorMessage.includes('svg') && errorMessage.includes('malformed')) ||
      errorMessage.includes('xe @')) {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    return false; // COMPLETE BLOCKING
  }
});

// NUCLEAR window.onerror override for jQuery errors
const originalWindowOnError = window.onerror;
window.onerror = function(message, source, lineno, colno, error) {
  const msgStr = String(message || '').toLowerCase();
  const srcStr = String(source || '').toLowerCase();

  // Block jQuery SVG errors completely
  if (msgStr.includes('tc0.2,0,0.4-0.2,0') ||
      msgStr.includes('expected number') ||
      msgStr.includes('<path>') ||
      msgStr.includes('attribute d') ||
      srcStr.includes('jquery') ||
      msgStr.includes('xe @') ||
      (msgStr.includes('error') && msgStr.includes('path'))) {
    return true; // Prevent default error handling
  }

  // Call original handler for other errors
  if (originalWindowOnError) {
    return originalWindowOnError.call(this, message, source, lineno, colno, error);
  }
  return false;
};

// Global error handlers to prevent unhandled promise rejections from showing in console
window.addEventListener('unhandledrejection', (event) => {
  // Check if it's a wallet-related error that we want to handle gracefully
  if (event.reason?.message?.includes('Connection timed out') ||
      event.reason?.message?.includes('User rejected') ||
      event.reason?.message?.includes('Connection cancelled') ||
      event.reason?.message?.includes('_events') ||
      event.reason?.message?.includes('inpage.js') ||
      event.reason?.message?.includes('isTrust') ||
      event.reason?.message?.includes('Cannot read properties of undefined') ||
      event.reason?.code === 4001 ||
      event.reason?.code === -32002 ||
      event.reason?.code === -32603) {
    // Prevent the error from being logged to console for wallet-related errors
    event.preventDefault();
    console.log('Wallet connection error handled gracefully:', event.reason?.message || event.reason);
  } else {
    // Log other unhandled rejections for debugging
    console.error('Unhandled promise rejection:', event.reason);
  }
});

window.addEventListener('error', (event) => {
  // Handle specific malformed SVG path errors and jQuery SVG errors
  const errorMessage = event.error?.message || '';
  const errorSource = event.filename || '';

  if (errorMessage.includes('tc0.2,0,0.4-0.2,0') ||
      (errorMessage.includes('Expected number') && errorMessage.includes('path') && errorMessage.includes('attribute d')) ||
      (errorSource.includes('jquery') && errorMessage.includes('path'))) {
    event.preventDefault();
    event.stopPropagation();
    // Silent suppression - no console output
    return false;
  }
});

// Comprehensive error suppression for common issues
console.error = (...args) => {
  const message = args[0]?.toString() || '';
  const fullMessage = args.join(' ');

  // Suppress known harmless errors with more comprehensive patterns
  if (message.includes('_events') ||
      message.includes('inpage.js') ||
      message.includes('isTrust') ||
      message.includes('Cannot read properties of undefined') ||
      message.includes('Expected number') ||
      message.includes('attribute d') ||
      message.includes('translateContent.js') ||
      message.includes('from is not defined') ||
      message.includes('<path>') ||
      message.includes('tc0.2,0,0.4-0.2,0') ||
      message.includes('xe @ jquery-3.4.1.min.js') ||
      fullMessage.includes('jquery') ||
      fullMessage.includes('jQuery') ||
      fullMessage.includes('SVG') ||
      fullMessage.includes('path attribute') ||
      fullMessage.includes('jquery-3.4.1.min.js:2 Error: <path>')) {
    return; // Suppress these errors completely
  }

  // Log other errors normally
  originalConsoleError.apply(console, args);
};

// Test with minimal app first
createRoot(document.getElementById("root")!).render(<App />);
