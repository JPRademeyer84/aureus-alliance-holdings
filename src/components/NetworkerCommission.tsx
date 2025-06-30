import React from 'react';
import { Badge } from '@/components/ui/badge';
import { 
  Users, 
  DollarSign, 
  Gift, 
  TrendingUp,
  Network,
  Coins,
  Star,
  ArrowDown,
  Calculator
} from 'lucide-react';
import { ST as T } from '@/components/SimpleTranslator';

const NetworkerCommission: React.FC = () => {
  const commissionLevels = [
    { level: 1, usdtCommission: 12, nftBonus: 12 },
    { level: 2, usdtCommission: 5, nftBonus: 5 },
    { level: 3, usdtCommission: 3, nftBonus: 3 }
  ];

  return (
    <section className="py-16 px-6 md:px-12 bg-gradient-to-b from-royal/20 to-charcoal/30">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
            <span className="text-gradient">Networker Commission</span> Structure
          </h2>
          <div className="flex items-center justify-center gap-2 mb-6">
            <Badge className="bg-gold/20 text-gold border-gold/30 px-4 py-2">
              💼 Unilevel 3-Level Plan
            </Badge>
          </div>
          <p className="text-center text-white/70 mb-12 max-w-2xl mx-auto">
            <T k="commission.description" fallback="Earn dual rewards in USDT + NFT Pack bonuses through our transparent 3-level commission structure." />
          </p>
        </div>

        <div className="grid lg:grid-cols-2 gap-12 items-start">
          <div className="space-y-8">
            <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-3 bg-gold/10 rounded-full">
                  <TrendingUp className="w-6 h-6 text-gold" />
                </div>
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="commission.structure_title" fallback="Commission Structure" />
                </h3>
              </div>
              <div className="space-y-4">
                {commissionLevels.map((level, index) => (
                  <div key={level.level}>
                    <div className="p-4 bg-black/20 rounded-lg border border-gold/20 hover:border-gold/40 transition-all">
                      <div className="flex items-center justify-between mb-3">
                        <div className="flex items-center gap-3">
                          <Badge className="bg-gold/20 text-gold border-gold/30 font-bold">
                            Level {level.level}
                          </Badge>
                          <Users className="w-5 h-5 text-gold" />
                        </div>
                        <div className="text-right">
                          <div className="text-lg font-bold text-gold">
                            {level.usdtCommission}% + {level.nftBonus}%
                          </div>
                          <div className="text-xs text-white/60">USDT + NFT</div>
                        </div>
                      </div>
                      
                      <div className="grid grid-cols-2 gap-4">
                        <div className="flex items-center gap-2">
                          <DollarSign className="w-4 h-4 text-gold" />
                          <span className="text-white text-sm">
                            <span className="font-bold text-gold">{level.usdtCommission}%</span> USDT Commission
                          </span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Gift className="w-4 h-4 text-gold" />
                          <span className="text-white text-sm">
                            <span className="font-bold text-gold">{level.nftBonus}%</span> NFT Pack Bonus
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    {index < commissionLevels.length - 1 && (
                      <div className="flex justify-center py-2">
                        <ArrowDown className="w-5 h-5 text-white/40" />
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>

            <div className="bg-black/30 rounded-lg p-6 border border-gold/30 hover:border-gold/50 transition-all hover:shadow-[0_0_15px_rgba(212,175,55,0.3)]">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-3 bg-gold/10 rounded-full">
                  <Calculator className="w-6 h-6 text-gold" />
                </div>
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="commission.example_title" fallback="Example Calculation" />
                </h3>
              </div>
              <div className="bg-black/20 p-4 rounded-lg border border-gold/20">
                <div className="text-center mb-4">
                  <div className="text-2xl font-bold text-gold mb-1">$1,000</div>
                  <div className="text-sm text-white/60">NFT Pack Sale</div>
                </div>
                
                <div className="space-y-3">
                  <div className="flex items-center justify-between p-3 bg-green-500/10 rounded border border-green-500/30">
                    <span className="text-white">Level 1 Earns:</span>
                    <div className="text-right">
                      <div className="text-green-400 font-bold">$120 USDT</div>
                      <div className="text-green-400 text-sm">+ 24 NFT Shares</div>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between p-3 bg-blue-500/10 rounded border border-blue-500/30">
                    <span className="text-white">Level 2 Earns:</span>
                    <div className="text-right">
                      <div className="text-blue-400 font-bold">$50 USDT</div>
                      <div className="text-blue-400 text-sm">+ 10 NFT Shares</div>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between p-3 bg-purple-500/10 rounded border border-purple-500/30">
                    <span className="text-white">Level 3 Earns:</span>
                    <div className="text-right">
                      <div className="text-purple-400 font-bold">$30 USDT</div>
                      <div className="text-purple-400 text-sm">+ 6 NFT Shares</div>
                    </div>
                  </div>
                </div>
                
                <div className="mt-4 p-3 bg-black/20 rounded border border-gold/20">
                  <div className="text-center">
                    <div className="text-gold font-bold">Total Commission Pool</div>
                    <div className="text-xl font-bold text-white">$200 USDT + 40 NFT Shares</div>
                    <div className="text-xs text-white/60 mt-1">20% of sale value distributed</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-8">
            <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-3 bg-gold/10 rounded-full">
                  <Star className="w-6 h-6 text-gold" />
                </div>
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="commission.benefits_title" fallback="Key Benefits" />
                </h3>
              </div>
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <DollarSign className="w-4 h-4 text-gold" />
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="commission.benefit1_title" fallback="Dual Reward System" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="commission.benefit1_desc" fallback="Earn both USDT commissions and NFT pack bonuses for maximum value." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <Network className="w-4 h-4 text-gold" />
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="commission.benefit2_title" fallback="3-Level Deep" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="commission.benefit2_desc" fallback="Build a sustainable network with rewards from 3 levels of referrals." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <Coins className="w-4 h-4 text-gold" />
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="commission.benefit3_title" fallback="Instant Payouts" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="commission.benefit3_desc" fallback="Receive USDT commissions immediately upon successful referral sales." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <Gift className="w-4 h-4 text-gold" />
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="commission.benefit4_title" fallback="NFT Ownership" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="commission.benefit4_desc" fallback="NFT bonuses provide real ownership in gold mining operations with future dividends." />
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-black/30 rounded-lg p-6 border border-gold/30 hover:border-gold/50 transition-all hover:shadow-[0_0_15px_rgba(212,175,55,0.3)]">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-3 bg-gold/10 rounded-full">
                  <TrendingUp className="w-6 h-6 text-gold" />
                </div>
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="commission.pool_title" fallback="Total Commission Pool" />
                </h3>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-gold mb-2">$450,000</div>
                <div className="text-white/70 mb-4">45% of Presale Funds</div>
                <div className="bg-black/20 p-4 rounded-lg border border-gold/20">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <div className="text-gold font-semibold">USDT Pool</div>
                      <div className="text-white">$225,000</div>
                    </div>
                    <div>
                      <div className="text-gold font-semibold">NFT Pool</div>
                      <div className="text-white">45,000 Shares</div>
                    </div>
                  </div>
                </div>
                <p className="text-xs text-white/60 mt-3">
                  <T k="commission.pool_note" fallback="Commission pool funded from presale proceeds, ensuring sustainable rewards." />
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default NetworkerCommission;
