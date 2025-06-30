import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Trophy,
  Crown,
  Medal,
  Star,
  TrendingUp,
  Users,
  DollarSign,
  Zap,
  Target,
  Award,
  Sparkles
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { ST as T } from '@/components/SimpleTranslator';
import ApiConfig from '@/config/api';

interface LeaderboardEntry {
  rank: number;
  username: string;
  referrals: number;
  volume: number;
  prize: number;
  country: string;
  flag: string;
  isQualified: boolean;
}

const GoldDiggersClub: React.FC = () => {
  const [leaderboardData, setLeaderboardData] = useState<LeaderboardEntry[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [totalParticipants, setTotalParticipants] = useState(0);
  const [leadingVolume, setLeadingVolume] = useState(0);

  // Load real leaderboard data from API
  useEffect(() => {
    const loadLeaderboardData = async () => {
      try {
        const response = await fetch(`${ApiConfig.endpoints.referrals.goldDiggers}?action=gold_diggers_club`);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
          setLeaderboardData(data.data.leaderboard || []);
          // Update stats if available
          if (data.data.total_participants !== undefined) {
            setTotalParticipants(data.data.total_participants);
          }
          if (data.data.leading_volume !== undefined) {
            setLeadingVolume(data.data.leading_volume);
          }
        } else {
          console.error('API returned error:', data.message);
          setLeaderboardData([]);
        }

        setIsLoading(false);
      } catch (error) {
        console.error('Error loading leaderboard:', error);
        setLeaderboardData([]);
        setIsLoading(false);
      }
    };

    loadLeaderboardData();
  }, []);

  const getRankIcon = (rank: number) => {
    switch (rank) {
      case 1:
        return <Crown className="w-6 h-6 text-yellow-400" />;
      case 2:
        return <Medal className="w-6 h-6 text-gray-300" />;
      case 3:
        return <Award className="w-6 h-6 text-amber-600" />;
      default:
        return <Trophy className="w-5 h-5 text-gold/60" />;
    }
  };

  const getRankBadgeColor = (rank: number) => {
    switch (rank) {
      case 1:
        return "bg-gradient-to-r from-yellow-400 to-yellow-600 text-black";
      case 2:
        return "bg-gradient-to-r from-gray-300 to-gray-500 text-black";
      case 3:
        return "bg-gradient-to-r from-amber-600 to-amber-800 text-white";
      default:
        return "bg-gold/20 text-gold border border-gold/30";
    }
  };

  return (
    <section className="py-16 px-6 md:px-12 bg-gradient-to-b from-black/50 to-royal/20">
      {/* Clear section separator */}

      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
            <span className="text-gradient">Gold Diggers Club</span>
          </h2>
          <div className="flex items-center justify-center gap-2 mb-6">
            <DollarSign className="w-6 h-6 text-gold" />
            <span className="text-2xl md:text-3xl font-bold text-gold">$250,000</span>
            <span className="text-xl text-white/80">BONUS POOL</span>
          </div>
          <p className="text-center text-white/70 mb-12 max-w-2xl mx-auto">
            Special leaderboard competition for the <strong>Top 10 Direct Sellers</strong> in the presale. Minimum $2,500 in direct referrals to qualify.
          </p>
        </div>

        <div className="grid lg:grid-cols-2 gap-12 items-start">
          {/* Left Side - Explanation */}
          <div className="space-y-8">
            <div className="bg-black/30 rounded-lg p-6 border border-gold/30">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-3 bg-gold/10 rounded-full">
                  <Target className="w-6 h-6 text-gold" />
                </div>
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
                      <T k="leaderboard.step1_title" fallback="Refer & Earn" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="leaderboard.step1_desc" fallback="Build your network by referring new investors. Each qualified referral counts toward your ranking." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-gold font-bold text-sm">2</span>
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="leaderboard.step2_title" fallback="Minimum Qualification" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="leaderboard.step2_desc" fallback="Achieve minimum $2,500 in direct referral volume to qualify for bonus pool distribution." />
                    </p>
                  </div>
                </div>
                
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-gold/20 flex items-center justify-center flex-shrink-0 mt-1">
                    <span className="text-gold font-bold text-sm">3</span>
                  </div>
                  <div>
                    <h4 className="font-semibold text-white mb-1">
                      <T k="leaderboard.step3_title" fallback="Climb the Rankings" />
                    </h4>
                    <p className="text-white/70 text-sm">
                      <T k="leaderboard.step3_desc" fallback="Your position is determined by total referral volume and network growth metrics." />
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Prize Distribution */}
            <div className="bg-black/30 rounded-lg p-6 border border-gold/30 hover:border-gold/50 transition-all hover:shadow-[0_0_15px_rgba(212,175,55,0.3)]">
              <div className="flex items-center gap-3 mb-6">
                <div className="p-3 bg-gold/10 rounded-full">
                  <Trophy className="w-6 h-6 text-gold" />
                </div>
                <h3 className="text-xl font-playfair font-semibold">
                  <T k="leaderboard.prize_distribution" fallback="Prize Distribution" />
                </h3>
              </div>
                <div className="space-y-3">
                  <div className="flex items-center justify-between p-3 bg-gradient-to-r from-yellow-400/20 to-yellow-600/20 rounded-lg border border-yellow-400/30">
                    <div className="flex items-center gap-3">
                      <Crown className="w-5 h-5 text-yellow-400" />
                      <span className="font-semibold text-white">🥇 1st Place</span>
                    </div>
                    <span className="text-yellow-400 font-bold">$100,000</span>
                  </div>

                  <div className="flex items-center justify-between p-3 bg-gradient-to-r from-gray-300/20 to-gray-500/20 rounded-lg border border-gray-300/30">
                    <div className="flex items-center gap-3">
                      <Medal className="w-5 h-5 text-gray-300" />
                      <span className="font-semibold text-white">🥈 2nd Place</span>
                    </div>
                    <span className="text-gray-300 font-bold">$50,000</span>
                  </div>

                  <div className="flex items-center justify-between p-3 bg-gradient-to-r from-amber-600/20 to-amber-800/20 rounded-lg border border-amber-600/30">
                    <div className="flex items-center gap-3">
                      <Award className="w-5 h-5 text-amber-600" />
                      <span className="font-semibold text-white">🥉 3rd Place</span>
                    </div>
                    <span className="text-amber-600 font-bold">$30,000</span>
                  </div>

                  <div className="flex items-center justify-between p-3 bg-gold/10 rounded-lg border border-gold/30">
                    <div className="flex items-center gap-3">
                      <Trophy className="w-5 h-5 text-gold" />
                      <span className="font-semibold text-white">4th – 10th Place</span>
                    </div>
                    <span className="text-gold font-bold">$10,000 each</span>
                  </div>
                </div>
                
                <div className="mt-4 p-3 bg-black/20 rounded-lg border border-gold/20">
                  <p className="text-sm text-white/70 text-center">
                    <T k="leaderboard.distribution_note" fallback="Remaining pool distributed proportionally among top 10 qualified participants" />
                  </p>
                </div>
              </div>
            </div>

            {/* Call to Action */}
            <div className="text-center">
              <Button className="bg-gold-gradient text-black font-bold px-8 py-4 text-lg hover:opacity-90 transition-opacity" asChild>
                <Link to="/auth">
                  <Zap className="w-5 h-5 mr-2" />
                  <T k="leaderboard.join_competition" fallback="Join the Competition" />
                </Link>
              </Button>
              <p className="text-sm text-white/60 mt-2">
                <T k="leaderboard.competition_ends" fallback="Competition ends when presale reaches $250,000 total volume" />
              </p>
            </div>
          </div>

          {/* Right Side - Leaderboard */}
          <div>
            <div className="bg-black/30 rounded-lg border border-gold/30">
              <div className="p-6 pb-4 border-b border-white/10">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="p-3 bg-gold/10 rounded-full">
                      <TrendingUp className="w-6 h-6 text-gold" />
                    </div>
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
              <div className="p-0">
                {isLoading ? (
                  <div className="space-y-3 p-6">
                    {[...Array(10)].map((_, i) => (
                      <div key={i} className="flex items-center gap-4 p-3 bg-white/5 rounded-lg animate-pulse">
                        <div className="w-8 h-8 bg-white/10 rounded-full"></div>
                        <div className="flex-1 space-y-2">
                          <div className="h-4 bg-white/10 rounded w-3/4"></div>
                          <div className="h-3 bg-white/10 rounded w-1/2"></div>
                        </div>
                        <div className="w-16 h-6 bg-white/10 rounded"></div>
                      </div>
                    ))}
                  </div>
                ) : leaderboardData.length === 0 ? (
                  <div className="p-12 text-center">
                    <Trophy className="w-16 h-16 text-gold/30 mx-auto mb-4" />
                    <h3 className="text-xl font-semibold text-white mb-2">Competition Starting Soon!</h3>
                    <p className="text-white/60 mb-6">
                      The Gold Diggers Club leaderboard will be populated as participants join the presale.
                      <br />
                      Be the first to start building your network!
                    </p>
                    <div className="bg-gold/10 border border-gold/30 rounded-lg p-4">
                      <div className="flex items-center justify-center gap-2 text-gold font-semibold">
                        <Target className="w-5 h-5" />
                        Minimum $2,500 in direct referrals to qualify
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="max-h-96 overflow-y-auto">
                    {leaderboardData.map((entry, index) => (
                      <div
                        key={entry.rank}
                        className={`flex items-center gap-4 p-4 border-b border-white/10 hover:bg-white/5 transition-colors ${
                          entry.rank <= 3 ? 'bg-gradient-to-r from-gold/5 to-transparent' : ''
                        }`}
                      >
                        <div className="flex items-center gap-3 min-w-0 flex-1">
                          <Badge className={`${getRankBadgeColor(entry.rank)} min-w-[2rem] h-8 flex items-center justify-center`}>
                            #{entry.rank}
                          </Badge>

                          <div className="flex items-center gap-2">
                            {getRankIcon(entry.rank)}
                          </div>

                          <div className="min-w-0 flex-1">
                            <div className="flex items-center gap-2">
                              <span className="font-semibold text-white truncate">
                                {entry.username}
                              </span>
                              <span className="text-lg">{entry.flag}</span>
                            </div>
                            <div className="flex items-center gap-4 text-xs text-white/60">
                              <span className="flex items-center gap-1">
                                <Users className="w-3 h-3" />
                                {entry.referrals} refs
                              </span>
                              <span className="flex items-center gap-1">
                                <DollarSign className="w-3 h-3" />
                                ${entry.volume.toLocaleString()}
                              </span>
                            </div>
                          </div>
                        </div>

                        <div className="text-right">
                          <div className="text-gold font-bold">
                            ${entry.prize.toLocaleString()}
                          </div>
                          {entry.isQualified && (
                            <Badge className="bg-green-500/20 text-green-400 border-green-500/30 text-xs">
                              <T k="leaderboard.qualified" fallback="Qualified" />
                            </Badge>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
            
            {/* Stats Summary */}
            <div className="grid grid-cols-2 gap-4 mt-6">
              <div className="bg-black/30 rounded-lg p-4 border border-gold/30 text-center">
                <div className="text-2xl font-bold text-gold">{totalParticipants}</div>
                <div className="text-sm text-white/60">
                  <T k="leaderboard.total_participants" fallback="Total Participants" />
                </div>
              </div>

              <div className="bg-black/30 rounded-lg p-4 border border-gold/30 text-center">
                <div className="text-2xl font-bold text-gold">${leadingVolume.toLocaleString()}</div>
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

export default GoldDiggersClub;
