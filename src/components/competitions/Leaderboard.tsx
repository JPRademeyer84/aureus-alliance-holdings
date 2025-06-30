import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Trophy,
  Crown,
  Award,
  Medal,
  TrendingUp,
  DollarSign,
  Target,
  Users,
  RefreshCw
} from 'lucide-react';

interface Participant {
  id: number;
  user_id: number;
  username: string;
  sales_count: number;
  total_volume: number;
  referrals_count: number;
  current_rank: number;
  prize_amount: number;
  is_winner: boolean;
  joined_at: string;
}

interface Competition {
  id: number;
  name: string;
  winner_selection_criteria: 'sales_volume' | 'sales_count' | 'referrals';
  prize_pool: number;
  is_active: boolean;
}

interface LeaderboardProps {
  competition: Competition;
  refreshInterval?: number;
}

const Leaderboard: React.FC<LeaderboardProps> = ({ 
  competition, 
  refreshInterval = 30000 // 30 seconds default
}) => {
  const [participants, setParticipants] = useState<Participant[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date>(new Date());
  const { toast } = useToast();

  useEffect(() => {
    fetchLeaderboard();
    
    // Set up auto-refresh for active competitions
    let interval: NodeJS.Timeout;
    if (competition.is_active && refreshInterval > 0) {
      interval = setInterval(fetchLeaderboard, refreshInterval);
    }
    
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [competition.id, refreshInterval]);

  const fetchLeaderboard = async () => {
    try {
      const response = await fetch(`/api/competitions/leaderboard.php?competition_id=${competition.id}`, {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setParticipants(data.participants || []);
        setLastUpdated(new Date());
      } else {
        throw new Error(data.error || 'Failed to fetch leaderboard');
      }
    } catch (error) {
      console.error('Failed to fetch leaderboard:', error);
      if (isLoading) {
        toast({
          title: "Error",
          description: "Failed to load leaderboard",
          variant: "destructive"
        });
      }
    } finally {
      setIsLoading(false);
    }
  };

  const getRankIcon = (rank: number) => {
    switch (rank) {
      case 1:
        return <Crown className="h-6 w-6 text-yellow-400" />;
      case 2:
        return <Award className="h-6 w-6 text-gray-400" />;
      case 3:
        return <Medal className="h-6 w-6 text-orange-400" />;
      default:
        return (
          <div className="h-6 w-6 rounded-full bg-gray-600 flex items-center justify-center text-xs font-bold text-white">
            {rank}
          </div>
        );
    }
  };

  const getRankBadge = (rank: number) => {
    if (rank === 1) {
      return <Badge className="bg-yellow-500 hover:bg-yellow-600 text-black">1st Place</Badge>;
    } else if (rank === 2) {
      return <Badge className="bg-gray-400 hover:bg-gray-500 text-black">2nd Place</Badge>;
    } else if (rank === 3) {
      return <Badge className="bg-orange-500 hover:bg-orange-600 text-black">3rd Place</Badge>;
    }
    return null;
  };

  const getScoreValue = (participant: Participant) => {
    switch (competition.winner_selection_criteria) {
      case 'sales_volume':
        return `$${(participant.total_volume || 0).toLocaleString()}`;
      case 'sales_count':
        return `${participant.sales_count || 0} sales`;
      case 'referrals':
        return `${participant.referrals_count || 0} referrals`;
      default:
        return '0';
    }
  };

  const getScoreIcon = () => {
    switch (competition.winner_selection_criteria) {
      case 'sales_volume':
        return <DollarSign className="h-4 w-4 text-green-400" />;
      case 'sales_count':
        return <Target className="h-4 w-4 text-blue-400" />;
      case 'referrals':
        return <Users className="h-4 w-4 text-purple-400" />;
      default:
        return <Trophy className="h-4 w-4 text-gold" />;
    }
  };

  const getCriteriaLabel = () => {
    switch (competition.winner_selection_criteria) {
      case 'sales_volume':
        return 'Sales Volume';
      case 'sales_count':
        return 'Sales Count';
      case 'referrals':
        return 'Referrals';
      default:
        return 'Score';
    }
  };

  if (isLoading) {
    return (
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-8">
          <div className="flex items-center justify-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-gray-800 border-gray-700">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-white flex items-center gap-2">
            <Trophy className="h-5 w-5 text-gold" />
            {competition.name} - Leaderboard
          </CardTitle>
          <div className="flex items-center gap-2 text-sm text-gray-400">
            <RefreshCw className="h-4 w-4" />
            Updated: {lastUpdated.toLocaleTimeString()}
          </div>
        </div>
        <div className="flex items-center gap-4 text-sm text-gray-400">
          <div className="flex items-center gap-1">
            {getScoreIcon()}
            <span>Ranked by {getCriteriaLabel()}</span>
          </div>
          <div className="flex items-center gap-1">
            <DollarSign className="h-4 w-4 text-yellow-400" />
            <span>Prize Pool: ${(competition.prize_pool || 0).toLocaleString()}</span>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        {participants.length > 0 ? (
          <div className="space-y-3">
            {participants.map((participant, index) => (
              <div
                key={participant.id}
                className={`flex items-center justify-between p-4 rounded-lg transition-colors ${
                  participant.current_rank <= 3
                    ? 'bg-gradient-to-r from-gold/10 to-gold/5 border border-gold/20'
                    : 'bg-gray-700/50 hover:bg-gray-700/70'
                }`}
              >
                <div className="flex items-center gap-4">
                  {getRankIcon(participant.current_rank)}
                  <div>
                    <div className="flex items-center gap-2">
                      <h3 className="text-white font-medium">{participant.username}</h3>
                      {getRankBadge(participant.current_rank)}
                      {participant.is_winner && (
                        <Badge className="bg-gold hover:bg-gold/80 text-black">
                          Winner
                        </Badge>
                      )}
                    </div>
                    <p className="text-sm text-gray-400">
                      Joined {new Date(participant.joined_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>

                <div className="text-right">
                  <div className="flex items-center gap-2 justify-end mb-1">
                    {getScoreIcon()}
                    <span className="text-white font-bold text-lg">
                      {getScoreValue(participant)}
                    </span>
                  </div>
                  {participant.prize_amount > 0 && (
                    <p className="text-sm text-green-400">
                      Prize: ${participant.prize_amount.toLocaleString()}
                    </p>
                  )}
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8">
            <Trophy className="h-12 w-12 text-gray-600 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-white mb-2">No Participants Yet</h3>
            <p className="text-gray-400">Be the first to join this competition!</p>
          </div>
        )}

        {/* Competition Status */}
        <div className="mt-6 pt-4 border-t border-gray-700">
          <div className="flex items-center justify-between text-sm">
            <span className="text-gray-400">Competition Status:</span>
            <Badge className={competition.is_active ? 'bg-green-500' : 'bg-gray-500'}>
              {competition.is_active ? 'Active' : 'Ended'}
            </Badge>
          </div>
          {competition.is_active && (
            <p className="text-xs text-gray-500 mt-2">
              Leaderboard updates automatically every {refreshInterval / 1000} seconds
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default Leaderboard;
