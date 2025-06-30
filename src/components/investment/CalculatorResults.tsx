
import React from 'react';
import { InvestmentPackage } from '@/hooks/useInvestmentPackages';
import { yieldDeadline, quarterlyDividendsStart } from "@/pages/investment/constants";
import { ST as T } from '@/components/SimpleTranslator';

interface CalculatorResultsProps {
  selectedPackage: InvestmentPackage;
}

const CalculatorResults: React.FC<CalculatorResultsProps> = ({ selectedPackage }) => {
  // Add safety checks for undefined values
  if (!selectedPackage) {
    return null;
  }

  const formatNumber = (value: number | undefined) => {
    return value ? value.toLocaleString() : '0';
  };

  return (
    <div className="mt-8 animate-fade-in border-t border-gold/20 pt-6">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-black/20 p-4 rounded-lg text-center">
          <p className="text-white/70 text-sm mb-1">
            <T k="calculator.total_reward" fallback="Total Reward" />
          </p>
          <p className="text-3xl font-bold text-gradient">
            ${formatNumber(selectedPackage.reward)}
          </p>
          <p className="text-xs text-white/50 mt-1">
            <T k="calculator.by_date" fallback={`By ${yieldDeadline}`} />
          </p>
        </div>

        <div className="bg-black/20 p-4 rounded-lg text-center">
          <p className="text-white/70 text-sm mb-1">
            <T k="calculator.aureus_shares" fallback="Aureus Shares" />
          </p>
          <p className="text-3xl font-bold text-gradient">
            {selectedPackage.shares || 0}
          </p>
          <p className="text-xs text-white/50 mt-1">
            <T k="calculator.package_name" fallback={`${selectedPackage.name || 'Unknown'} Package`} />
          </p>
        </div>

        <div className="bg-black/20 p-4 rounded-lg text-center">
          <p className="text-white/70 text-sm mb-1">
            <T k="calculator.annual_benefit" fallback="Annual Benefit" />
          </p>
          <p className="text-3xl font-bold text-gradient">
            ${formatNumber(selectedPackage.annual_dividends)}
          </p>
          <p className="text-xs text-white/50 mt-1">
            <T k="calculator.starting_date" fallback={`Starting ${quarterlyDividendsStart}`} />
          </p>
        </div>
      </div>

      <div className="mt-6 bg-royal/20 p-4 rounded-lg text-center">
        <p className="text-white font-semibold">
          <T k="calculator.participation_summary" fallback={`By participating with $${formatNumber(selectedPackage.price)} today, you could receive $${formatNumber(selectedPackage.reward)} in rewards by ${yieldDeadline}, plus $${formatNumber(selectedPackage.annual_dividends)} annually in benefits starting ${quarterlyDividendsStart}.`} />
        </p>
      </div>
    </div>
  );
};

export default CalculatorResults;
