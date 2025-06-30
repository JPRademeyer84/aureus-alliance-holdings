
import React from 'react';
import InvestmentPlans from "../InvestmentPlans";
import PaymentMethodsInfo from "../PaymentMethodsInfo";
import InvestmentStatus from "../InvestmentStatus";
import InvestmentActionPanel from "../InvestmentActionPanel";
import { WalletProviderName } from "../useWalletConnection";
import { InvestmentPlan } from "../constants";
import { z } from "zod";
import { formSchema } from "../InvestmentForm";
import { useForm } from "react-hook-form";

interface InvestmentContentProps {
  selectedPlan: InvestmentPlan | null;
  setSelectedPlan: (plan: InvestmentPlan | null) => void;
  paymentStatus: 'idle' | 'pending' | 'success' | 'error';
  paymentTxHash: string | null;
  setPaymentStatus: (status: 'idle' | 'pending' | 'success' | 'error') => void;
  walletAddress: string;
  isConnecting: boolean;
  connectionError: string | null;
  handleConnectWallet: (provider: WalletProviderName) => void;
  selectedProvider: WalletProviderName;
  setSelectedProvider: (provider: WalletProviderName) => void;
  form: ReturnType<typeof useForm<z.infer<typeof formSchema>>>;
  isPaying: boolean;
  onSubmit: () => void;
}

const InvestmentContent: React.FC<InvestmentContentProps> = ({
  selectedPlan,
  setSelectedPlan,
  paymentStatus,
  paymentTxHash,
  setPaymentStatus,
  walletAddress,
  isConnecting,
  connectionError,
  handleConnectWallet,
  selectedProvider,
  setSelectedProvider,
  form,
  isPaying,
  onSubmit
}) => {
  return (
    <div className="max-w-xl mx-auto">
      <div className="bg-black/40 border golden-border rounded-lg p-8">
        <h2 className="text-2xl font-bold font-playfair mb-6 text-center text-white">
          Complete Your Participation
        </h2>
        <InvestmentStatus
          paymentStatus={paymentStatus}
          paymentTxHash={paymentTxHash}
          setPaymentStatus={setPaymentStatus}
        />
        {paymentStatus === 'idle' && (
          <InvestmentActionPanel
            selectedPlan={selectedPlan}
            setSelectedPlan={setSelectedPlan}
            walletAddress={walletAddress}
            isConnecting={isConnecting}
            connectionError={connectionError}
            connectWallet={handleConnectWallet}
            selectedProvider={selectedProvider}
            setSelectedProvider={setSelectedProvider}
            form={form}
            isPaying={isPaying}
            onSubmit={onSubmit}
          />
        )}
      </div>
      <PaymentMethodsInfo />
    </div>
  );
};

export default InvestmentContent;
