import React, { useState, useEffect } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { RefreshCw, AlertTriangle, CheckCircle, Wallet } from "lucide-react";
import { getUSDTBalance, BalanceInfo, SupportedChain } from "@/pages/investment/utils/web3Transaction";
import { WalletProviderName } from "@/pages/investment/useWalletConnection";

interface BalanceCheckerProps {
  walletAddress: string;
  walletProvider: WalletProviderName;
  selectedChain: SupportedChain;
  requiredAmount: number;
  onBalanceCheck: (hasEnoughBalance: boolean, balance: BalanceInfo) => void;
}

const BalanceChecker: React.FC<BalanceCheckerProps> = ({
  walletAddress,
  walletProvider,
  selectedChain,
  requiredAmount,
  onBalanceCheck
}) => {
  const [balance, setBalance] = useState<BalanceInfo | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const checkBalance = async () => {
    if (!walletAddress || !selectedChain) return;

    setIsLoading(true);
    setError(null);

    try {
      const balanceInfo = await getUSDTBalance(walletProvider, walletAddress, selectedChain);
      setBalance(balanceInfo);
      
      const hasEnoughBalance = parseFloat(balanceInfo.formatted) >= (typeof requiredAmount === 'number' ? requiredAmount : 0);
      onBalanceCheck(hasEnoughBalance, balanceInfo);
    } catch (err: any) {
      const errorMessage = err.message || "Failed to check balance";
      setError(errorMessage);
      console.error("Balance check failed:", err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    checkBalance();
  }, [walletAddress, selectedChain, requiredAmount]);

  const safeRequiredAmount = typeof requiredAmount === 'number' ? requiredAmount : 0;
  const hasEnoughBalance = balance ? parseFloat(balance.formatted) >= safeRequiredAmount : false;
  const shortfall = balance ? Math.max(0, safeRequiredAmount - parseFloat(balance.formatted)) : 0;

  return (
    <Card className="bg-charcoal/50 border-gray-600/30">
      <CardContent className="p-4">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            <Wallet className="h-4 w-4 text-gold" />
            <span className="text-white font-medium">USDT Balance</span>
          </div>
          <Button
            variant="ghost"
            size="sm"
            onClick={checkBalance}
            disabled={isLoading}
            className="h-8 w-8 p-0 text-gray-400 hover:text-white"
          >
            <RefreshCw className={`h-3 w-3 ${isLoading ? 'animate-spin' : ''}`} />
          </Button>
        </div>

        {error ? (
          <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
            <div className="flex items-start gap-2">
              <AlertTriangle className="h-4 w-4 text-red-400 mt-0.5 flex-shrink-0" />
              <div className="text-sm text-red-300">
                <div className="font-medium mb-1">Balance Check Failed</div>
                <div className="text-xs opacity-80">{error}</div>
                {balance?.error && (
                  <div className="text-xs opacity-60 mt-1">
                    Debug: {balance.error}
                  </div>
                )}
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={checkBalance}
                  className="mt-2 h-6 px-2 text-xs text-red-300 hover:text-red-200"
                >
                  Try Again
                </Button>
              </div>
            </div>
          </div>
        ) : isLoading ? (
          <div className="flex items-center justify-center py-4">
            <div className="flex items-center gap-2 text-gray-400">
              <RefreshCw className="h-4 w-4 animate-spin" />
              <span className="text-sm">Checking balance...</span>
            </div>
          </div>
        ) : balance ? (
          <div className="space-y-3">
            {/* Balance Display */}
            <div className="flex items-center justify-between">
              <span className="text-gray-300">Available:</span>
              <div className="flex items-center gap-2">
                <span className="text-white font-mono text-lg">
                  {balance.formatted}
                </span>
                <Badge className="bg-blue-500/10 border-blue-500/30 text-blue-400">
                  USDT
                </Badge>
              </div>
            </div>

            {/* Required Amount */}
            <div className="flex items-center justify-between">
              <span className="text-gray-300">Required:</span>
              <div className="flex items-center gap-2">
                <span className="text-white font-mono text-lg">
                  {safeRequiredAmount.toFixed(2)}
                </span>
                <Badge className="bg-gold/10 border-gold/30 text-gold">
                  USDT
                </Badge>
              </div>
            </div>

            {/* Balance Status */}
            <div className="pt-2 border-t border-gray-600/30">
              {hasEnoughBalance ? (
                <div className="flex items-center gap-2 text-green-400">
                  <CheckCircle className="h-4 w-4" />
                  <span className="text-sm font-medium">Sufficient balance</span>
                </div>
              ) : (
                <div className="space-y-2">
                  <div className="flex items-center gap-2 text-red-400">
                    <AlertTriangle className="h-4 w-4" />
                    <span className="text-sm font-medium">Insufficient balance</span>
                  </div>
                  <div className="text-xs text-red-300">
                    You need {shortfall.toFixed(2)} more USDT to complete this purchase
                  </div>
                </div>
              )}
            </div>

            {/* Wallet Info */}
            <div className="pt-2 border-t border-gray-600/30">
              <div className="text-xs text-gray-400 space-y-1">
                <div>
                  Wallet: {walletAddress.slice(0, 6)}...{walletAddress.slice(-4)}
                </div>
                <div>
                  Chain: {selectedChain.toUpperCase()}
                </div>
              </div>
            </div>
          </div>
        ) : (
          <div className="text-center py-4 text-gray-400">
            <div className="text-sm">Connect wallet to check balance</div>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default BalanceChecker;
