import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import { ST as T } from '@/components/SimpleTranslator';

// Safe icons
const DollarSign = ({ className }: { className?: string }) => <span className={className}>💰</span>;
const Trophy = ({ className }: { className?: string }) => <span className={className}>🏆</span>;
const Crown = ({ className }: { className?: string }) => <span className={className}>👑</span>;
const Medal = ({ className }: { className?: string }) => <span className={className}>🏅</span>;
const Award = ({ className }: { className?: string }) => <span className={className}>🎖️</span>;
const Target = ({ className }: { className?: string }) => <span className={className}>🎯</span>;
const Zap = ({ className }: { className?: string }) => <span className={className}>⚡</span>;

const SimpleGoldDiggersClub: React.FC = () => {
  return (
    <section className="py-16 px-6 md:px-12 bg-gradient-to-b from-black/50 to-royal/20">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
            <span className="text-gradient"><T k="leaderboard.title" fallback="Gold Diggers Club" /></span>
          </h2>
          <div className="flex items-center justify-center gap-2 mb-6">
            <DollarSign className="w-6 h-6 text-gold" />
            <span className="text-2xl md:text-3xl font-bold text-gold">$250,000</span>
            <span className="text-xl text-white/80"><T k="leaderboard.bonus_pool" fallback="BONUS POOL" /></span>
          </div>
          <p className="text-center text-white/70 mb-12 max-w-2xl mx-auto">
            <T k="leaderboard.description" fallback="Special leaderboard competition for the Top 10 Direct Sellers in the presale. Minimum $2,500 in direct referrals to qualify." />
          </p>
        </div>

        <div className="grid lg:grid-cols-2 gap-12 items-start">
          <div className="space-y-8">
            <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
              <div className="flex items-center gap-3 mb-6">
                <Target className="w-6 h-6 text-gold" />
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="leaderboard.how_it_works" fallback="How It Works" />
                </h3>
              </div>
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-gold font-bold text-sm">1</span>
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="golddiggers.refer_earn" fallback="Refer & Earn" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="golddiggers.refer_earn_desc" fallback="Build your network by referring new investors. Each qualified referral counts toward your ranking." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-gold font-bold text-sm">2</span>
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="golddiggers.minimum_qualification" fallback="Minimum Qualification" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="golddiggers.minimum_qualification_desc" fallback="Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-gold font-bold text-sm">3</span>
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="golddiggers.climb_rankings" fallback="Climb the Rankings" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="golddiggers.climb_rankings_desc" fallback="Your position is determined by total referral volume and network growth metrics." />
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
              <div className="flex items-center gap-3 mb-6">
                <Trophy className="w-6 h-6 text-gold" />
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="leaderboard.prize_distribution" fallback="Prize Distribution" />
                </h3>
              </div>
              <div className="space-y-3">
                <div className="flex items-center justify-between p-3 bg-gradient-to-r from-yellow-400/20 to-yellow-600/20 rounded-lg border border-yellow-400/30">
                  <div className="flex items-center gap-3">
                    <Crown className="w-5 h-5 text-yellow-400" />
                    <span className="font-semibold text-white">🥇 <T k="golddiggers.first_place" fallback="1st Place" /></span>
                  </div>
                  <span className="text-yellow-400 font-bold">$100,000</span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gradient-to-r from-gray-300/20 to-gray-500/20 rounded-lg border border-gray-300/30">
                  <div className="flex items-center gap-3">
                    <Medal className="w-5 h-5 text-gray-300" />
                    <span className="font-semibold text-white">🥈 <T k="golddiggers.second_place" fallback="2nd Place" /></span>
                  </div>
                  <span className="text-gray-300 font-bold">$50,000</span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gradient-to-r from-amber-600/20 to-amber-800/20 rounded-lg border border-amber-600/30">
                  <div className="flex items-center gap-3">
                    <Award className="w-5 h-5 text-amber-600" />
                    <span className="font-semibold text-white">🥉 <T k="golddiggers.third_place" fallback="3rd Place" /></span>
                  </div>
                  <span className="text-amber-600 font-bold">$30,000</span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gold/10 rounded-lg border border-gold/30">
                  <div className="flex items-center gap-3">
                    <Trophy className="w-5 h-5 text-gold" />
                    <span className="font-semibold text-white"><T k="golddiggers.fourth_tenth_place" fallback="4th – 10th Place" /></span>
                  </div>
                  <span className="text-gold font-bold">$10,000 <T k="golddiggers.each" fallback="each" /></span>
                </div>
              </div>
              
              <div className="mt-4 p-3 bg-black/20 rounded-lg border border-gold/20">
                <p className="text-sm text-white/70 text-center">
                  <T k="golddiggers.remaining_pool_desc" fallback="Remaining pool distributed proportionally among top 10 qualified participants" />
                </p>
              </div>
            </div>

            <div className="text-center">
              <Button className="bg-gold-gradient text-black font-bold px-8 py-4 text-lg hover:opacity-90 transition-opacity" asChild>
                <Link to="/auth">
                  <Zap className="w-5 h-5 mr-2" />
                  <T k="leaderboard.join_competition" fallback="Join the Competition" />
                </Link>
              </Button>
              <p className="text-sm text-white/60 mt-2">
                <T k="golddiggers.competition_ends" fallback="Competition ends when presale reaches $250,000 total volume" />
              </p>
            </div>
          </div>

          <div>
            <div className="bg-black/30 rounded-lg border border-gold/30">
              <div className="p-6 pb-4 border-b border-white/10">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Trophy className="w-6 h-6 text-gold" />
                    <h3 className="text-xl font-playfair font-semibold">
                      <T k="leaderboard.live_rankings" fallback="Live Rankings" />
                    </h3>
                  </div>
                  <Badge className="bg-green-500/20 text-green-400 border-green-500/30">
                    <div className="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                    <T k="leaderboard.live" fallback="LIVE" />
                  </Badge>
                </div>
              </div>
              
              <div className="p-12 text-center">
                <Trophy className="w-16 h-16 text-gold/30 mx-auto mb-4" />
                <h3 className="text-xl font-semibold text-white mb-2">
                  <T k="golddiggers.competition_starting_soon" fallback="Competition Starting Soon!" />
                </h3>
                <p className="text-white/60 mb-6">
                  <T k="golddiggers.leaderboard_populated" fallback="The Gold Diggers Club leaderboard will be populated as participants join the presale." />
                  <br />
                  <T k="golddiggers.be_first_to_start" fallback="Be the first to start building your network!" />
                </p>
                <div className="bg-gold/10 border border-gold/30 rounded-lg p-4">
                  <div className="flex items-center justify-center gap-2 text-gold font-semibold">
                    <Target className="w-5 h-5" />
                    <T k="golddiggers.minimum_qualify_badge" fallback="Minimum $2,500 in direct referrals to qualify" />
                  </div>
                </div>
              </div>
            </div>
            
            <div className="grid grid-cols-2 gap-4 mt-6">
              <div className="bg-black/30 rounded-lg p-4 border border-gold/30 text-center">
                <div className="text-2xl font-bold text-gold">0</div>
                <div className="text-sm text-white/60">
                  <T k="leaderboard.total_participants" fallback="Total Participants" />
                </div>
              </div>

              <div className="bg-black/30 rounded-lg p-4 border border-gold/30 text-center">
                <div className="text-2xl font-bold text-gold">$0</div>
                <div className="text-sm text-white/60">
                  <T k="leaderboard.leading_volume" fallback="Leading Volume" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default SimpleGoldDiggersClub;
