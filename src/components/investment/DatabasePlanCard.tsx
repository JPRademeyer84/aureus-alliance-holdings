import React from "react";
import { useNavigate } from "react-router-dom";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ParticipationPackage } from "@/hooks/useInvestmentPackages";
import { useUser } from "@/contexts/UserContext";
import { ST as T } from '@/components/SimpleTranslator';
import {
  Pickaxe,
  HardHat,
  Truck,
  Construction,
  Settings,
  Factory,
  Crown,
  Star
} from "lucide-react";

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

// Bonus text to translation key mapping
const bonusTranslationMap: Record<string, string> = {
  'NFT Share Certificate': 'bonus.nft_share_certificate',
  'Mining Progress Reports': 'bonus.mining_progress_reports',
  'Priority Support': 'bonus.priority_support',
  'Quarterly Dividend Bonus': 'bonus.quarterly_dividend_bonus',
  'VIP Mining Updates': 'bonus.vip_mining_updates',
  'Exclusive Investor Events': 'bonus.exclusive_investor_events',
  'Gold Diggers Club Access': 'bonus.gold_diggers_club_access'
};

// Function to get translation key for bonus text
const getBonusTranslationKey = (bonusText: string): string => {
  return bonusTranslationMap[bonusText] || bonusText;
};

interface DatabasePlanCardProps {
  package: ParticipationPackage;
  isPopular?: boolean;
  isPremium?: boolean;
  showParticipateButton?: boolean;
}

const DatabasePlanCard: React.FC<DatabasePlanCardProps> = ({
  package: pkg,
  isPopular = false,
  isPremium = false,
  showParticipateButton = true
}) => {
  const navigate = useNavigate();
  const { isAuthenticated } = useUser();
  const contrastList = "text-white/90";
  const textBase = "text-sm";
  const rewardDeadline = "1 January 2026";

  // Get the appropriate icon for the package
  const IconComponent = pkg.icon && iconMap[pkg.icon as keyof typeof iconMap]
    ? iconMap[pkg.icon as keyof typeof iconMap]
    : Star;

  // Get icon background color class, default to gold if not specified
  const iconBgClass = pkg.icon_color || 'bg-gold';

  const handleParticipateClick = () => {
    if (isAuthenticated) {
      // User is logged in, go to dashboard with package selected
      navigate(`/dashboard?package=${pkg.id}`);
    } else {
      // User not logged in, go to auth with package info
      navigate(`/auth?package=${pkg.id}&returnTo=/dashboard`);
    }
  };

  return (
    <Card
      className={`
        bg-[#23243a]
        rounded-xl shadow-lg
        relative
        hover:scale-[1.02] transition-all
        ${isPopular
          ? "border-2 border-gold golden-border"
          : isPremium
            ? "border-2 border-purple-500/60"
            : "border-2 border-gold/30"
        }
      `}
    >
      {isPopular && (
        <div className="absolute -top-3 left-0 right-0 mx-auto w-max px-4 py-1 bg-gold text-black font-semibold text-sm rounded-full">
          <T k="investment.most_popular" fallback="Most Popular" />
        </div>
      )}
      {isPremium && (
        <div className="absolute -top-3 left-0 right-0 mx-auto w-max px-4 py-1 bg-purple-500 text-white font-semibold text-sm rounded-full">
          <T k="investment.ultimate_value" fallback="Ultimate Value" />
        </div>
      )}
      <CardHeader>
        {/* Package Icon */}
        <div className="flex justify-center mb-3">
          <div className={`p-3 rounded-full ${iconBgClass}`}>
            <IconComponent className="h-8 w-8 text-white" />
          </div>
        </div>
        <CardTitle className="text-xl font-playfair font-semibold text-center text-gold drop-shadow">
          {pkg.name}
        </CardTitle>
        <p className="text-3xl font-bold text-center text-gradient mb-2">
          ${pkg.price.toLocaleString()}
        </p>
      </CardHeader>
      <CardContent className={contrastList + " pb-0"}>
        <ul className="space-y-3">
          <li className="flex items-center">
            <span className="text-gold font-semibold mr-2">•</span>
            <span className={textBase}>{pkg.shares} <T k="investment.aureus_shares" fallback="Aureus Shares" /></span>
          </li>
          <li className="flex items-center">
            <span className="text-green-400 font-semibold mr-2">•</span>
            <span className={textBase}>{pkg.commission_percentage || 20}% <T k="commission.direct_sales" fallback="Direct Sales Commission" /></span>
          </li>
          <li className="flex items-center">
            <span className="text-purple-400 font-semibold mr-2">•</span>
            <span className={textBase}>{pkg.npo_allocation || 10}% <T k="npo.charity_contribution" fallback="Charity Contribution" /></span>
          </li>
          <li className="flex items-center">
            <span className="text-yellow-400 font-semibold mr-2">•</span>
            <span className={textBase}><T k="competition.participation" fallback="Competition Participation" /></span>
          </li>
        </ul>

        {/* Revenue Distribution */}
        <div className="mt-4 pt-4 border-t border-gold/20">
          <p className="text-sm font-bold text-gold mb-2">Revenue Distribution:</p>
          <div className="grid grid-cols-2 gap-2 text-xs">
            <div className="flex justify-between">
              <span className="text-green-400">Commission:</span>
              <span className="text-white">{pkg.commission_percentage || 15}%</span>
            </div>
            <div className="flex justify-between">
              <span className="text-yellow-400">Competition:</span>
              <span className="text-white">{pkg.competition_allocation || 15}%</span>
            </div>
            <div className="flex justify-between">
              <span className="text-purple-400">NPO Fund:</span>
              <span className="text-white">{pkg.npo_allocation || 10}%</span>
            </div>
            <div className="flex justify-between">
              <span className="text-blue-400">Platform:</span>
              <span className="text-white">{pkg.platform_allocation || 25}%</span>
            </div>
            <div className="flex justify-between col-span-2">
              <span className="text-orange-400">Mine Setup:</span>
              <span className="text-white">{pkg.mine_allocation || 35}%</span>
            </div>
          </div>
        </div>

        {pkg.bonuses && pkg.bonuses.length > 0 && (
          <div className="mt-4 pt-4 border-t border-gold/20">
            <p className="text-sm font-bold text-gold mb-2">
              <T k="investment.package_bonuses" fallback="Package Bonuses:" />
            </p>
            <ul className="space-y-1">
              {pkg.bonuses.map((bonus, index) => {
                const translationKey = getBonusTranslationKey(bonus);
                return (
                  <li key={index} className="flex items-start">
                    <span className="text-gold font-semibold mr-2 mt-0.5">•</span>
                    <span className="text-xs text-white/80">
                      <T k={translationKey} fallback={bonus} />
                    </span>
                  </li>
                );
              })}
            </ul>
          </div>
        )}
      </CardContent>
      
      {showParticipateButton && (
        <div className="p-6 pt-4">
          <Button
            onClick={handleParticipateClick}
            className="w-full bg-gold-gradient text-black font-semibold hover:opacity-90 transition-all duration-200"
            size="lg"
          >
            <T k="participation.participate_button" fallback="Participate →" />
          </Button>
        </div>
      )}
    </Card>
  );
};

export default DatabasePlanCard;
