
import { useToast } from "@/hooks/use-toast";
import { useState, useEffect } from "react";
import { CHAINS } from "./utils/chains";
import { getProviderObject, isWalletProviderAvailable } from "./utils/walletProviders";
import { registerWalletEventHandlers, removeWalletEventHandlers } from "./utils/walletEventHandlers";
import { logWalletConnection } from "./utils/supabaseLogger";
import { setCurrentProviderLocal, resetProviderState } from "./utils/providerUtils";
import { useWalletEventHandlers } from "./utils/useWalletEventHandlers";
import { connectWalletHelper, switchChainHelper, disconnectWalletHelper } from "./utils/walletConnectionHelpers";

export type WalletProviderName = "safepal";

export interface UseWalletConnection {
  walletAddress: string;
  isConnecting: boolean;
  connectWallet: (provider: WalletProviderName) => Promise<void>;
  connectionError: string | null;
  chainId: string | null;
  switchChain: (chainId: string) => Promise<boolean>;
  disconnectWallet: () => void;
  currentProvider: WalletProviderName | null;
}

export const useWalletConnection = (): UseWalletConnection => {
  const { toast } = useToast();
  const [walletAddress, setWalletAddress] = useState("");
  const [isConnecting, setIsConnecting] = useState(false);
  const [connectionError, setConnectionError] = useState<string | null>(null);
  const [chainId, setChainId] = useState<string | null>(null);
  const [currentProvider, setCurrentProvider] = useState<WalletProviderName | null>(null);

  // Load saved wallet connection on mount (but don't auto-connect to prevent popups)
  useEffect(() => {
    const loadSavedConnection = async () => {
      try {
        const savedWallet = localStorage.getItem('wallet_address');
        const savedProvider = localStorage.getItem('wallet_provider') as WalletProviderName;
        const savedChainId = localStorage.getItem('wallet_chain_id');

        if (savedWallet && savedProvider) {
          console.log(`Found saved wallet connection: ${savedWallet} (${savedProvider})`);

          // Only restore if the provider is available and user is on investment page
          if (isWalletProviderAvailable(savedProvider) && window.location.pathname.includes('/investment')) {
            const providerObj = getProviderObject(savedProvider);

            if (providerObj) {
              // For SafePal wallet, check if the account is still connected
              try {
                const accounts = await providerObj.request({ method: 'eth_accounts' });
                if (accounts && accounts.length > 0 && accounts[0].toLowerCase() === savedWallet.toLowerCase()) {
                  setWalletAddress(savedWallet);
                  setCurrentProvider(savedProvider);
                  if (savedChainId) setChainId(savedChainId);
                  console.log('SafePal wallet connection restored successfully');
                } else {
                  console.log('Saved wallet no longer connected, clearing storage');
                  localStorage.removeItem('wallet_address');
                  localStorage.removeItem('wallet_provider');
                  localStorage.removeItem('wallet_chain_id');
                }
              } catch (error) {
                console.log('Error checking saved wallet connection:', error);
                localStorage.removeItem('wallet_address');
                localStorage.removeItem('wallet_provider');
                localStorage.removeItem('wallet_chain_id');
              }
            }
          } else if (!isWalletProviderAvailable(savedProvider)) {
            console.log('Saved wallet provider no longer available');
            localStorage.removeItem('wallet_address');
            localStorage.removeItem('wallet_provider');
            localStorage.removeItem('wallet_chain_id');
          }
        }
      } catch (error) {
        console.error('Error loading saved wallet connection:', error);
      }
    };

    // Only auto-restore on investment pages to prevent dashboard popups
    if (window.location.pathname.includes('/investment')) {
      const timer = setTimeout(loadSavedConnection, 1000);
      return () => clearTimeout(timer);
    }
  }, []);

  // Detect SafePal wallet availability (but don't auto-connect)
  useEffect(() => {
    // Only check wallet availability on investment and dashboard pages
    const shouldCheckWallet = window.location.pathname.includes('/investment') ||
                             window.location.pathname.includes('/dashboard') ||
                             window.location.pathname.includes('/admin');

    if (!shouldCheckWallet) {
      // Silent skip - no console spam
      return;
    }

    // Only check wallet availability, don't auto-connect
    const checkSafePal = () => {
      const available = isWalletProviderAvailable("safepal");
      // Silent detection - no console spam
      // Don't auto-connect to prevent Trust Wallet popup
    };

    // Run detection after a slight delay to ensure providers are loaded
    const timer = setTimeout(checkSafePal, 500);

    return () => {
      clearTimeout(timer);
    };
  }, []);

  // Register Ethereum event handlers in a custom hook
  useWalletEventHandlers({
    walletAddress,
    setWalletAddress,
    setChainId,
    disconnectWallet: () => disconnectWalletHelper({ setWalletAddress, setChainId, setCurrentProvider, setConnectionError, toast }),
    toast,
  });

  const connectWallet = async (provider: WalletProviderName) => {
    // Verify provider is available before attempting connection
    if (!isWalletProviderAvailable(provider)) {
      const errorMsg = `${provider} wallet not detected. Please install the extension and refresh.`;
      setConnectionError(errorMsg);
      toast({
        title: "Wallet Not Found",
        description: errorMsg,
        variant: "destructive"
      });
      return;
    }

    try {
      return await connectWalletHelper({
        provider,
        setIsConnecting,
        setConnectionError,
        toast,
        setWalletAddress,
        setCurrentProvider,
        setChainId,
      });
    } catch (error: any) {
      // Handle any unhandled errors from the connection helper
      console.log('Wallet connection error caught in useWalletConnection:', error?.message || error);
      // Error is already handled by connectWalletHelper, so we don't need to do anything else
    }
  };

  const switchChain = (targetChainId: string) =>
    switchChainHelper({
      targetChainId,
      walletAddress,
      setChainId,
      toast,
    });

  const disconnectWallet = () =>
    disconnectWalletHelper({
      setWalletAddress,
      setChainId,
      setCurrentProvider,
      setConnectionError,
      toast,
    });

  return {
    walletAddress,
    isConnecting,
    connectWallet,
    connectionError,
    chainId,
    switchChain,
    disconnectWallet,
    currentProvider,
  };
};

// Make TypeScript aware of SafePal wallet provider objects
declare global {
  interface Window {
    ethereum?: any;
    safepal?: any;
    safepalProvider?: any;
  }
}
