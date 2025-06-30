
import { ConnectWalletHelperParams, SwitchChainHelperParams, DisconnectWalletHelperParams } from "./types";
import { getProviderObject } from "./walletProviders";
import { CHAINS } from "./chains";
import { logWalletConnection } from "./supabaseLogger";
import { setCurrentProviderState } from "./providerUtils";
import { connectWallet } from "./connectWallet";
import { disconnectWalletHelper } from "./disconnectWallet";

export { connectWallet as connectWalletHelper };
export { disconnectWalletHelper };

export const switchChainHelper = async ({
  targetChainId, 
  walletAddress,
  setChainId, 
  toast
}: SwitchChainHelperParams): Promise<boolean> => {
  if (!walletAddress) {
    toast({
      title: "Wallet Not Connected",
      description: "Please connect your wallet first",
      variant: "destructive"
    });
    return false;
  }

  try {
    const targetChain = Object.entries(CHAINS).find(([id]) => id === targetChainId)?.[1];
    if (!targetChain) {
      throw new Error(`Unknown chain ID: ${targetChainId}`);
    }

    // Get SafePal provider specifically
    const safepalProvider = window.safepal?.ethereum ||
      (window.ethereum && (window.ethereum.isSafePal || window.ethereum.isSafeWallet) ? window.ethereum : null);

    if (!safepalProvider?.request) {
      throw new Error("SafePal wallet not found or not connected");
    }

    // Request chain switch
    try {
      await safepalProvider.request({
        method: 'wallet_switchEthereumChain',
        params: [{ chainId: targetChain.chainId }],
      });
      
      setChainId(targetChain.chainId);
      
      toast({
        title: "Chain Switched",
        description: `Successfully switched to ${targetChain.name}`
      });
      
      return true;
    } catch (switchError: any) {
      // Chain doesn't exist, add it
      if (switchError.code === 4902 || switchError.code === -32603) {
        try {
          await safepalProvider.request({
            method: 'wallet_addEthereumChain',
            params: [
              {
                chainId: targetChain.chainId,
                chainName: targetChain.name,
                nativeCurrency: {
                  name: targetChain.symbol,
                  symbol: targetChain.symbol,
                  decimals: targetChain.decimals
                },
                rpcUrls: [targetChain.rpcUrl],
                blockExplorerUrls: [targetChain.blockExplorerUrl]
              }
            ],
          });
          
          setChainId(targetChain.chainId);
          
          toast({
            title: "Chain Added & Switched",
            description: `Successfully added and switched to ${targetChain.name}`
          });
          
          return true;
        } catch (addError) {
          console.error("Error adding chain:", addError);
          throw new Error(`Failed to add ${targetChain.name} to your wallet`);
        }
      } else {
        throw switchError;
      }
    }
  } catch (error: any) {
    console.error("Chain switch error:", error);
    
    toast({
      title: "Chain Switch Failed",
      description: error.message || "Failed to switch network. Please try manually.",
      variant: "destructive"
    });
    
    return false;
  }
};
