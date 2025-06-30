
import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Wallet, CreditCard, Shield, AlertCircle, CheckCircle2, Loader2 } from "lucide-react";
import { WalletProviderName } from "./useWalletConnection";
import { cn } from "@/lib/utils";
import { isWalletProviderAvailable, getProviderObject } from "./utils/walletProviders";

interface WalletSelectorProps {
  selected: WalletProviderName;
  setSelected: (wallet: WalletProviderName) => void;
  connecting: boolean;
  onConnect: () => void;
}

const wallets = [
  {
    id: "safepal" as WalletProviderName,
    name: "SafePal",
    icon: <Shield className="h-5 w-5" />,
    description: "Supports all chains (Ethereum, BSC, Polygon, TRON)",
  },
];

export const WalletSelector: React.FC<WalletSelectorProps> = ({
  selected,
  setSelected,
  connecting,
  onConnect,
}) => {
  const [availableWallets, setAvailableWallets] = useState<Record<WalletProviderName, boolean>>({
    safepal: false
  });
  
  const [isChecking, setIsChecking] = useState<boolean>(true);

  // Check wallet availability on component mount and window focus
  useEffect(() => {
    const checkWalletAvailability = () => {
      setIsChecking(true);
      const walletStatus: Record<WalletProviderName, boolean> = {
        safepal: false
      };

      wallets.forEach(wallet => {
        try {
          const available = isWalletProviderAvailable(wallet.id);
          walletStatus[wallet.id] = available;

          // Log wallet detection details for debugging
          console.log(`${wallet.name} detection:`, {
            available,
            providerExists: !!getProviderObject(wallet.id),
            provider: getProviderObject(wallet.id)
          });
        } catch (error) {
          console.log(`Error detecting ${wallet.name}:`, error);
          walletStatus[wallet.id] = false;
        }
      });

      setAvailableWallets(walletStatus);
      setIsChecking(false);
    };

    // Check on mount with a slight delay to ensure providers are loaded
    const timer = setTimeout(() => checkWalletAvailability(), 500);

    // Check on window focus (in case wallet extension was installed/activated)
    window.addEventListener('focus', checkWalletAvailability);

    return () => {
      clearTimeout(timer);
      window.removeEventListener('focus', checkWalletAvailability);
    };
  }, []);

  return (
    <div className="grid gap-3">
      <h3 className="text-lg font-semibold mb-1">Connect SafePal Wallet</h3>

      <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3 mb-2">
        <p className="text-blue-400 text-sm">
          <Shield className="h-4 w-4 inline mr-1" />
          SafePal is the only supported wallet. It works with all chains: Ethereum, BSC, Polygon & TRON.
        </p>
      </div>
      
      {isChecking ? (
        <div className="flex items-center justify-center p-4 bg-black/40 rounded-md border border-white/10">
          <Loader2 className="h-5 w-5 text-gold animate-spin mr-2" />
          <span className="text-white/80">Detecting wallets...</span>
        </div>
      ) : (
        wallets.map((wallet) => {
          const isAvailable = availableWallets[wallet.id];
          return (
            <Button
              key={wallet.id}
              onClick={() => {
                setSelected(wallet.id);
                // Don't auto-connect, let user click the connect button below
              }}
              disabled={connecting || (!isAvailable && !isChecking)}
              className={cn(
                "w-full flex items-center gap-3 p-4 h-auto transition-all duration-200",
                selected === wallet.id
                  ? "bg-gold/20 hover:bg-gold/30 border border-gold text-white"
                  : "bg-black/40 hover:bg-black/60 border border-white/10 text-white/80 hover:text-white",
                !isAvailable && !isChecking && "opacity-50 cursor-not-allowed hover:bg-black/40"
              )}
            >
              <div className={cn(
                "p-2 rounded-full",
                selected === wallet.id ? "bg-gold/20" : "bg-black/40"
              )}>
                {wallet.icon}
              </div>
              <div className="flex-1 text-left">
                <div className="font-medium flex items-center gap-2">
                  {wallet.name}
                  {isAvailable ? (
                    <span className="text-xs bg-green-500/20 text-green-300 px-2 py-0.5 rounded flex items-center gap-1">
                      <CheckCircle2 className="h-3 w-3" /> Detected
                    </span>
                  ) : (
                    <span className="text-xs bg-red-500/20 text-red-300 px-2 py-0.5 rounded flex items-center gap-1">
                      <AlertCircle className="h-3 w-3" /> Not Detected
                    </span>
                  )}
                </div>
                <div className="text-xs opacity-70">
                  {!isAvailable 
                    ? "Wallet extension not found" 
                    : wallet.description}
                </div>
              </div>
            </Button>
          );
        })
      )}
    </div>
  );
};
