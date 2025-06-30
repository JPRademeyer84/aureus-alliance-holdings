import React from 'react';
import { Badge } from '@/components/ui/badge';
import { ST as T } from '@/components/SimpleTranslator';

// Safe icons
const Calendar = ({ className }: { className?: string }) => <span className={className}>📅</span>;
const DollarSign = ({ className }: { className?: string }) => <span className={className}>💰</span>;
const Shield = ({ className }: { className?: string }) => <span className={className}>🛡️</span>;
const TrendingUp = ({ className }: { className?: string }) => <span className={className}>📈</span>;

// Package name to translation key mapping
const packageNameMap: Record<string, string> = {
  'Shovel': 'rewards.package_shovel',
  'Pick': 'rewards.package_pick',
  'Miner': 'rewards.package_miner',
  'Loader': 'rewards.package_loader',
  'Excavator': 'rewards.package_excavator',
  'Crusher': 'rewards.package_crusher',
  'Refinery': 'rewards.package_refinery',
  'Aureus': 'rewards.package_aureus'
};

const SimpleRewards: React.FC = () => {
  const packages = [
    { name: 'Shovel', purchase: 25, dailyReward: 1.70, totalReward: 306, nftShares: 5 },
    { name: 'Pick', purchase: 50, dailyReward: 1.85, totalReward: 333, nftShares: 10 },
    { name: 'Miner', purchase: 75, dailyReward: 2.00, totalReward: 360, nftShares: 15 },
    { name: 'Loader', purchase: 100, dailyReward: 2.20, totalReward: 396, nftShares: 20 },
    { name: 'Excavator', purchase: 250, dailyReward: 2.50, totalReward: 450, nftShares: 50 },
    { name: 'Crusher', purchase: 500, dailyReward: 3.00, totalReward: 540, nftShares: 100 },
    { name: 'Refinery', purchase: 750, dailyReward: 4.00, totalReward: 720, nftShares: 150 },
    { name: 'Aureus', purchase: 1000, dailyReward: 5.00, totalReward: 900, nftShares: 200 }
  ];

  return (
    <section className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal/30 to-black/50">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
            <span className="text-gradient"><T k="rewards.participant_rewards_model" fallback="Participant Rewards Model" /></span>
          </h2>
          <div className="flex items-center justify-center gap-4 mb-6">
            <Badge className="bg-gold/20 text-gold border-gold/30 px-4 py-2">
              <Calendar className="w-4 h-4 mr-2" />
              <T k="rewards.duration_180_days" fallback="180 Days Duration" />
            </Badge>
            <Badge className="bg-gold/20 text-gold border-gold/30 px-4 py-2">
              <Shield className="w-4 h-4 mr-2" />
              <T k="rewards.funded_by_main_sales" fallback="Funded by Main Sales" />
            </Badge>
          </div>
          <p className="text-center text-white/70 mb-12 max-w-2xl mx-auto">
            <T k="rewards.description" fallback="Choose from 8 participation packages with guaranteed daily rewards over 180 days, plus NFT shares for long-term benefits." />
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
          {packages.map((pkg) => (
            <div key={pkg.name} className="bg-black/30 rounded-lg p-6 border border-gold/30 hover:border-gold/50 transition-all">
              <div className="text-center mb-4">
                <h3 className="text-xl font-playfair font-semibold text-white mb-2">
                  <T k={packageNameMap[pkg.name]} fallback={pkg.name} />
                </h3>
                <Badge className="bg-gold/20 text-gold border-gold/30 font-bold">
                  ${pkg.purchase}
                </Badge>
              </div>
              
              <div className="space-y-3">
                <div className="text-center">
                  <div className="text-2xl font-bold text-gold mb-1">
                    {pkg.dailyReward}%
                  </div>
                  <div className="text-xs text-white/60">
                    <T k="rewards.daily_reward" fallback="Daily Reward" />
                  </div>
                </div>
                
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-white/70">
                      <T k="rewards.total_reward" fallback="Total Reward:" />
                    </span>
                    <span className="text-gold font-semibold">{pkg.totalReward}%</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-white/70">
                      <T k="rewards.nft_shares" fallback="NFT Shares:" />
                    </span>
                    <span className="text-gold font-semibold">{pkg.nftShares}</span>
                  </div>
                </div>

                <div className="text-center pt-3 border-t border-gold/20">
                  <div className="text-2xl font-bold text-white mb-1">
                    ${(pkg.purchase + (pkg.purchase * pkg.totalReward / 100)).toLocaleString()}
                  </div>
                  <div className="text-xs text-white/60">
                    <T k="rewards.reward_value" fallback="Reward Value" />
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="grid lg:grid-cols-2 gap-12">
          <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
            <div className="flex items-center gap-3 mb-6">
              <TrendingUp className="w-6 h-6 text-gold" />
              <h3 className="text-xl font-playfair font-semibold">
                <T k="rewards.how_it_works" fallback="How Rewards Work" />
              </h3>
            </div>
            <div className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                  <span className="text-gold font-bold text-sm">1</span>
                </div>
                <div>
                  <h4 className="font-semibold text-white mb-1">
                    <T k="rewards.step1_title" fallback="Choose Your Package" />
                  </h4>
                  <p className="text-white/70 text-sm">
                    <T k="rewards.step1_desc" fallback="Select from 8 participation packages ranging from $25 to $1,000 based on your budget." />
                  </p>
                </div>
              </div>
              
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                  <span className="text-gold font-bold text-sm">2</span>
                </div>
                <div>
                  <h4 className="font-semibold text-white mb-1">
                    <T k="rewards.step2_title" fallback="Daily Reward Payments" />
                  </h4>
                  <p className="text-white/70 text-sm">
                    <T k="rewards.step2_desc" fallback="Receive daily reward payments ranging from 1.7% to 5% for 180 consecutive days." />
                  </p>
                </div>
              </div>
              
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                  <span className="text-gold font-bold text-sm">3</span>
                </div>
                <div>
                  <h4 className="font-semibold text-white mb-1">
                    <T k="rewards.step3_title" fallback="NFT Ownership" />
                  </h4>
                  <p className="text-white/70 text-sm">
                    <T k="rewards.step3_desc" fallback="Get NFT shares representing real ownership in gold mining operations." />
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
            <div className="flex items-center gap-3 mb-6">
              <Shield className="w-6 h-6 text-gold" />
              <h3 className="text-xl font-playfair font-semibold">
                <T k="rewards.guarantee_title" fallback="Reward Guarantee" />
              </h3>
            </div>
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <DollarSign className="w-5 h-5 text-gold" />
                <span className="text-white">
                  <T k="rewards.guarantee1" fallback="Rewards funded from future main sale proceeds" />
                </span>
              </div>
              <div className="flex items-center gap-3">
                <DollarSign className="w-5 h-5 text-gold" />
                <span className="text-white">
                  <T k="rewards.guarantee2" fallback="Transparent blockchain-based payment system" />
                </span>
              </div>
              <div className="flex items-center gap-3">
                <DollarSign className="w-5 h-5 text-gold" />
                <span className="text-white">
                  <T k="rewards.guarantee3" fallback="Backed by real gold mining operations" />
                </span>
              </div>
              
              <div className="bg-black/20 p-4 rounded border border-gold/20 mt-6">
                <div className="text-center">
                  <div className="text-gold font-bold">
                    <T k="rewards.estimated_total_obligation" fallback="Estimated Total Reward Obligation" />
                  </div>
                  <div className="text-2xl font-bold text-white">~$5,000,000</div>
                  <div className="text-xs text-white/60">
                    <T k="rewards.if_all_presale_sold" fallback="If all presale packs are sold" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="mt-12 bg-black/30 rounded-lg p-6 border border-gold/30 text-center">
          <h3 className="text-xl font-playfair font-semibold mb-6">
            <T k="rewards.participation_timeline" fallback="Participation Timeline" />
          </h3>
          <div className="grid md:grid-cols-3 gap-6">
            <div className="flex items-center gap-4 p-3 bg-black/20 rounded border border-gold/20">
              <div className="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center">
                <span className="text-gold font-bold">1</span>
              </div>
              <div className="text-left">
                <div className="font-semibold text-white">
                  <T k="rewards.day_0" fallback="Day 0" />
                </div>
                <div className="text-sm text-white/70">
                  <T k="rewards.purchase_package" fallback="Purchase Package" />
                </div>
              </div>
            </div>

            <div className="flex items-center gap-4 p-3 bg-black/20 rounded border border-gold/20">
              <div className="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center">
                <span className="text-gold font-bold">180</span>
              </div>
              <div className="text-left">
                <div className="font-semibold text-white">
                  <T k="rewards.day_1_180" fallback="Day 1-180" />
                </div>
                <div className="text-sm text-white/70">
                  <T k="rewards.daily_reward_payments" fallback="Daily Reward Payments" />
                </div>
              </div>
            </div>
            
            <div className="flex items-center gap-4 p-3 bg-black/20 rounded border border-gold/20">
              <div className="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center">
                <span className="text-gold font-bold">∞</span>
              </div>
              <div className="text-left">
                <div className="font-semibold text-white">
                  <T k="rewards.ongoing_title" fallback="Ongoing" />
                </div>
                <div className="text-sm text-white/70">
                  <T k="rewards.ongoing_desc" fallback="NFT Benefits from Mining" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default SimpleRewards;
