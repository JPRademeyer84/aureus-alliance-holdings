
import { useEffect } from "react";
import { CHAINS } from "./chains";
import { registerWalletEventHandlers, removeWalletEventHandlers } from "./walletEventHandlers";

interface UseWalletEventHandlersProps {
  walletAddress: string;
  setWalletAddress: (addr: string) => void;
  setChainId: (cid: string) => void;
  disconnectWallet: () => void;
  toast: any;
}

// Handles chain and accounts change events from window.ethereum
export function useWalletEventHandlers({
  walletAddress,
  setWalletAddress,
  setChainId,
  disconnectWallet,
  toast,
}: UseWalletEventHandlersProps) {
  useEffect(() => {
    // Don't register wallet event handlers on homepage
    if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
      return;
    }

    // Only use SafePal provider to prevent Trust Wallet interference
    const safepalProvider = window.safepal?.ethereum ||
      (window.ethereum && (window.ethereum.isSafePal || window.ethereum.isSafeWallet) ? window.ethereum : null);

    if (!safepalProvider) return;

    const handleChainChanged = (newChainId: string) => {
      setChainId(newChainId);
      const chainName = CHAINS[newChainId as keyof typeof CHAINS]?.name || "Unknown Network";
      toast({
        title: "Network Changed",
        description: `You are now connected to ${chainName}`,
      });
    };

    const handleAccountsChanged = (accounts: string[]) => {
      if (accounts.length === 0) {
        disconnectWallet();
      } else {
        setWalletAddress(accounts[0]);
      }
    };

    // Pass wallet event handlers parameters directly
    const cleanup = registerWalletEventHandlers({
      walletAddress,
      setWalletAddress,
      setChainId,
      disconnectWallet
    });

    if (walletAddress && safepalProvider) {
      safepalProvider.request({ method: "eth_chainId" })
        .then((result: string) => setChainId(result))
        .catch(console.error);
    }

    return () => {
      // Call removeWalletEventHandlers without arguments
      removeWalletEventHandlers();
    };
  }, [walletAddress, toast, setChainId, setWalletAddress, disconnectWallet]);
}
