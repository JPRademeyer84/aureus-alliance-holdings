import React from "react";
import { ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { ParticipationPlan, participationAmounts, planConfig, rewardDeadline, quarterlyBenefitsStart } from "@/pages/investment/constants";

interface PlanCardProps {
  planKey: ParticipationPlan;
  isPopular?: boolean;
  isPremium?: boolean;
  showParticipateButton?: boolean;
}

const textBase =
  "text-[16px] md:text-[17px] text-white font-normal tracking-tight leading-snug";
const contrastList =
  "bg-[#23243a] border border-gold/25 rounded-lg px-3 py-2";

const PlanCard: React.FC<PlanCardProps> = ({ planKey, isPopular, isPremium, showParticipateButton = true }) => {
  const plan = planConfig[planKey];
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
          Most Popular
        </div>
      )}
      {isPremium && (
        <div className="absolute -top-3 left-0 right-0 mx-auto w-max px-4 py-1 bg-purple-500 text-white font-semibold text-sm rounded-full">
          Ultimate Value
        </div>
      )}
      <CardHeader>
        <CardTitle className="text-xl font-playfair font-semibold text-center text-gold drop-shadow">
          {plan.name}
        </CardTitle>
        <p className="text-3xl font-bold text-center text-gradient mb-2">
          ${participationAmounts[planKey].toLocaleString()}
        </p>
      </CardHeader>
      <CardContent className={contrastList + " pb-0"}>
        <ul className="space-y-3">
          <li className="flex items-center">
            <span className="text-gold font-semibold mr-2">•</span>
            <span className={textBase}>{plan.shares} Aureus Shares</span>
          </li>
          <li className="flex items-center">
            <span className="text-gold font-semibold mr-2">•</span>
            <span className={textBase}>${plan.yield.toLocaleString()} Yield by {yieldDeadline}</span>
          </li>
          <li className="flex items-center">
            <span className="text-gold font-semibold mr-2">•</span>
            <span className={textBase}>${plan.annualDividends.toLocaleString()} Annual Dividends</span>
          </li>
          <li className="flex items-center">
            <span className="text-gold font-semibold mr-2">•</span>
            <span className={textBase}>${plan.quarterDividends.toLocaleString()} Quarterly Dividends</span>
          </li>
        </ul>
      </CardContent>
      <CardFooter className="pt-6">
        {showParticipateButton && (
          <Button className={`w-full ${isPremium ? "bg-purple-gradient text-white" : "bg-gold-gradient text-black"} font-semibold hover:opacity-90 transition-opacity`} asChild>
            <Link to="/auth">Participate <ArrowRight className="ml-2 h-4 w-4" /></Link>
          </Button>
        )}
      </CardFooter>
    </Card>
  );
};

export default PlanCard;
