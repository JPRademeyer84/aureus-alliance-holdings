
// SafePal wallet provider detection only
import type { WalletProviderName } from "../useWalletConnection";

export function getProviderObject(provider: WalletProviderName) {
  // Don't access wallet providers on homepage to prevent Trust Wallet popup
  if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
    return null;
  }

  switch (provider) {
    case "safepal":
      // SafePal detection strategy:
      // 1. Check if SafePal is explicitly identified
      if (window.ethereum && (
        window.ethereum.isSafePal ||
        window.ethereum.isSafeWallet ||
        (window.ethereum as any).isSafePalWallet ||
        (window.ethereum as any).wallet === 'SafePal'
      )) {
        return window.ethereum;
      }

      // 2. Check dedicated SafePal objects
      if (window.safepal?.ethereum) return window.safepal.ethereum;
      if (window.safepal) return window.safepal;
      if (window.safepalProvider) return window.safepalProvider;
      if ((window as any).SafePal) return (window as any).SafePal;

      // 3. Check providers array
      if (window.ethereum?.providers) {
        const safepalProvider = window.ethereum.providers.find((p: any) =>
          p.isSafePal || p.isSafeWallet || (p as any).isSafePalWallet
        );
        if (safepalProvider) return safepalProvider;
      }

      // 4. Fallback: If window.ethereum exists and no other wallet is detected,
      // assume it might be SafePal (but be more restrictive to avoid false positives)
      if (window.ethereum) {
        try {
          const ethereum = window.ethereum;
          const isTrustWallet = ethereum.isTrust ||
                               ethereum.isTrustWallet ||
                               (ethereum as any).isTrustWallet ||
                               (ethereum as any).isTrust ||
                               ethereum.constructor?.name?.toLowerCase().includes('trust');

          if (!ethereum.isMetaMask && !isTrustWallet) {
            return ethereum;
          }
        } catch (error) {
          // If there's an error accessing wallet properties, skip this provider
          console.warn('Error checking wallet properties:', error);
        }
      }

      return null;
    default:
      return null;
  }
}

// Debug function to log SafePal wallet objects
function debugSafePalObjects() {
  console.log("=== SAFEPAL DEBUG INFO ===");
  console.log("window.ethereum:", window.ethereum);
  console.log("window.safepal:", window.safepal);
  console.log("window.SafePal:", (window as any).SafePal);
  console.log("window.safepalProvider:", window.safepalProvider);

  if (window.ethereum) {
    console.log("ethereum.isSafePal:", window.ethereum.isSafePal);
    console.log("ethereum.isSafeWallet:", window.ethereum.isSafeWallet);
    console.log("ethereum.isSafePalWallet:", (window.ethereum as any).isSafePalWallet);
    console.log("ethereum.providers:", window.ethereum.providers);
    console.log("ethereum constructor:", window.ethereum.constructor?.name);
  }
  console.log("========================");
}

// Check if SafePal wallet provider exists and is properly structured
export function isWalletProviderAvailable(provider: WalletProviderName): boolean {
  // Don't run wallet detection on homepage to prevent Trust Wallet popup
  if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
    console.log('Skipping wallet detection on homepage');
    return false;
  }

  const providerObj = getProviderObject(provider);

  if (!providerObj) return false;

  // Check if this is actually Trust Wallet and reject it
  try {
    if (providerObj.isTrust ||
        providerObj.isTrustWallet ||
        (providerObj as any).isTrustWallet ||
        providerObj.constructor?.name?.toLowerCase().includes('trust')) {
      console.log('Detected Trust Wallet, rejecting for SafePal detection');
      return false;
    }
  } catch (error) {
    // If there's an error checking Trust Wallet properties, continue with SafePal detection
    console.warn('Error checking Trust Wallet properties:', error);
  }

  // Silent detection - no console spam
  try {
    // Detection logic runs silently to prevent console spam
  } catch (error) {
    // Silent error handling
  }

  // SafePal detection - be more lenient since SafePal often injects as main ethereum provider
  if (provider === "safepal") {
    // If we found a provider object, check if it has connection methods
    if (providerObj) {
      const hasEthereumMethods = typeof providerObj.request === 'function' ||
                                typeof providerObj.sendAsync === 'function' ||
                                (providerObj.ethereum && typeof providerObj.ethereum.request === 'function');

      // If it has ethereum methods, consider it available
      if (hasEthereumMethods) return true;
    }

    // Check for SafePal-specific indicators
    const hasSafePalIndicators = !!(
      providerObj?.isSafePal ||
      providerObj?.isSafeWallet ||
      (providerObj as any)?.isSafePalWallet ||
      (providerObj as any)?.wallet === 'SafePal'
    );

    // Check if SafePal objects exist in window
    const isInWindow = !!(window.safepal || (window as any).SafePal);

    // Check if SafePal is in providers array
    const isInProviders = window.ethereum?.providers?.some((p: any) =>
      p.isSafePal || p.isSafeWallet || (p as any).isSafePalWallet
    );

    // SafePal is available if any of these conditions are met
    return hasSafePalIndicators || isInWindow || isInProviders || !!providerObj;
  }

  // Default check for SafePal
  return typeof providerObj.request === 'function';
}

// Get a friendly name for display purposes
export function getWalletProviderDisplayName(provider: WalletProviderName): string {
  switch (provider) {
    case "safepal":
      return "SafePal";
    default:
      return "SafePal";
  }
}
