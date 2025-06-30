
import React from "react";
import { WalletSelector } from "./WalletSelector";
import WalletConnector from "./WalletConnector";
import { InvestmentForm, formSchema } from "./InvestmentForm";
import { InvestmentPlan } from "./constants";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { WalletProviderName } from "./useWalletConnection";

interface InvestmentActionPanelProps {
  selectedPlan: InvestmentPlan | null;
  setSelectedPlan: (plan: InvestmentPlan | null) => void;
  walletAddress: string;
  isConnecting: boolean;
  connectionError: string | null;
  connectWallet: (provider: WalletProviderName) => void;
  selectedProvider: WalletProviderName;
  setSelectedProvider: (provider: WalletProviderName) => void;
  form: ReturnType<typeof useForm<z.infer<typeof formSchema>>>;
  isPaying: boolean;
  onSubmit: () => void;
}

const InvestmentActionPanel: React.FC<InvestmentActionPanelProps> = ({
  selectedPlan,
  setSelectedPlan,
  walletAddress,
  isConnecting,
  connectionError,
  connectWallet,
  selectedProvider,
  setSelectedProvider,
  form,
  isPaying,
  onSubmit,
}) => (
  <>
    <WalletSelector
      selected={selectedProvider}
      setSelected={setSelectedProvider}
      connecting={isConnecting}
      onConnect={() => connectWallet(selectedProvider)}
    />
    <WalletConnector
      walletAddress={walletAddress}
      isConnecting={isConnecting}
      connectionError={connectionError}
      connectWallet={() => connectWallet(selectedProvider)}
      selectedProvider={selectedProvider}
    />
    <InvestmentForm
      selectedPlan={selectedPlan}
      setSelectedPlan={setSelectedPlan}
      walletAddress={walletAddress}
      isPaying={isPaying}
      onSubmit={onSubmit}
      form={form}
    />
  </>
);

export default InvestmentActionPanel;
