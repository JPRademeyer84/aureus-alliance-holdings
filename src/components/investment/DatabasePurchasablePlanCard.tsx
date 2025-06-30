import React from "react";
import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import {
  Wallet,
  Pickaxe,
  HardHat,
  Truck,
  Construction,
  Settings,
  Factory,
  Crown,
  Star
} from "lucide-react";
import { ParticipationPackage } from "@/hooks/useInvestmentPackages";

// Icon mapping for mining packages
const iconMap = {
  shovel: Pickaxe,
  pickaxe: Pickaxe,
  'hard-hat': HardHat,
  hardhat: HardHat,
  truck: Truck,
  construction: Construction,
  settings: Settings,
  factory: Factory,
  crown: Crown,
  star: Star
} as const;

interface DatabasePurchasablePlanCardProps {
  package: ParticipationPackage;
  isExpanded: boolean;
  onPurchase: () => void;
}

const DatabasePurchasablePlanCard: React.FC<DatabasePurchasablePlanCardProps> = ({
  package: pkg,
  isExpanded,
  onPurchase,
}) => {
  // Get the appropriate icon for the package
  const IconComponent = pkg.icon && iconMap[pkg.icon as keyof typeof iconMap]
    ? iconMap[pkg.icon as keyof typeof iconMap]
    : Star;

  // Get icon background color class, default to gold if not specified
  const iconBgClass = pkg.icon_color || 'bg-gold';

  return (
    <motion.div
      layout
      className={`relative rounded-xl border-2 p-4 
        ${isExpanded ? "border-gold bg-black" : "border-gold/30 bg-black/50"}
        transition-colors duration-200 hover:border-gold/60`}
    >
      <motion.div layout className="flex flex-col h-full">
        <div className="mb-3 text-center">
          {/* Package Icon */}
          <div className="flex justify-center mb-2">
            <div className={`p-3 rounded-full ${iconBgClass}`}>
              <IconComponent className="h-8 w-8 text-white" />
            </div>
          </div>
          <h3 className="text-xl font-playfair font-bold text-white text-shadow">
            {pkg.name}
          </h3>
        </div>
        
        <p className="text-2xl font-bold text-center text-gradient mb-2">
          ${pkg.price.toLocaleString()} USDT
        </p>
        
        <div className="space-y-1 text-sm mb-2">
          <p>
            <span className="text-gold font-medium">{pkg.shares.toLocaleString()}</span> Digital Shares
          </p>
          <p>
            <span className="text-green-400 font-medium">{pkg.commission_percentage || 20}%</span> Direct Commission
          </p>
          <p>
            <span className="text-purple-400 font-medium">{pkg.npo_allocation || 10}%</span> Charity Contribution
          </p>
          <p>
            <span className="text-yellow-400 font-medium">Phase {pkg.phase_id || 1}</span> Competition Entry
          </p>
        </div>

        <div className="flex-grow mt-2 border-t border-gold/20 pt-2">
          <p className="text-sm font-bold text-gold mb-1">Package Bonuses:</p>
          <ul className="list-disc pl-4 space-y-0.5 text-xs text-white">
            {pkg.bonuses && pkg.bonuses.map((bonus, index) => (
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

export default DatabasePurchasablePlanCard;
