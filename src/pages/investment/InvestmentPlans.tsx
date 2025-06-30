
import React, { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { InvestmentPlan, planConfig } from "./constants";
import DatabasePurchasablePlanCard from "@/components/investment/DatabasePurchasablePlanCard";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import WalletConnectionDialog from "@/components/investment/WalletConnectionDialog";
import { WalletProviderName } from "./useWalletConnection";
import { useInvestmentPackages, InvestmentPackage } from "@/hooks/useInvestmentPackages";
import { Loader2 } from "lucide-react";

interface InvestmentPlansProps {
  selectedPlan: InvestmentPlan | null;
  setSelectedPlan: (plan: InvestmentPlan) => void;
  form: any;
  walletAddress: string;
  isConnecting: boolean;
  isPaying: boolean;
  onSubmit: () => void;
  connectionError: string | null;
  connectWallet: (provider: WalletProviderName) => void;
}

const InvestmentPlans: React.FC<InvestmentPlansProps> = ({
  selectedPlan,
  setSelectedPlan,
  form,
  walletAddress,
  isConnecting,
  isPaying,
  onSubmit,
  connectionError,
  connectWallet,
}) => {
  const { packages, isLoading } = useInvestmentPackages();
  const [expandedPackage, setExpandedPackage] = useState<InvestmentPackage | null>(null);
  const [selected, setSelected] = useState<WalletProviderName>("safepal");
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const handlePurchase = (pkg: InvestmentPackage) => {
    if (expandedPackage?.id === pkg.id) {
      setExpandedPackage(null);
      setSelectedPlan(null);
      setIsDialogOpen(false);
    } else {
      setExpandedPackage(pkg);
      // Convert package name to plan key for backward compatibility
      const planKey = pkg.name.toLowerCase() as InvestmentPlan;
      setSelectedPlan(planKey);
      form.setValue("investmentPlan", planKey);
      form.setValue("investmentPackage", pkg);
      setIsDialogOpen(true);
    }
  };

  useEffect(() => {
    if (selectedPlan && packages.length > 0) {
      const matchingPackage = packages.find(pkg =>
        pkg.name.toLowerCase() === selectedPlan.toLowerCase()
      );
      if (matchingPackage) {
        setExpandedPackage(matchingPackage);
      }
    }
  }, [selectedPlan, packages]);

  useEffect(() => {
    if (!isDialogOpen) {
      setExpandedPackage(null);
    }
  }, [isDialogOpen]);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-gold" />
        <span className="ml-2 text-white">Loading investment plans...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {packages.map((pkg) => (
          <div
            key={pkg.id}
            className={expandedPackage && expandedPackage.id !== pkg.id ? "opacity-40 transition-opacity duration-300" : ""}
          >
            <DatabasePurchasablePlanCard
              package={pkg}
              isExpanded={expandedPackage?.id === pkg.id}
              onPurchase={() => handlePurchase(pkg)}
            />
          </div>
        ))}
      </div>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="sm:max-w-[425px] p-0 bg-transparent border-none shadow-none">
          <WalletConnectionDialog
            selected={selected}
            setSelected={setSelected}
            walletAddress={walletAddress}
            isConnecting={isConnecting}
            connectionError={connectionError}
            connectWallet={connectWallet}
            form={form}
            isPaying={isPaying}
            onSubmit={onSubmit}
            onClose={() => {
              setIsDialogOpen(false);
              setExpandedPlan(null);
            }}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default InvestmentPlans;
