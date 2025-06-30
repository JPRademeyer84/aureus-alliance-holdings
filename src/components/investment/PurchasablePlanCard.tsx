
import React from "react";
import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { Wallet } from "lucide-react";
import { InvestmentPlan, planConfig, investmentAmounts } from "@/pages/investment/constants";

interface PurchasablePlanCardProps {
  planKey: InvestmentPlan;
  isExpanded: boolean;
  onPurchase: () => void;
}

const PurchasablePlanCard: React.FC<PurchasablePlanCardProps> = ({
  planKey,
  isExpanded,
  onPurchase,
}) => {
  const plan = planConfig[planKey];
  
  return (
    <motion.div
      layout
      className={`relative rounded-xl border-2 p-4 
        ${isExpanded ? "border-gold bg-black" : "border-gold/30 bg-black/50"}
        transition-colors duration-200 hover:border-gold/60`}
    >
      <motion.div layout className="flex flex-col h-full">
        <div className="mb-1 text-center">
          <h3 className="text-xl font-playfair font-bold text-white text-shadow">
            {plan.name}
          </h3>
        </div>
        
        <p className="text-2xl font-bold text-center text-gradient mb-2">
          ${investmentAmounts[planKey].toLocaleString()} USDT
        </p>
        
        <div className="space-y-1 text-sm mb-2">
          <p>
            <span className="text-gold font-medium">{plan.shares.toLocaleString()}</span> Digital Shares
          </p>
          <p>
            <span className="text-green-400 font-medium">20%</span> Direct Commission
          </p>
          <p>
            <span className="text-purple-400 font-medium">10%</span> Charity Contribution
          </p>
          <p>
            <span className="text-yellow-400 font-medium">Competition</span> Entry
          </p>
        </div>

        <div className="flex-grow mt-2 border-t border-gold/20 pt-2">
          <p className="text-sm font-bold text-gold mb-1">Package Bonuses:</p>
          <ul className="list-disc pl-4 space-y-0.5 text-xs text-white">
            {plan.bonuses.map((bonus, index) => (
              <li key={index}>{bonus}</li>
            ))}
          </ul>
        </div>

        <Button
          onClick={onPurchase}
          size="sm"
          className={`w-full mt-3 ${
            isExpanded ? "bg-purple-600 hover:bg-purple-700" : "bg-gold-gradient text-black"
          } font-semibold hover:opacity-90 transition-all duration-200`}
        >
          {isExpanded ? "Close" : (
            <>Purchase <Wallet className="ml-1 h-4 w-4" /></>
          )}
        </Button>
      </motion.div>
    </motion.div>
  );
};

export default PurchasablePlanCard;
