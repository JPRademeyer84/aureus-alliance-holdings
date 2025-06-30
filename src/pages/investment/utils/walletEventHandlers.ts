
import { toast } from "@/hooks/use-toast";

interface WalletEventHandlersParams {
  walletAddress: string;
  setWalletAddress: (address: string) => void;
  setChainId: (chainId: string | null) => void;
  disconnectWallet: () => void;
}

export function registerWalletEventHandlers({
  walletAddress,
  setWalletAddress,
  setChainId,
  disconnectWallet
}: WalletEventHandlersParams) {
  // Don't register wallet event handlers on homepage
  if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
    return () => {}; // Return empty cleanup function
  }

  // Only register events for SafePal wallet
  const safepalProvider = window.safepal?.ethereum ||
    (window.ethereum && (window.ethereum.isSafePal || window.ethereum.isSafeWallet) ? window.ethereum : null);

  if (!safepalProvider) return;
  
  const handleAccountsChanged = (accounts: string[]) => {
    if (accounts.length === 0) {
      // User disconnected their wallet
      if (walletAddress) {
        disconnectWallet();
      }
    } else if (accounts[0] !== walletAddress) {
      // User switched account
      setWalletAddress(accounts[0]);
      console.log(`Wallet account changed to ${accounts[0]}`);
      
      toast({
        title: "Wallet Account Changed",
        description: `Connected to account ${accounts[0].slice(0, 6)}...${accounts[0].slice(-4)}`,
      });
    }
  };
  
  const handleChainChanged = (chainIdHex: string) => {
    // Convert hex to decimal string if needed
    const chainId = chainIdHex.startsWith('0x') 
      ? chainIdHex 
      : `0x${parseInt(chainIdHex).toString(16)}`;
      
    setChainId(chainId);
    console.log(`Chain changed to ${chainId}`);
    
    toast({
      title: "Network Changed",
      description: `Wallet network changed to chain ID: ${chainId}`,
    });
  };
  
  const handleDisconnect = (error: { code: number; message: string }) => {
    console.log("Wallet disconnected:", error);
    disconnectWallet();
  };
  
  // Register SafePal event handlers
  safepalProvider.on('accountsChanged', handleAccountsChanged);
  safepalProvider.on('chainChanged', handleChainChanged);
  safepalProvider.on('disconnect', handleDisconnect);

  // Return a function to remove event handlers
  return () => {
    safepalProvider?.removeListener('accountsChanged', handleAccountsChanged);
    safepalProvider?.removeListener('chainChanged', handleChainChanged);
    safepalProvider?.removeListener('disconnect', handleDisconnect);
  };
}

export function removeWalletEventHandlers() {
  // Don't access wallet providers on homepage
  if (window.location.pathname === '/' || window.location.pathname === '/index.html') {
    return;
  }

  // Only remove SafePal event handlers
  const safepalProvider = window.safepal?.ethereum ||
    (window.ethereum && (window.ethereum.isSafePal || window.ethereum.isSafeWallet) ? window.ethereum : null);

  if (!safepalProvider) return;

  safepalProvider.removeAllListeners('accountsChanged');
  safepalProvider.removeAllListeners('chainChanged');
  safepalProvider.removeAllListeners('disconnect');
}
