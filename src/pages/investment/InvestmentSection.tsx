
import React from "react";
import { useWalletConnection, WalletProviderName } from "./useWalletConnection";
import { useInvestmentForm } from "./hooks/useInvestmentForm";
import { useChainSwitch } from "./hooks/useChainSwitch";
import InvestmentContent from "./components/InvestmentContent";
import { InvestmentPlan } from "./constants";

interface InvestmentSectionProps {
  selectedPlan: InvestmentPlan | null;
  setSelectedPlan: (plan: InvestmentPlan | null) => void;
  paymentStatus: 'idle' | 'pending' | 'success' | 'error';
  setPaymentStatus: (status: 'idle' | 'pending' | 'success' | 'error') => void;
  paymentTxHash: string | null;
  setPaymentTxHash: (hash: string | null) => void;
  isPaying: boolean;
  setIsPaying: (b: boolean) => void;
}

const InvestmentSection: React.FC<InvestmentSectionProps> = ({
  selectedPlan,
  setSelectedPlan,
  paymentStatus,
  setPaymentStatus,
  paymentTxHash,
  setPaymentTxHash,
  isPaying,
  setIsPaying,
}) => {
  const [selectedProvider, setSelectedProvider] = React.useState<WalletProviderName>("safepal");
  
  const {
    walletAddress,
    isConnecting,
    connectWallet,
    connectionError,
    chainId,
    switchChain,
  } = useWalletConnection();

  const { form, onSubmit } = useInvestmentForm(
    setPaymentStatus,
    setPaymentTxHash,
    setIsPaying,
    selectedPlan,
    setSelectedPlan,
    walletAddress
  );

  // Watch the selected chain for network switching
  const selectedChain = form.watch('chain');
  useChainSwitch(selectedChain, walletAddress, chainId, switchChain);

  // Function to connect to the wallet with the selected provider
  const handleConnectWallet = (provider: WalletProviderName) => {
    connectWallet(provider);
  };

  return (
    <div>
      <InvestmentContent
        selectedPlan={selectedPlan}
        setSelectedPlan={setSelectedPlan}
        paymentStatus={paymentStatus}
        paymentTxHash={paymentTxHash}
        setPaymentStatus={setPaymentStatus}
        walletAddress={walletAddress}
        isConnecting={isConnecting}
        connectionError={connectionError}
        handleConnectWallet={handleConnectWallet}
        selectedProvider={selectedProvider}
        setSelectedProvider={setSelectedProvider}
        form={form}
        isPaying={isPaying}
        onSubmit={form.handleSubmit(onSubmit)}
      />
    </div>
  );
};

export default InvestmentSection;
