
import React from 'react';
import { Button } from "@/components/ui/button";
import { SlidersHorizontal, Package } from "lucide-react";
import { ST as T } from '@/components/SimpleTranslator';

interface CalculatorActionsProps {
  onCalculate: () => void;
  onPurchase: () => void;
}

const CalculatorActions: React.FC<CalculatorActionsProps> = ({ onCalculate, onPurchase }) => (
  <div className="flex flex-col sm:flex-row gap-4 w-full">
    <Button
      className="flex-1 bg-gold-gradient text-black font-semibold hover:opacity-90 transition-opacity py-6"
      onClick={onCalculate}
    >
      <SlidersHorizontal className="mr-2" />
      <T k="calculator.calculate_rewards" fallback="Calculate Rewards" />
    </Button>

    <Button
      className="flex-1 bg-royal text-white font-semibold hover:opacity-90 transition-opacity py-6"
      onClick={onPurchase}
    >
      <Package className="mr-2" />
      <T k="calculator.view_participation_plans" fallback="View Participation Plans" />
    </Button>
  </div>
);

export default CalculatorActions;
