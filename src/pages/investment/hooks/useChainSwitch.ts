
import { useEffect } from "react";
import { useToast } from "@/hooks/use-toast";

const chainIdMap: Record<string, string> = {
  ethereum: '0x1',
  bnb: '0x38',
  polygon: '0x89',
};

export const useChainSwitch = (
  selectedChain: string,
  walletAddress: string | null,
  chainId: string | null,
  switchChain: (chainId: string) => Promise<boolean>
) => {
  const { toast } = useToast();

  useEffect(() => {
    const checkAndSwitchChain = async () => {
      if (!walletAddress || !chainId) return;
      const requiredChainId = chainIdMap[selectedChain as keyof typeof chainIdMap];
      if (!requiredChainId) return;
      if (chainId.toLowerCase() !== requiredChainId.toLowerCase()) {
        toast({
          title: "Network Change Required",
          description: `This investment requires ${selectedChain} network. Switching...`,
        });
        await switchChain(requiredChainId);
      }
    };
    checkAndSwitchChain();
  }, [selectedChain, walletAddress, chainId, switchChain, toast]);
};
