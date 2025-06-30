import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  DollarSign, 
  Percent, 
  CreditCard, 
  Package, 
  Headphones, 
  Zap,
  TrendingUp,
  Shield,
  Star,
  Crown,
  Unlock,
  CheckCircle
} from 'lucide-react';
import KYCLevelBadge from './KYCLevelBadge';

interface Benefit {
  id: string;
  type: string;
  name: string;
  value: string;
  description: string;
}

interface Level {
  id: number;
  level_number: number;
  name: string;
  description: string;
  badge_color: string;
  benefits: Benefit[];
}

interface KYCBenefitsShowcaseProps {
  levels: Level[];
  currentLevel: number;
  className?: string;
}

const KYCBenefitsShowcase: React.FC<KYCBenefitsShowcaseProps> = ({
  levels,
  currentLevel,
  className = ''
}) => {
  const getBenefitIcon = (type: string) => {
    switch (type) {
      case 'investment_limit':
        return <DollarSign className="h-5 w-5" />;
      case 'commission_rate':
        return <Percent className="h-5 w-5" />;
      case 'withdrawal_limit':
        return <CreditCard className="h-5 w-5" />;
      case 'nft_limit':
        return <Package className="h-5 w-5" />;
      case 'support_tier':
        return <Headphones className="h-5 w-5" />;
      case 'feature_access':
        return <Zap className="h-5 w-5" />;
      default:
        return <Star className="h-5 w-5" />;
    }
  };

  const getBenefitColor = (type: string) => {
    switch (type) {
      case 'investment_limit':
        return 'text-green-400';
      case 'commission_rate':
        return 'text-blue-400';
      case 'withdrawal_limit':
        return 'text-purple-400';
      case 'nft_limit':
        return 'text-orange-400';
      case 'support_tier':
        return 'text-pink-400';
      case 'feature_access':
        return 'text-yellow-400';
      default:
        return 'text-gray-400';
    }
  };

  const getLevelIcon = (level: number) => {
    switch (level) {
      case 1:
        return <Shield className="h-6 w-6" />;
      case 2:
        return <Star className="h-6 w-6" />;
      case 3:
        return <Crown className="h-6 w-6" />;
      default:
        return <Shield className="h-6 w-6" />;
    }
  };

  return (
    <div className={`space-y-6 ${className}`}>
      <div className="text-center mb-8">
        <h2 className="text-2xl font-bold text-white mb-2">KYC Level Benefits</h2>
        <p className="text-gray-400">Unlock more features and benefits as you progress through KYC levels</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {levels.map((level) => {
          const isCurrentLevel = level.level_number === currentLevel;
          const isUnlocked = level.level_number <= currentLevel;
          const isNextLevel = level.level_number === currentLevel + 1;

          return (
            <Card
              key={level.id}
              className={`relative overflow-hidden transition-all duration-300 ${
                isCurrentLevel
                  ? 'border-gold bg-gold/5 shadow-lg shadow-gold/20'
                  : isUnlocked
                  ? 'border-green-500/30 bg-green-500/5'
                  : isNextLevel
                  ? 'border-yellow-500/30 bg-yellow-500/5'
                  : 'border-gray-600 bg-gray-800/50'
              }`}
            >
              {/* Level Header */}
              <CardHeader className="pb-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className={`p-2 rounded-lg ${
                      isCurrentLevel
                        ? 'bg-gold/20 text-gold'
                        : isUnlocked
                        ? 'bg-green-500/20 text-green-400'
                        : 'bg-gray-700 text-gray-400'
                    }`}>
                      {getLevelIcon(level.level_number)}
                    </div>
                    <div>
                      <KYCLevelBadge 
                        level={level.level_number} 
                        levelName={level.name}
                        size="sm"
                      />
                      <p className="text-xs text-gray-400 mt-1">{level.description}</p>
                    </div>
                  </div>
                  
                  {isCurrentLevel && (
                    <Badge className="bg-gold/20 text-gold text-xs">
                      Current
                    </Badge>
                  )}
                  {isUnlocked && !isCurrentLevel && (
                    <Badge className="bg-green-500/20 text-green-400 text-xs">
                      <CheckCircle className="h-3 w-3 mr-1" />
                      Unlocked
                    </Badge>
                  )}
                  {!isUnlocked && (
                    <Badge className="bg-gray-500/20 text-gray-400 text-xs">
                      <Unlock className="h-3 w-3 mr-1" />
                      Locked
                    </Badge>
                  )}
                </div>
              </CardHeader>

              {/* Benefits List */}
              <CardContent className="space-y-4">
                {level.benefits.map((benefit) => (
                  <div
                    key={benefit.id}
                    className={`flex items-start gap-3 p-3 rounded-lg transition-all ${
                      isUnlocked
                        ? 'bg-gray-700/50 border border-gray-600'
                        : 'bg-gray-800/50 border border-gray-700 opacity-60'
                    }`}
                  >
                    <div className={`flex-shrink-0 ${getBenefitColor(benefit.type)} ${
                      !isUnlocked ? 'opacity-50' : ''
                    }`}>
                      {getBenefitIcon(benefit.type)}
                    </div>
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-1">
                        <h4 className={`font-medium ${
                          isUnlocked ? 'text-white' : 'text-gray-400'
                        }`}>
                          {benefit.name}
                        </h4>
                        <Badge className={`text-xs ${
                          isUnlocked
                            ? 'bg-blue-500/20 text-blue-400'
                            : 'bg-gray-500/20 text-gray-500'
                        }`}>
                          {benefit.value}
                        </Badge>
                      </div>
                      <p className={`text-sm ${
                        isUnlocked ? 'text-gray-300' : 'text-gray-500'
                      }`}>
                        {benefit.description}
                      </p>
                    </div>
                  </div>
                ))}

                {/* Upgrade Incentive for Next Level */}
                {isNextLevel && (
                  <div className="mt-6 p-4 bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/30 rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <TrendingUp className="h-4 w-4 text-yellow-400" />
                      <span className="text-yellow-400 font-medium text-sm">Next Level Benefits</span>
                    </div>
                    <p className="text-xs text-gray-300">
                      Complete your KYC requirements to unlock these enhanced benefits and features.
                    </p>
                  </div>
                )}

                {/* Current Level Highlight */}
                {isCurrentLevel && (
                  <div className="mt-6 p-4 bg-gradient-to-r from-gold/10 to-yellow-500/10 border border-gold/30 rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <CheckCircle className="h-4 w-4 text-gold" />
                      <span className="text-gold font-medium text-sm">Your Current Benefits</span>
                    </div>
                    <p className="text-xs text-gray-300">
                      You have access to all the benefits listed above. Continue to the next level for even more rewards!
                    </p>
                  </div>
                )}
              </CardContent>

              {/* Locked Overlay */}
              {!isUnlocked && (
                <div className="absolute inset-0 bg-gray-900/20 backdrop-blur-[1px] flex items-center justify-center">
                  <div className="text-center">
                    <Unlock className="h-8 w-8 text-gray-400 mx-auto mb-2" />
                    <p className="text-sm text-gray-400 font-medium">
                      Complete Level {level.level_number - 1} to unlock
                    </p>
                  </div>
                </div>
              )}
            </Card>
          );
        })}
      </div>

      {/* Comparison Table */}
      <Card className="bg-gray-800 border-gray-700 mt-8">
        <CardHeader>
          <CardTitle className="text-white">Benefits Comparison</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-600">
                  <th className="text-left py-3 px-4 text-gray-400">Feature</th>
                  {levels.map((level) => (
                    <th key={level.id} className="text-center py-3 px-4">
                      <KYCLevelBadge level={level.level_number} size="sm" />
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {['investment_limit', 'commission_rate', 'withdrawal_limit', 'nft_limit', 'support_tier'].map((benefitType) => (
                  <tr key={benefitType} className="border-b border-gray-700/50">
                    <td className="py-3 px-4 text-gray-300 font-medium">
                      {benefitType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </td>
                    {levels.map((level) => {
                      const benefit = level.benefits.find(b => b.type === benefitType);
                      const isUnlocked = level.level_number <= currentLevel;
                      return (
                        <td key={level.id} className="text-center py-3 px-4">
                          <span className={`${
                            isUnlocked ? 'text-white' : 'text-gray-500'
                          }`}>
                            {benefit?.value || '-'}
                          </span>
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default KYCBenefitsShowcase;
