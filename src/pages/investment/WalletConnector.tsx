
import React from "react";
import { Button } from "@/components/ui/button";
import { AlertTriangle, Loader2, CheckCircle2, Wallet, ExternalLink } from "lucide-react";
import { getWalletProviderDisplayName } from "./utils/walletProviders";
import { WalletProviderName } from "./useWalletConnection";

interface WalletConnectorProps {
  walletAddress: string;
  isConnecting: boolean;
  connectionError: string | null;
  connectWallet: () => void;
  selectedProvider: WalletProviderName;
}

const WalletConnector: React.FC<WalletConnectorProps> = ({
  walletAddress,
  isConnecting,
  connectionError,
  connectWallet,
  selectedProvider,
}) => {
  const walletName = getWalletProviderDisplayName(selectedProvider);
  
  const getWalletInstallLink = (provider: WalletProviderName): string => {
    switch (provider) {
      case "safepal":
        return "https://www.safepal.com/download";
      default:
        return "https://www.safepal.com/download";
    }
  };
  
  return (
    <div className="mb-8">
      <h3 className="text-lg font-semibold mb-4">1. Connect Your Wallet</h3>
      {walletAddress ? (
        <div className="p-4 bg-green-500/20 rounded-md border border-green-500/30 mb-4">
          <div className="flex items-center gap-2 justify-center">
            <CheckCircle2 className="text-green-500" size={18} />
            <p className="font-medium text-green-200">
              Connected: {walletAddress.substring(0, 6)}...{walletAddress.substring(walletAddress.length - 4)}
            </p>
          </div>
        </div>
      ) : connectionError ? (
        <div className="p-4 bg-red-500/20 rounded-md border border-red-500/30 mb-4">
          <div className="flex items-center gap-2 justify-center mb-2">
            <AlertTriangle className="text-red-500" size={18} />
            <p className="font-semibold text-red-400">
              {connectionError.includes("cancelled") || connectionError.includes("rejected") ? "Connection Cancelled" : "Connection Error"}
            </p>
          </div>
          <p className="text-center text-sm text-red-200 mb-3">{connectionError}</p>

          {connectionError.includes("cancelled") || connectionError.includes("rejected") ? (
            <div className="bg-yellow-500/10 border border-yellow-500/30 rounded p-3 mb-3">
              <p className="text-yellow-200 text-sm text-center">
                💡 <strong>Tip:</strong> When you click "Connect Wallet", approve the connection request in your wallet popup to continue with your investment.
              </p>
            </div>
          ) : null}

          <div className="flex flex-col sm:flex-row gap-2 mt-3">
            <Button
              onClick={connectWallet}
              className="flex-1 bg-gold-gradient text-black font-semibold hover:opacity-90"
              disabled={isConnecting}
            >
              {connectionError.includes("cancelled") || connectionError.includes("rejected") ? "Try Connecting Again" : `Retry with ${walletName}`}
            </Button>
            <Button
              onClick={() => window.open(getWalletInstallLink(selectedProvider), '_blank')}
              className="flex-1 bg-transparent hover:bg-white/10 text-white border border-white/30"
              type="button"
            >
              <ExternalLink size={16} className="mr-1" />
              Install {walletName}
            </Button>
          </div>
        </div>
      ) : (
        <Button
          onClick={connectWallet}
          className="w-full bg-gold-gradient text-black font-semibold"
          disabled={isConnecting}
        >
          {isConnecting ? (
            <span className="flex items-center gap-2">
              <Loader2 className="animate-spin" size={18} /> 
              Connecting to {walletName}...
            </span>
          ) : (
            <span className="flex items-center gap-2">
              <Wallet size={18} />
              Connect to {walletName}
            </span>
          )}
        </Button>
      )}
    </div>
  );
};

export default WalletConnector;
