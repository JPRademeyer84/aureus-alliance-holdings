import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { useToast } from '@/hooks/use-toast';
import { 
  Trophy, 
  Medal, 
  Award, 
  Crown, 
  Star, 
  TrendingUp, 
  Users, 
  DollarSign,
  Target,
  Timer,
  Sparkles,
  Gift,
  Zap,
  RefreshCw
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import ApiConfig from '@/config/api';

interface LeaderboardEntry {
  rank: number;
  user_id: string;
  username: string;
  full_name?: string;
  direct_sales_volume: number;
  direct_referrals_count: number;
  bonus_amount: number;
  qualified: boolean;
  profile_image?: string;
}

interface PresaleStats {
  total_packs_sold: number;
  total_packs_available: number;
  total_raised: number;
  presale_progress: number;
  estimated_end_date: string;
  is_presale_active: boolean;
}

const GoldDiggersClub: React.FC = () => {
  const { user } = useUser();
  const { toast } = useToast();
  const [leaderboard, setLeaderboard] = useState<LeaderboardEntry[]>([]);
  const [presaleStats, setPresaleStats] = useState<PresaleStats | null>(null);
  const [userRank, setUserRank] = useState<LeaderboardEntry | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date>(new Date());

  const bonusStructure = [
    { rank: 1, amount: 100000, icon: <Crown className="h-6 w-6" />, color: 'text-yellow-400', bgColor: 'bg-yellow-500/20', title: '1st Place' },
    { rank: 2, amount: 50000, icon: <Medal className="h-6 w-6" />, color: 'text-gray-300', bgColor: 'bg-gray-500/20', title: '2nd Place' },
    { rank: 3, amount: 30000, icon: <Award className="h-6 w-6" />, color: 'text-amber-600', bgColor: 'bg-amber-500/20', title: '3rd Place' },
    { rank: 4, amount: 10000, icon: <Trophy className="h-5 w-5" />, color: 'text-blue-400', bgColor: 'bg-blue-500/20', title: '4th-10th' }
  ];

  useEffect(() => {
    fetchLeaderboardData();
    const interval = setInterval(fetchLeaderboardData, 30000); // Update every 30 seconds
    return () => clearInterval(interval);
  }, []);

  const fetchLeaderboardData = async () => {
    try {
      const url = `${ApiConfig.endpoints.referrals.goldDiggers}?action=gold_diggers_club`;
      console.log('Fetching from URL:', url); // Debug log

      const response = await fetch(url);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const text = await response.text();
      console.log('Raw response:', text); // Debug log

      let data;
      try {
        data = JSON.parse(text);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.error('Response text:', text);
        throw new Error('Invalid JSON response from server');
      }

      if (data.success) {
        setLeaderboard(data.data?.leaderboard || []);
        setPresaleStats(data.data?.presale_stats);

        // Find current user's rank
        if (user?.id) {
          const userEntry = data.data?.leaderboard?.find((entry: LeaderboardEntry) => entry.user_id === user.id.toString());
          setUserRank(userEntry || null);
        }

        setLastUpdated(new Date());
      } else {
        throw new Error(data.message || 'Failed to fetch leaderboard data');
      }
    } catch (error) {
      console.error('Failed to fetch leaderboard:', error);
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to load leaderboard data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const getBonusAmount = (rank: number): number => {
    if (rank === 1) return 100000;
    if (rank === 2) return 50000;
    if (rank === 3) return 30000;
    if (rank >= 4 && rank <= 10) return 10000;
    return 0;
  };

  const getRankIcon = (rank: number) => {
    if (rank === 1) return <Crown className="h-6 w-6 text-yellow-400" />;
    if (rank === 2) return <Medal className="h-6 w-6 text-gray-300" />;
    if (rank === 3) return <Award className="h-6 w-6 text-amber-600" />;
    if (rank <= 10) return <Trophy className="h-5 w-5 text-blue-400" />;
    return <Star className="h-4 w-4 text-gray-400" />;
  };

  const getRankBadgeColor = (rank: number) => {
    if (rank === 1) return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
    if (rank === 2) return 'bg-gray-500/20 text-gray-300 border-gray-500/30';
    if (rank === 3) return 'bg-amber-500/20 text-amber-600 border-amber-500/30';
    if (rank <= 10) return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
    return 'bg-gray-600/20 text-gray-400 border-gray-600/30';
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="text-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gold mx-auto mb-4"></div>
          <p className="text-gray-400">Loading Gold Diggers Club...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="text-center">
        <div className="flex items-center justify-center gap-3 mb-4">
          <div className="p-3 bg-gold-gradient rounded-full">
            <Sparkles className="h-8 w-8 text-black" />
          </div>
          <h1 className="text-4xl font-bold bg-gold-gradient bg-clip-text text-transparent">
            GOLD DIGGERS CLUB
          </h1>
          <div className="p-3 bg-gold-gradient rounded-full">
            <Trophy className="h-8 w-8 text-black" />
          </div>
        </div>
        <p className="text-xl text-gray-300 mb-2">Top 10 Recruiters Bonus Pool</p>
        <div className="text-5xl font-bold text-gold mb-4">$250,000</div>
        <p className="text-gray-400 max-w-2xl mx-auto">
          Exclusive reward pool for the top 10 individual referrers in the presale phase, 
          based purely on direct sales volume.
        </p>
      </div>

      {/* Presale Progress */}
      {presaleStats && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Target className="h-5 w-5 text-gold" />
              Presale Progress
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
              <div className="text-center">
                <div className="text-2xl font-bold text-white">{presaleStats.total_packs_sold.toLocaleString()}</div>
                <div className="text-sm text-gray-400">Packs Sold</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-white">{presaleStats.total_packs_available.toLocaleString()}</div>
                <div className="text-sm text-gray-400">Total Available</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-green-400">{formatCurrency(presaleStats.total_raised)}</div>
                <div className="text-sm text-gray-400">Total Raised</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-400">{presaleStats.presale_progress.toFixed(1)}%</div>
                <div className="text-sm text-gray-400">Complete</div>
              </div>
            </div>
            <Progress value={presaleStats.presale_progress} className="h-3 bg-gray-700" />
            <div className="flex items-center justify-between mt-2 text-sm text-gray-400">
              <span>Presale Progress</span>
              <span>
                {presaleStats.is_presale_active ? (
                  <Badge className="bg-green-500/20 text-green-400">Active</Badge>
                ) : (
                  <Badge className="bg-red-500/20 text-red-400">Ended</Badge>
                )}
              </span>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Bonus Structure */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Gift className="h-5 w-5 text-gold" />
            Bonus Distribution
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {bonusStructure.map((bonus, index) => (
              <div key={index} className={`${bonus.bgColor} border border-gray-600 rounded-lg p-4 text-center`}>
                <div className={`${bonus.color} mb-2 flex justify-center`}>
                  {bonus.icon}
                </div>
                <div className="text-white font-bold text-lg">{bonus.title}</div>
                <div className={`${bonus.color} font-bold text-xl`}>
                  {formatCurrency(bonus.amount)}
                  {bonus.rank === 4 && <div className="text-sm text-gray-400">each</div>}
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Rules */}
      <Card className="bg-blue-500/10 border-blue-500/30">
        <CardHeader>
          <CardTitle className="text-blue-400 flex items-center gap-2">
            <Zap className="h-5 w-5" />
            Competition Rules
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3 text-blue-300">
            <div className="flex items-start gap-2">
              <div className="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
              <div>
                <strong>Direct Sales Only:</strong> Only direct referrals count — no team or level-based volume.
              </div>
            </div>
            <div className="flex items-start gap-2">
              <div className="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
              <div>
                <strong>Minimum Qualification:</strong> Must have a minimum of $2,500 in direct referrals to qualify.
              </div>
            </div>
            <div className="flex items-start gap-2">
              <div className="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
              <div>
                <strong>Final Calculation:</strong> Calculated and finalized at the end of the presale (200,000 NFT packs sold or end of sale period).
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* User's Current Position */}
      {userRank && (
        <Card className="bg-gold/10 border-gold/30">
          <CardHeader>
            <CardTitle className="text-gold flex items-center gap-2">
              <Star className="h-5 w-5" />
              Your Current Position
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div className="text-center">
                <div className="text-3xl font-bold text-white mb-1">#{userRank.rank}</div>
                <div className="text-sm text-gray-400">Current Rank</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-green-400 mb-1">{formatCurrency(userRank.direct_sales_volume)}</div>
                <div className="text-sm text-gray-400">Direct Sales</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-blue-400 mb-1">{userRank.direct_referrals_count}</div>
                <div className="text-sm text-gray-400">Direct Referrals</div>
              </div>
              <div className="text-center">
                <div className="text-3xl font-bold text-gold mb-1">{formatCurrency(userRank.bonus_amount)}</div>
                <div className="text-sm text-gray-400">Potential Bonus</div>
              </div>
            </div>
            
            {!userRank.qualified && (
              <div className="mt-4 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                <div className="flex items-center gap-2 text-yellow-400">
                  <Timer className="h-4 w-4" />
                  <span className="font-medium">
                    Need {formatCurrency(2500 - userRank.direct_sales_volume)} more in direct sales to qualify
                  </span>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Leaderboard */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-white flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-gold" />
              Live Leaderboard
            </CardTitle>
            <div className="flex items-center gap-2">
              <span className="text-xs text-gray-400">
                Last updated: {lastUpdated.toLocaleTimeString()}
              </span>
              <Button
                size="sm"
                variant="ghost"
                onClick={fetchLeaderboardData}
                className="h-8 w-8 p-0"
              >
                <RefreshCw className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {leaderboard.length === 0 ? (
              <div className="text-center py-8 text-gray-400">
                <Users className="h-12 w-12 mx-auto mb-4 text-gray-500" />
                <p>No qualified participants yet. Be the first to reach $2,500 in direct sales!</p>
              </div>
            ) : (
              leaderboard.map((entry, index) => (
                <div
                  key={entry.user_id}
                  className={`flex items-center justify-between p-4 rounded-lg border ${
                    entry.rank <= 10 
                      ? 'bg-gray-700 border-gray-600' 
                      : 'bg-gray-800 border-gray-700'
                  } ${entry.user_id === user?.id?.toString() ? 'ring-2 ring-gold/50' : ''}`}
                >
                  <div className="flex items-center gap-4">
                    <div className="flex items-center gap-2">
                      {getRankIcon(entry.rank)}
                      <Badge className={getRankBadgeColor(entry.rank)}>
                        #{entry.rank}
                      </Badge>
                    </div>
                    
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 bg-gold-gradient rounded-full flex items-center justify-center">
                        {entry.profile_image ? (
                          <img 
                            src={entry.profile_image} 
                            alt={entry.username}
                            className="w-full h-full rounded-full object-cover"
                          />
                        ) : (
                          <span className="text-black font-bold">
                            {(entry.full_name || entry.username).charAt(0).toUpperCase()}
                          </span>
                        )}
                      </div>
                      
                      <div>
                        <div className="font-medium text-white">
                          {entry.full_name || entry.username}
                        </div>
                        <div className="text-sm text-gray-400">@{entry.username}</div>
                      </div>
                    </div>
                  </div>

                  <div className="text-right">
                    <div className="text-lg font-bold text-green-400">
                      {formatCurrency(entry.direct_sales_volume)}
                    </div>
                    <div className="text-sm text-gray-400">
                      {entry.direct_referrals_count} direct referrals
                    </div>
                    {entry.rank <= 10 && (
                      <div className="text-sm font-medium text-gold">
                        {formatCurrency(entry.bonus_amount)} bonus
                      </div>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default GoldDiggersClub;
