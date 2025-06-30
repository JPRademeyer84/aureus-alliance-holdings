import React, { useState, useEffect } from 'react';
import { Slider } from "@/components/ui/slider";
import { useNavigate } from 'react-router-dom';
import { maxRoundInvestment } from "@/pages/investment/constants";
import { useInvestmentPackages } from '@/hooks/useInvestmentPackages';
import { useUser } from '@/contexts/UserContext';
import CalculatorLoading from './investment/CalculatorLoading';
import CalculatorResults from './investment/CalculatorResults';
import CalculatorActions from './investment/CalculatorActions';
import { ST as T } from '@/components/SimpleTranslator';

const RewardsCalculator: React.FC = () => {
  const navigate = useNavigate();
  const { isAuthenticated } = useUser();
  const { packages, isLoading, selectedPackageIndex, setSelectedPackageIndex } = useInvestmentPackages();
  const [showResults, setShowResults] = useState(false);
  const [sliderValue, setSliderValue] = useState([500]);

  const handleCalculate = () => {
    console.log('Calculate button clicked, selected package:', selectedPackage);
    setShowResults(true);
  };
  
  const handlePurchase = () => {
    if (isAuthenticated) {
      // User is logged in, go to dashboard
      navigate('/dashboard');
    } else {
      // User not logged in, go to auth/login
      navigate('/auth');
    }
  };

  const selectedPackage = packages[selectedPackageIndex];

  // Update slider when packages change
  useEffect(() => {
    if (packages.length > 0 && selectedPackage) {
      setSliderValue([selectedPackage.price]);
    }
  }, [selectedPackage, packages]);

  // Handle slider changes
  const handleSliderChange = (value: number[]) => {
    setSliderValue(value);
    
    // Find the closest package to the slider value
    const targetValue = value[0];
    let closestIndex = 0;
    let closestDifference = Math.abs(packages[0].price - targetValue);
    
    packages.forEach((pkg, index) => {
      const difference = Math.abs(pkg.price - targetValue);
      if (difference < closestDifference) {
        closestDifference = difference;
        closestIndex = index;
      }
    });
    
    if (closestIndex !== selectedPackageIndex) {
      setSelectedPackageIndex(closestIndex);
      setShowResults(false); // Reset results when package changes
    }
  };

  // Show loading state
  if (isLoading) {
    return (
      <section id="rewards" className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal/30 to-charcoal">
        <div className="max-w-4xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-8 text-center">
            <span className="text-gradient">Rewards</span> Calculator
          </h2>
          <CalculatorLoading />
        </div>
      </section>
    );
  }

  // Show error state if no packages
  if (packages.length === 0) {
    return (
      <section id="rewards" className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal/30 to-charcoal">
        <div className="max-w-4xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-8 text-center">
            <span className="text-gradient">Rewards</span> Calculator
          </h2>
          <p className="text-center text-white/80">Participation packages are currently unavailable. Please check back later.</p>
        </div>
      </section>
    );
  }

  const minPrice = Math.min(...packages.map(pkg => pkg.price));
  const maxPrice = Math.max(...packages.map(pkg => pkg.price));

  return (
    <section id="rewards" className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal/30 to-charcoal">
      <div className="max-w-4xl mx-auto">
        <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-8 text-center">
          <span className="text-gradient">Rewards</span> Calculator
        </h2>

        <div className="bg-charcoal/70 border golden-border rounded-lg p-6 md:p-8">
          <div className="mb-8">
            <div className="flex items-center justify-between mb-2">
              <label className="block text-white/80">
                <T k="calculator.participation_package" fallback="Participation Package:" />
              </label>
              <span className="text-gold font-semibold">
                {selectedPackage?.name} - ${selectedPackage?.price.toLocaleString()}
              </span>
            </div>
            
            <Slider
              value={sliderValue}
              min={minPrice}
              max={maxPrice}
              step={1}
              onValueChange={handleSliderChange}
              className="py-4"
            />
            
            <div className="flex justify-between text-sm text-white/60 mt-1">
              <span>${minPrice.toLocaleString()}</span>
              <span>${maxPrice.toLocaleString()}</span>
            </div>
          </div>

          <CalculatorActions
            onCalculate={handleCalculate}
            onPurchase={handlePurchase}
          />

          {showResults && selectedPackage && (
            <CalculatorResults selectedPackage={selectedPackage} />
          )}

          <div className="mt-6 text-xs text-white/50 text-center">
            <p>
              <T k="calculator.participation_limits" fallback={`Participation between $${minPrice.toLocaleString()} and $${maxPrice.toLocaleString()}. Maximum total round: $${maxRoundInvestment.toLocaleString()}.`} />
            </p>
          </div>
        </div>
      </div>
    </section>
  );
};

export default RewardsCalculator;
