import React from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { SUPPORTED_CHAINS, SupportedChain } from "@/pages/investment/utils/web3Transaction";
import { WalletProviderName } from "@/pages/investment/useWalletConnection";
import { Check, ExternalLink, Loader2 } from "lucide-react";

interface ChainSelectorProps {
  selectedChain: SupportedChain | null;
  onChainSelect: (chain: SupportedChain) => void;
  walletProvider: WalletProviderName;
  isLoading?: boolean;
  disabled?: boolean;
}

const ChainSelector: React.FC<ChainSelectorProps> = ({
  selectedChain,
  onChainSelect,
  walletProvider,
  isLoading = false,
  disabled = false
}) => {
  // Filter chains based on wallet provider
  const getAvailableChains = (): SupportedChain[] => {
    if (walletProvider === "tronlink") {
      return ["tron"];
    }
    return ["ethereum", "bsc", "polygon"];
  };

  const availableChains = getAvailableChains();

  const getChainIcon = (chainKey: SupportedChain): string => {
    const icons = {
      ethereum: "⟠",
      bsc: "🟡",
      polygon: "🟣",
      tron: "🔴"
    };
    return icons[chainKey];
  };

  const getChainColor = (chainKey: SupportedChain): string => {
    const colors = {
      ethereum: "bg-blue-500/10 border-blue-500/30 text-blue-400",
      bsc: "bg-yellow-500/10 border-yellow-500/30 text-yellow-400",
      polygon: "bg-purple-500/10 border-purple-500/30 text-purple-400",
      tron: "bg-red-500/10 border-red-500/30 text-red-400"
    };
    return colors[chainKey];
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-white font-semibold">Select Payment Chain</h3>
        {isLoading && (
          <div className="flex items-center gap-2 text-gold">
            <Loader2 className="h-4 w-4 animate-spin" />
            <span className="text-sm">Switching chain...</span>
          </div>
        )}
      </div>
      
      <div className="grid gap-3">
        {availableChains.map((chainKey) => {
          const chain = SUPPORTED_CHAINS[chainKey];
          const isSelected = selectedChain === chainKey;
          
          return (
            <Card
              key={chainKey}
              className={`cursor-pointer transition-all duration-200 ${
                isSelected
                  ? "bg-gold/10 border-gold/50"
                  : "bg-charcoal/50 border-gray-600/30 hover:border-gold/30"
              } ${disabled ? "opacity-50 cursor-not-allowed" : ""}`}
              onClick={() => !disabled && !isLoading && onChainSelect(chainKey)}
            >
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="text-2xl">{getChainIcon(chainKey)}</div>
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-white font-medium">{chain.name}</span>
                        {isSelected && (
                          <Check className="h-4 w-4 text-gold" />
                        )}
                      </div>
                      <div className="flex items-center gap-2 mt-1">
                        <Badge className={getChainColor(chainKey)}>
                          {chain.nativeCurrency.symbol}
                        </Badge>
                        <span className="text-xs text-gray-400">
                          Pay with USDT
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex items-center gap-2">
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-8 w-8 p-0 text-gray-400 hover:text-white"
                      onClick={(e) => {
                        e.stopPropagation();
                        window.open(chain.blockExplorerUrls[0], '_blank');
                      }}
                    >
                      <ExternalLink className="h-3 w-3" />
                    </Button>
                  </div>
                </div>
                
                {isSelected && (
                  <div className="mt-3 pt-3 border-t border-gold/20">
                    <div className="text-xs text-gray-300 space-y-1">
                      <div>Chain ID: {chain.chainId}</div>
                      <div>USDT Contract: {chain.usdtContract.slice(0, 10)}...{chain.usdtContract.slice(-8)}</div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          );
        })}
      </div>
      
      {walletProvider === "tronlink" && (
        <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
          <div className="flex items-start gap-2">
            <div className="text-blue-400 mt-0.5">ℹ️</div>
            <div className="text-sm text-blue-300">
              <strong>TronLink detected:</strong> You can only pay with USDT on the TRON network.
            </div>
          </div>
        </div>
      )}
      
      {availableChains.length === 0 && (
        <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4 text-center">
          <div className="text-red-400 mb-2">⚠️</div>
          <div className="text-red-300">
            No supported chains available for {walletProvider}
          </div>
        </div>
      )}
    </div>
  );
};

export default ChainSelector;
