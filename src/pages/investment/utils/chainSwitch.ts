import { CHAINS } from "./chains";
import { SwitchChainHelperParams } from "./types";

export const switchChainHelper = async ({
  targetChainId,
  walletAddress,
  setChainId,
  toast,
}: SwitchChainHelperParams): Promise<boolean> => {
  if (!window.ethereum || !walletAddress) return false;
  
  try {
    await window.ethereum.request({
      method: 'wallet_switchEthereumChain',
      params: [{ chainId: targetChainId }],
    });
    setChainId(targetChainId);
    return true;
  } catch (error: any) {
    if (error.code === 4902) {
      try {
        const chainData = CHAINS[targetChainId as keyof typeof CHAINS];
        if (!chainData) throw new Error("Unsupported chain");
        
        await window.ethereum.request({
          method: 'wallet_addEthereumChain',
          params: [
            {
              chainId: targetChainId,
              chainName: chainData.name,
              nativeCurrency: {
                name: chainData.name,
                symbol: chainData.symbol,
                decimals: chainData.decimals,
              },
              rpcUrls: [chainData.rpcUrl],
              blockExplorerUrls: [chainData.blockExplorerUrl],
            },
          ],
        });
        
        await window.ethereum.request({
          method: 'wallet_switchEthereumChain',
          params: [{ chainId: targetChainId }],
        });
        
        setChainId(targetChainId);
        return true;
      } catch (addError) {
        toast({
          title: "Network Error",
          description: "Failed to add network to your wallet.",
          variant: "destructive"
        });
        return false;
      }
    }
    toast({
      title: "Network Error",
      description: "Failed to switch network. Please try manually in your wallet.",
      variant: "destructive"
    });
    return false;
  }
};
