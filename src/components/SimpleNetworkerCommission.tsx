import React from 'react';
import { Badge } from '@/components/ui/badge';
import { ST as T } from '@/components/SimpleTranslator';

// Safe icons
const DollarSign = ({ className }: { className?: string }) => <span className={className}>💰</span>;
const Users = ({ className }: { className?: string }) => <span className={className}>👥</span>;
const Gift = ({ className }: { className?: string }) => <span className={className}>🎁</span>;

const SimpleNetworkerCommission: React.FC = () => {
  return (
    <section className="py-16 px-6 md:px-12 bg-gradient-to-b from-royal/20 to-charcoal/30">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
            <span className="text-gradient"><T k="commission.title" fallback="Networker Commission" /></span> <T k="commission.structure" fallback="Structure" />
          </h2>
          <Badge className="bg-gold/20 text-gold border-gold/30 px-4 py-2 mb-6">
            💼 <T k="commission.plan_type" fallback="Unilevel 3-Level Plan" />
          </Badge>
          <p className="text-center text-white/70 mb-12 max-w-2xl mx-auto">
            <T k="commission.description" fallback="Earn dual rewards in USDT + NFT Pack bonuses through our transparent 3-level commission structure." />
          </p>
        </div>

        <div className="grid lg:grid-cols-3 gap-8">
          <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
            <div className="text-center mb-4">
              <Badge className="bg-gold/20 text-gold border-gold/30 font-bold mb-4">
                <T k="commission.level1" fallback="Level 1" />
              </Badge>
              <Users className="w-8 h-8 text-gold mx-auto mb-4" />
            </div>
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-white/70">
                  <T k="commission.usdt_commission" fallback="USDT Commission:" />
                </span>
                <span className="text-gold font-bold">12%</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-white/70">
                  <T k="commission.nft_bonus" fallback="NFT Bonus:" />
                </span>
                <span className="text-gold font-bold">12%</span>
              </div>
            </div>
          </div>

          <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
            <div className="text-center mb-4">
              <Badge className="bg-gold/20 text-gold border-gold/30 font-bold mb-4">
                <T k="commission.level2" fallback="Level 2" />
              </Badge>
              <Users className="w-8 h-8 text-gold mx-auto mb-4" />
            </div>
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-white/70">
                  <T k="commission.usdt_commission" fallback="USDT Commission:" />
                </span>
                <span className="text-gold font-bold">5%</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-white/70">
                  <T k="commission.nft_bonus" fallback="NFT Bonus:" />
                </span>
                <span className="text-gold font-bold">5%</span>
              </div>
            </div>
          </div>

          <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
            <div className="text-center mb-4">
              <Badge className="bg-gold/20 text-gold border-gold/30 font-bold mb-4">
                <T k="commission.level3" fallback="Level 3" />
              </Badge>
              <Users className="w-8 h-8 text-gold mx-auto mb-4" />
            </div>
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-white/70">
                  <T k="commission.usdt_commission" fallback="USDT Commission:" />
                </span>
                <span className="text-gold font-bold">3%</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-white/70">
                  <T k="commission.nft_bonus" fallback="NFT Bonus:" />
                </span>
                <span className="text-gold font-bold">3%</span>
              </div>
            </div>
          </div>
        </div>

        <div className="mt-12 bg-black/30 rounded-lg p-6 border border-gold/30">
          <h3 className="text-xl font-playfair font-semibold text-center mb-6">
            <T k="commission.example_title" fallback="Example: $1,000 Sale" />
          </h3>
          <div className="grid md:grid-cols-3 gap-4">
            <div className="bg-green-500/10 rounded p-4 border border-green-500/30 text-center">
              <div className="text-green-400 font-bold">
                <T k="commission.level1_earns" fallback="Level 1 Earns" />
              </div>
              <div className="text-white">
                <T k="commission.level1_example" fallback="$120 USDT + 24 NFT Shares" />
              </div>
            </div>
            <div className="bg-blue-500/10 rounded p-4 border border-blue-500/30 text-center">
              <div className="text-blue-400 font-bold">
                <T k="commission.level2_earns" fallback="Level 2 Earns" />
              </div>
              <div className="text-white">
                <T k="commission.level2_example" fallback="$50 USDT + 10 NFT Shares" />
              </div>
            </div>
            <div className="bg-purple-500/10 rounded p-4 border border-purple-500/30 text-center">
              <div className="text-purple-400 font-bold">
                <T k="commission.level3_earns" fallback="Level 3 Earns" />
              </div>
              <div className="text-white">
                <T k="commission.level3_example" fallback="$30 USDT + 6 NFT Shares" />
              </div>
            </div>
          </div>
          <div className="mt-6 text-center">
            <div className="text-gold font-bold text-lg">
              <T k="commission.total_commission_pool" fallback="Total Commission Pool" />
            </div>
            <div className="text-2xl font-bold text-white">
              <T k="commission.total_example" fallback="$200 USDT + 40 NFT Shares" />
            </div>
            <div className="text-sm text-white/60">
              <T k="commission.sale_value_distributed" fallback="20% of sale value distributed" />
            </div>
          </div>
        </div>

        <div className="mt-12 bg-black/30 rounded-lg p-6 border border-gold/30 text-center">
          <h3 className="text-xl font-playfair font-semibold mb-4">
            <T k="commission.pool_title" fallback="Total Commission Pool" />
          </h3>
          <div className="text-3xl font-bold text-gold mb-2">$450,000</div>
          <div className="text-white/70 mb-4">
            <T k="commission.presale_funds_percent" fallback="45% of Presale Funds" />
          </div>
          <div className="grid grid-cols-2 gap-4 max-w-md mx-auto">
            <div>
              <div className="text-gold font-semibold">
                <T k="commission.usdt_pool" fallback="USDT Pool" />
              </div>
              <div className="text-white">$225,000</div>
            </div>
            <div>
              <div className="text-gold font-semibold">
                <T k="commission.nft_pool" fallback="NFT Pool" />
              </div>
              <div className="text-white">
                45,000 <T k="commission.shares" fallback="Shares" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default SimpleNetworkerCommission;
