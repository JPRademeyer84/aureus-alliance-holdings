import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Trophy,
  Users,
  DollarSign,
  Calendar,
  Target,
  Award,
  Crown,
  TrendingUp,
  Clock,
  Play,
  Gift
} from 'lucide-react';

interface Competition {
  id: number;
  phase_id: number;
  phase_name: string;
  name: string;
  description: string;
  prize_pool: number;
  start_date: string;
  end_date: string;
  is_active: boolean;
  winner_selection_criteria: 'sales_volume' | 'sales_count' | 'referrals';
  max_winners: number;
  participants_count: number;
  total_sales_volume: number;
  created_at: string;
}

interface CompetitionListProps {
  showActiveOnly?: boolean;
  phaseId?: number;
  onCompetitionSelect?: (competition: Competition) => void;
}

const CompetitionList: React.FC<CompetitionListProps> = ({
  showActiveOnly = false,
  phaseId,
  onCompetitionSelect
}) => {
  const [competitions, setCompetitions] = useState<Competition[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const { toast } = useToast();

  useEffect(() => {
    fetchCompetitions();
  }, [phaseId]);

  const fetchCompetitions = async () => {
    setIsLoading(true);
    try {
      let url = '/api/competitions/list.php';
      if (phaseId) {
        url += `?phase_id=${phaseId}`;
      }

      const response = await fetch(url, {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        let competitionList = data.data?.competitions || [];
        
        if (showActiveOnly) {
          competitionList = competitionList.filter((c: Competition) => c.is_active);
        }
        
        setCompetitions(competitionList);
      } else {
        throw new Error(data.error || 'Failed to fetch competitions');
      }
    } catch (error) {
      console.error('Failed to fetch competitions:', error);
      toast({
        title: "Error",
        description: "Failed to load competitions",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const joinCompetition = async (competitionId: number) => {
    try {
      const response = await fetch('/api/competitions/join.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          competition_id: competitionId
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Successfully joined competition!",
        });
        fetchCompetitions(); // Refresh the list
      } else {
        throw new Error(data.error || 'Failed to join competition');
      }
    } catch (error) {
      console.error('Failed to join competition:', error);
      toast({
        title: "Error",
        description: "Failed to join competition",
        variant: "destructive"
      });
    }
  };

  const getStatusBadge = (competition: Competition) => {
    if (!competition.is_active) {
      return <Badge className="bg-gray-500 hover:bg-gray-600">Inactive</Badge>;
    }

    const now = new Date();
    const endDate = new Date(competition.end_date);
    const daysRemaining = Math.ceil((endDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

    if (daysRemaining < 0) {
      return <Badge className="bg-red-500 hover:bg-red-600">Ended</Badge>;
    } else if (daysRemaining <= 3) {
      return <Badge className="bg-yellow-500 hover:bg-yellow-600">Ending Soon</Badge>;
    } else {
      return <Badge className="bg-green-500 hover:bg-green-600">Active</Badge>;
    }
  };

  const getCriteriaIcon = (criteria: string) => {
    switch (criteria) {
      case 'sales_volume': return <DollarSign className="h-4 w-4 text-green-400" />;
      case 'sales_count': return <Target className="h-4 w-4 text-blue-400" />;
      case 'referrals': return <Users className="h-4 w-4 text-purple-400" />;
      default: return <Trophy className="h-4 w-4 text-gold" />;
    }
  };

  const formatTimeRemaining = (endDate: string) => {
    const now = new Date();
    const end = new Date(endDate);
    const diff = end.getTime() - now.getTime();

    if (diff <= 0) return 'Ended';

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    if (days > 0) {
      return `${days}d ${hours}h remaining`;
    } else {
      return `${hours}h remaining`;
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {competitions.length > 0 ? (
        competitions.map((competition) => (
          <Card key={competition.id} className="bg-gray-800 border-gray-700 hover:border-gold/50 transition-colors">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h3 className="text-xl font-semibold text-white mb-1">{competition.name}</h3>
                  <p className="text-sm text-gray-400">
                    {competition.phase_name} • {formatTimeRemaining(competition.end_date)}
                  </p>
                </div>
                {getStatusBadge(competition)}
              </div>

              <p className="text-gray-300 text-sm mb-4">{competition.description}</p>

              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div className="flex items-center gap-2">
                  <DollarSign className="h-5 w-5 text-yellow-400" />
                  <div>
                    <p className="text-xs text-gray-400">Prize Pool</p>
                    <p className="text-white font-medium">${competition.prize_pool.toLocaleString()}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Users className="h-5 w-5 text-blue-400" />
                  <div>
                    <p className="text-xs text-gray-400">Participants</p>
                    <p className="text-white font-medium">{competition.participants_count}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  {getCriteriaIcon(competition.winner_selection_criteria)}
                  <div>
                    <p className="text-xs text-gray-400">Criteria</p>
                    <p className="text-white font-medium capitalize">
                      {competition.winner_selection_criteria.replace('_', ' ')}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Award className="h-5 w-5 text-purple-400" />
                  <div>
                    <p className="text-xs text-gray-400">Max Winners</p>
                    <p className="text-white font-medium">{competition.max_winners}</p>
                  </div>
                </div>
              </div>

              <div className="flex gap-2">
                {competition.is_active && (
                  <Button
                    onClick={() => joinCompetition(competition.id)}
                    size="sm"
                    className="bg-gold hover:bg-gold/80 text-black"
                  >
                    <Trophy className="h-4 w-4 mr-2" />
                    Join Competition
                  </Button>
                )}
                <Button
                  onClick={() => onCompetitionSelect?.(competition)}
                  size="sm"
                  variant="outline"
                  className="border-gray-600"
                >
                  <TrendingUp className="h-4 w-4 mr-2" />
                  View Leaderboard
                </Button>
              </div>
            </CardContent>
          </Card>
        ))
      ) : (
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-8 text-center">
            <Trophy className="h-12 w-12 text-gray-600 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-white mb-2">No Competitions Available</h3>
            <p className="text-gray-400">
              {showActiveOnly 
                ? "There are no active competitions at the moment."
                : "No competitions have been created yet."
              }
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default CompetitionList;
