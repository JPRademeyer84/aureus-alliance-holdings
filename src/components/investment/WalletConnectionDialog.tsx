
import React from "react";
import { DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { WalletSelector } from "@/pages/investment/WalletSelector";
import WalletConnector from "@/pages/investment/WalletConnector";
import { InvestmentForm } from "@/pages/investment/InvestmentForm";
import WalletConnectionGuide from "@/components/investment/WalletConnectionGuide";
import WalletErrorHandler from "@/components/wallet/WalletErrorHandler";
import { X } from "lucide-react";
import { WalletProviderName } from "@/pages/investment/useWalletConnection";

interface WalletConnectionDialogProps {
  selected: WalletProviderName;
  setSelected: (wallet: WalletProviderName) => void;
  walletAddress: string;
  isConnecting: boolean;
  connectionError: string | null;
  connectWallet: (provider: WalletProviderName) => void;
  form: any;
  isPaying: boolean;
  onSubmit: () => void;
  onClose: () => void;
}

const WalletConnectionDialog: React.FC<WalletConnectionDialogProps> = ({
  selected,
  setSelected,
  walletAddress,
  isConnecting,
  connectionError,
  connectWallet,
  form,
  isPaying,
  onSubmit,
  onClose,
}) => {
  return (
    <div className="relative w-full max-w-[420px] mx-auto bg-black/95 border border-gold/30 rounded-lg overflow-hidden">
      <button
        onClick={onClose}
        className="absolute right-3 top-3 rounded-full p-1.5 bg-black/50 text-gold hover:bg-black/70 transition-colors"
      >
        <X className="h-4 w-4" />
        <span className="sr-only">Close</span>
      </button>

      <DialogHeader className="p-6 pb-0">
        <DialogTitle className="text-2xl font-playfair text-center text-gold">
          Connect Your Wallet
        </DialogTitle>
        <DialogDescription className="text-white/70 text-center mt-2">
          Choose your preferred wallet to continue
        </DialogDescription>
      </DialogHeader>

      <div className="p-4 sm:p-6">
        {/* Show improved error handler for connection errors */}
        {connectionError && (
          <div className="mb-6">
            <WalletErrorHandler
              error={(() => {
                // Try to parse structured error, fallback to simple error
                try {
                  const parsed = JSON.parse(connectionError);
                  return parsed;
                } catch {
                  // Create error object from string
                  let code = undefined;
                  if (connectionError.includes("cancelled") || connectionError.includes("rejected")) {
                    code = 4001;
                  } else if (connectionError.includes("pending")) {
                    code = -32002;
                  } else if (connectionError.includes("locked")) {
                    code = -32603;
                  }
                  return { message: connectionError, code };
                }
              })()}
              onRetry={() => connectWallet(selected)}
              isRetrying={isConnecting}
            />
          </div>
        )}

        {/* Show guide if no error but no wallet connected */}
        {!connectionError && !walletAddress && (
          <WalletConnectionGuide isVisible={true} />
        )}

        <WalletSelector
          selected={selected}
          setSelected={setSelected}
          connecting={isConnecting}
          onConnect={() => connectWallet(selected)}
        />

        <div className="mt-6">
          <WalletConnector
            walletAddress={walletAddress}
            isConnecting={isConnecting}
            connectionError={connectionError}
            connectWallet={() => connectWallet(selected)}
            selectedProvider={selected}
          />
        </div>

        {walletAddress && (
          <div className="mt-6 border-t border-gold/20 pt-6">
            <InvestmentForm
              selectedPlan={form.watch("investmentPlan")}
              setSelectedPlan={plan => form.setValue("investmentPlan", plan)}
              walletAddress={walletAddress}
              isPaying={isPaying}
              onSubmit={onSubmit}
              form={form}
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default WalletConnectionDialog;
