import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Slider } from '@/components/ui/slider';
import { useInvestmentPackages } from '@/hooks/useInvestmentPackages';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import {
  Plus,
  Minus,
  ShoppingCart,
  DollarSign,
  Target,
  TrendingUp,
  Package,
  Zap,
  Calculator,
  Wallet,
  Pickaxe,
  HardHat,
  Truck,
  Construction,
  Settings,
  Factory,
  Crown,
  Star
} from 'lucide-react';

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

interface PackageSelection {
  packageId: string;
  quantity: number;
  package: any;
}

interface MultiPackageSelectorProps {
  onPurchase: (selections: PackageSelection[], totalAmount: number) => void;
}

const MultiPackageSelector: React.FC<MultiPackageSelectorProps> = ({ onPurchase }) => {
  const { translate } = useTranslation();
  const { packages, isLoading } = useInvestmentPackages();
  const [targetAmount, setTargetAmount] = useState(500);
  const [selections, setSelections] = useState<PackageSelection[]>([]);
  const [showOptimized, setShowOptimized] = useState(false);

  // Calculate totals
  const totalAmount = selections.reduce((sum, sel) => sum + (sel.package.price * sel.quantity), 0);
  const totalShares = selections.reduce((sum, sel) => sum + (sel.package.shares * sel.quantity), 0);
  const totalROI = selections.reduce((sum, sel) => sum + (sel.package.roi * sel.quantity), 0);
  const totalDividends = selections.reduce((sum, sel) => sum + (sel.package.annual_dividends * sel.quantity), 0);

  // Auto-optimize package selection based on target amount
  const optimizeSelection = () => {
    if (packages.length === 0) return;

    const sortedPackages = [...packages].sort((a, b) => b.price - a.price);
    const newSelections: PackageSelection[] = [];
    let remainingAmount = targetAmount;

    for (const pkg of sortedPackages) {
      if (remainingAmount >= pkg.price) {
        const quantity = Math.floor(remainingAmount / pkg.price);
        if (quantity > 0) {
          newSelections.push({
            packageId: pkg.id,
            quantity,
            package: pkg
          });
          remainingAmount -= pkg.price * quantity;
        }
      }
    }

    setSelections(newSelections);
    setShowOptimized(true);
  };

  // Update package quantity
  const updateQuantity = (packageId: string, newQuantity: number) => {
    if (newQuantity <= 0) {
      setSelections(prev => prev.filter(sel => sel.packageId !== packageId));
      return;
    }

    const pkg = packages.find(p => p.id === packageId);
    if (!pkg) return;

    setSelections(prev => {
      const existing = prev.find(sel => sel.packageId === packageId);
      if (existing) {
        return prev.map(sel => 
          sel.packageId === packageId 
            ? { ...sel, quantity: newQuantity }
            : sel
        );
      } else {
        return [...prev, { packageId, quantity: newQuantity, package: pkg }];
      }
    });
  };

  // Add one of a package
  const addPackage = (pkg: any) => {
    const existing = selections.find(sel => sel.packageId === pkg.id);
    const currentQuantity = existing ? existing.quantity : 0;
    updateQuantity(pkg.id, currentQuantity + 1);
  };

  // Remove one of a package
  const removePackage = (packageId: string) => {
    const existing = selections.find(sel => sel.packageId === packageId);
    if (existing && existing.quantity > 0) {
      updateQuantity(packageId, existing.quantity - 1);
    }
  };

  // Clear all selections
  const clearSelections = () => {
    setSelections([]);
    setShowOptimized(false);
  };

  if (isLoading) {
    return (
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-8 text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold mx-auto mb-4"></div>
          <p className="text-gray-400">
            <T k="loading_investment_packages" fallback="Loading investment packages..." />
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      {/* Target Amount Selector */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center">
            <Target className="h-5 w-5 mr-2 text-gold" />
            <T k="set_investment_target" fallback="Set Your Investment Target" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="text-sm text-gray-400 mb-2 block">
                <T k="target_amount_usd" fallback="Target Amount (USD)" />
              </label>
              <Input
                type="number"
                value={targetAmount}
                onChange={(e) => setTargetAmount(Number(e.target.value))}
                className="bg-gray-700 border-gray-600 text-white"
                min="25"
                max="50000"
              />
            </div>
            <div className="flex items-end">
              <Button
                onClick={optimizeSelection}
                className="bg-gold-gradient text-black hover:opacity-90 w-full"
              >
                <Calculator className="h-4 w-4 mr-2" />
                <T k="auto_optimize" fallback="Auto-Optimize" />
              </Button>
            </div>
          </div>
          
          <div>
            <label className="text-sm text-gray-400 mb-2 block">
              {translate('quick_select_amount', 'Quick Select: ${amount}')
                .replace('${amount}', `$${targetAmount.toLocaleString()}`)}
            </label>
            <Slider
              value={[targetAmount]}
              onValueChange={(value) => setTargetAmount(value[0])}
              max={50000}
              min={25}
              step={25}
              className="w-full"
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span><T k="dollar_25" fallback="$25" /></span>
              <span><T k="dollar_50000" fallback="$50,000" /></span>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Package Selection Grid */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-white flex items-center">
              <Package className="h-5 w-5 mr-2 text-gold" />
              <T k="choose_your_packages" fallback="Choose Your Packages" />
            </CardTitle>
            {selections.length > 0 && (
              <Button
                variant="ghost"
                onClick={clearSelections}
                className="text-red-400 hover:text-red-300"
              >
                <T k="clear_all" fallback="Clear All" />
              </Button>
            )}
          </div>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {packages.map((pkg) => {
              const selection = selections.find(sel => sel.packageId === pkg.id);
              const quantity = selection ? selection.quantity : 0;

              // Get the appropriate icon for the package
              const IconComponent = pkg.icon && iconMap[pkg.icon as keyof typeof iconMap]
                ? iconMap[pkg.icon as keyof typeof iconMap]
                : Star;

              // Get icon background color class
              const iconBgClass = pkg.icon_color || 'bg-gold';

              return (
                <div
                  key={pkg.id}
                  className={`border-2 rounded-lg p-4 transition-all duration-200 ${
                    quantity > 0
                      ? 'border-gold bg-gold/5'
                      : 'border-gray-600 bg-gray-700/50 hover:border-gray-500'
                  }`}
                >
                  <div className="text-center mb-3">
                    {/* Package Icon */}
                    <div className="flex justify-center mb-2">
                      <div className={`p-2 rounded-full ${iconBgClass}`}>
                        <IconComponent className="h-6 w-6 text-white" />
                      </div>
                    </div>
                    <h3 className="font-bold text-white">{pkg.name}</h3>
                    <p className="text-gold font-semibold">${(pkg.price || 0).toLocaleString()}</p>
                  </div>
                  
                  <div className="text-xs text-gray-300 space-y-1 mb-3">
                    <div>• {translate('aureus_shares_count', '{count} Digital Shares').replace('{count}', (pkg.shares || 0).toString())}</div>
                    <div>• <span className="text-green-400">20% Direct Commission</span></div>
                    <div>• <span className="text-purple-400">Competition Entry</span></div>
                    <div>• <span className="text-yellow-400">NFT Certificate</span></div>
                  </div>

                  <div className="flex items-center justify-between">
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => removePackage(pkg.id)}
                      disabled={quantity === 0}
                      className="text-red-400 hover:text-red-300 p-1"
                    >
                      <Minus className="h-4 w-4" />
                    </Button>
                    
                    <div className="flex items-center space-x-2">
                      <span className="text-white font-semibold min-w-[20px] text-center">
                        {quantity}
                      </span>
                      {quantity > 0 && (
                        <Badge className="bg-gold/20 text-gold text-xs">
                          ${((pkg.price || 0) * quantity).toLocaleString()}
                        </Badge>
                      )}
                    </div>
                    
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => addPackage(pkg)}
                      className="text-green-400 hover:text-green-300 p-1"
                    >
                      <Plus className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Selection Summary */}
      {selections.length > 0 && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center">
              <ShoppingCart className="h-5 w-5 mr-2 text-gold" />
              <T k="your_investment_summary" fallback="Your Investment Summary" />
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Selected Packages */}
            <div className="space-y-2">
              {selections.map((sel) => (
                <div key={sel.packageId} className="flex items-center justify-between bg-gray-700/50 rounded-lg p-3">
                  <div>
                    <span className="text-white font-semibold">{sel.package.name}</span>
                    <span className="text-gray-400 ml-2">
                      {translate('x_quantity', 'x{quantity}').replace('{quantity}', sel.quantity.toString())}
                    </span>
                  </div>
                  <div className="text-gold font-semibold">
                    ${((sel.package.price || 0) * sel.quantity).toLocaleString()}
                  </div>
                </div>
              ))}
            </div>

            {/* Totals */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-700">
              <div className="text-center">
                <DollarSign className="h-6 w-6 text-gold mx-auto mb-1" />
                <div className="text-sm text-gray-400">
                  <T k="total_investment" fallback="Total Investment" />
                </div>
                <div className="font-bold text-white">${totalAmount.toLocaleString()}</div>
              </div>
              <div className="text-center">
                <Package className="h-6 w-6 text-blue-400 mx-auto mb-1" />
                <div className="text-sm text-gray-400">
                  <T k="total_shares" fallback="Total Shares" />
                </div>
                <div className="font-bold text-white">{totalShares.toLocaleString()}</div>
              </div>
              <div className="text-center">
                <TrendingUp className="h-6 w-6 text-green-400 mx-auto mb-1" />
                <div className="text-sm text-gray-400">
                  <T k="commission_potential" fallback="Commission Potential" />
                </div>
                <div className="font-bold text-white">${(totalAmount * 0.2).toLocaleString()}</div>
              </div>
              <div className="text-center">
                <Zap className="h-6 w-6 text-purple-400 mx-auto mb-1" />
                <div className="text-sm text-gray-400">
                  <T k="charity_contribution" fallback="Charity Contribution" />
                </div>
                <div className="font-bold text-white">${(totalAmount * 0.1).toLocaleString()}</div>
              </div>
            </div>

            {/* Purchase Button */}
            <Button
              onClick={() => onPurchase(selections, totalAmount)}
              className="w-full bg-gold-gradient text-black hover:opacity-90 py-3 text-lg font-semibold"
              disabled={totalAmount === 0}
            >
              <Wallet className="h-5 w-5 mr-2" />
              {translate('proceed_to_payment_amount', 'Proceed to Payment - ${amount}')
                .replace('${amount}', `$${totalAmount.toLocaleString()}`)}
            </Button>

            {showOptimized && (
              <div className="text-center text-sm text-green-400">
                {translate('optimized_selection_target', '✨ Optimized selection for ${amount} target')
                  .replace('${amount}', `$${targetAmount.toLocaleString()}`)}
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default MultiPackageSelector;
