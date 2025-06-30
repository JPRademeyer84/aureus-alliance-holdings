import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
  RefreshCw,
  Plus,
  Edit,
  Play,
  Pause,
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
  prize_distribution: {
    first: number;
    second: number;
    third: number;
    participation: number;
  };
  rules: string;
  participants_count: number;
  total_sales_volume: number;
  created_at: string;
  updated_at: string;
}

interface CompetitionStats {
  total_competitions: number;
  active_competitions: number;
  total_prize_pool: number;
  total_participants: number;
  competitions_this_month: number;
}

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

const CompetitionManager: React.FC = () => {
  const [competitions, setCompetitions] = useState<Competition[]>([]);
  const [stats, setStats] = useState<CompetitionStats | null>(null);
  const [selectedCompetition, setSelectedCompetition] = useState<Competition | null>(null);
  const [participants, setParticipants] = useState<Participant[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchCompetitions();
  }, []);

  const fetchCompetitions = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('/api/competitions/list.php', {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setCompetitions(data.competitions || []);
        setStats(data.stats || null);
      } else {
        throw new Error(data.error || 'Failed to fetch competitions');
      }
    } catch (error) {
      console.error('Failed to fetch competitions:', error);
      toast({
        title: "Error",
        description: "Failed to load competition data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const fetchParticipants = async (competitionId: number) => {
    try {
      const response = await fetch(`/api/competitions/participants.php?competition_id=${competitionId}`, {
        credentials: 'include'
      });

      const data = await response.json();

      if (data.success) {
        setParticipants(data.participants || []);
      } else {
        throw new Error(data.error || 'Failed to fetch participants');
      }
    } catch (error) {
      console.error('Failed to fetch participants:', error);
      toast({
        title: "Error",
        description: "Failed to load participants",
        variant: "destructive"
      });
    }
  };

  const createCompetition = async (competitionData: any) => {
    try {
      const response = await fetch('/api/competitions/create.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(competitionData)
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Competition created successfully",
        });
        setShowCreateForm(false);
        fetchCompetitions();
      } else {
        throw new Error(data.error || 'Failed to create competition');
      }
    } catch (error) {
      console.error('Failed to create competition:', error);
      toast({
        title: "Error",
        description: "Failed to create competition",
        variant: "destructive"
      });
    }
  };

  const toggleCompetitionStatus = async (competitionId: number, isActive: boolean) => {
    try {
      const response = await fetch('/api/competitions/toggle.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          competition_id: competitionId,
          is_active: !isActive
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: `Competition ${!isActive ? 'activated' : 'deactivated'} successfully`,
        });
        fetchCompetitions();
      } else {
        throw new Error(data.error || 'Failed to toggle competition status');
      }
    } catch (error) {
      console.error('Failed to toggle competition status:', error);
      toast({
        title: "Error",
        description: "Failed to update competition status",
        variant: "destructive"
      });
    }
  };

  const getStatusBadge = (isActive: boolean) => {
    return (
      <Badge className={isActive ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600'}>
        {isActive ? 'Active' : 'Inactive'}
      </Badge>
    );
  };

  const getCriteriaIcon = (criteria: string) => {
    switch (criteria) {
      case 'sales_volume': return <DollarSign className="h-4 w-4 text-green-400" />;
      case 'sales_count': return <Target className="h-4 w-4 text-blue-400" />;
      case 'referrals': return <Users className="h-4 w-4 text-purple-400" />;
      default: return <Trophy className="h-4 w-4 text-gold" />;
    }
  };

  const getRankIcon = (rank: number) => {
    switch (rank) {
      case 1: return <Crown className="h-5 w-5 text-yellow-400" />;
      case 2: return <Award className="h-5 w-5 text-gray-400" />;
      case 3: return <Gift className="h-5 w-5 text-orange-400" />;
      default: return <Trophy className="h-5 w-5 text-gray-600" />;
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
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-white">Competition Management</h1>
          <p className="text-gray-400">Manage phase competitions with 15% prize allocation</p>
        </div>
        <div className="flex gap-2">
          <Button onClick={() => setShowCreateForm(true)} className="bg-gold hover:bg-gold/80">
            <Plus className="h-4 w-4 mr-2" />
            Create Competition
          </Button>
          <Button onClick={fetchCompetitions} variant="outline" className="border-gray-600">
            <RefreshCw className="h-4 w-4 mr-2" />
            Refresh
          </Button>
        </div>
      </div>

      {/* Stats Overview */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Trophy className="h-8 w-8 text-gold" />
                <div>
                  <p className="text-sm text-gray-400">Total Competitions</p>
                  <p className="text-2xl font-bold text-white">{stats.total_competitions}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Play className="h-8 w-8 text-green-400" />
                <div>
                  <p className="text-sm text-gray-400">Active</p>
                  <p className="text-2xl font-bold text-white">{stats.active_competitions}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <DollarSign className="h-8 w-8 text-yellow-400" />
                <div>
                  <p className="text-sm text-gray-400">Total Prize Pool</p>
                  <p className="text-2xl font-bold text-white">${stats.total_prize_pool.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Users className="h-8 w-8 text-blue-400" />
                <div>
                  <p className="text-sm text-gray-400">Participants</p>
                  <p className="text-2xl font-bold text-white">{stats.total_participants}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Calendar className="h-8 w-8 text-purple-400" />
                <div>
                  <p className="text-sm text-gray-400">This Month</p>
                  <p className="text-2xl font-bold text-white">{stats.competitions_this_month}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Competitions List */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Trophy className="h-5 w-5 text-gold" />
              Active Competitions
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {competitions.filter(c => c.is_active).map((competition) => (
                <div key={competition.id} className="bg-gray-700/50 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-3">
                    <div>
                      <h3 className="text-lg font-semibold text-white">{competition.name}</h3>
                      <p className="text-sm text-gray-400">Phase {competition.phase_name}</p>
                    </div>
                    {getStatusBadge(competition.is_active)}
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4 text-sm mb-3">
                    <div className="flex items-center gap-2">
                      <DollarSign className="h-4 w-4 text-yellow-400" />
                      <div>
                        <p className="text-gray-400">Prize Pool</p>
                        <p className="text-white font-medium">${competition.prize_pool.toLocaleString()}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Users className="h-4 w-4 text-blue-400" />
                      <div>
                        <p className="text-gray-400">Participants</p>
                        <p className="text-white font-medium">{competition.participants_count}</p>
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 mb-3">
                    {getCriteriaIcon(competition.winner_selection_criteria)}
                    <span className="text-sm text-gray-300 capitalize">
                      {competition.winner_selection_criteria.replace('_', ' ')} Based
                    </span>
                  </div>

                  <div className="flex gap-2">
                    <Button
                      onClick={() => {
                        setSelectedCompetition(competition);
                        fetchParticipants(competition.id);
                      }}
                      size="sm"
                      variant="outline"
                      className="border-gray-600"
                    >
                      View Leaderboard
                    </Button>
                    <Button
                      onClick={() => toggleCompetitionStatus(competition.id, competition.is_active)}
                      size="sm"
                      className="bg-red-600 hover:bg-red-700"
                    >
                      <Pause className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
              
              {competitions.filter(c => c.is_active).length === 0 && (
                <div className="text-center py-8">
                  <Trophy className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                  <p className="text-gray-400">No active competitions</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Leaderboard */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-gold" />
              {selectedCompetition ? `${selectedCompetition.name} - Leaderboard` : 'Select Competition'}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {selectedCompetition ? (
              <div className="space-y-3">
                {participants.length > 0 ? (
                  participants.slice(0, 10).map((participant, index) => (
                    <div key={participant.id} className="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
                      <div className="flex items-center gap-3">
                        {getRankIcon(participant.current_rank)}
                        <div>
                          <p className="text-white font-medium">{participant.username}</p>
                          <p className="text-sm text-gray-400">
                            {selectedCompetition.winner_selection_criteria === 'sales_volume' && `$${participant.total_volume.toLocaleString()}`}
                            {selectedCompetition.winner_selection_criteria === 'sales_count' && `${participant.sales_count} sales`}
                            {selectedCompetition.winner_selection_criteria === 'referrals' && `${participant.referrals_count} referrals`}
                          </p>
                        </div>
                      </div>
                      <div className="text-right">
                        <p className="text-white font-medium">#{participant.current_rank}</p>
                        {participant.is_winner && (
                          <Badge className="bg-gold hover:bg-gold/80 text-black">
                            Winner
                          </Badge>
                        )}
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="text-center py-8">
                    <Users className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                    <p className="text-gray-400">No participants yet</p>
                  </div>
                )}
              </div>
            ) : (
              <div className="text-center py-8">
                <Trophy className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                <p className="text-gray-400">Select a competition to view leaderboard</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* All Competitions */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">All Competitions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {competitions.map((competition) => (
              <div key={competition.id} className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="text-lg font-semibold text-white">{competition.name}</h3>
                      {getStatusBadge(competition.is_active)}
                    </div>
                    <p className="text-gray-300 text-sm mb-3">{competition.description}</p>
                    
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                      <div>
                        <p className="text-gray-400">Phase</p>
                        <p className="text-white font-medium">{competition.phase_name}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Prize Pool</p>
                        <p className="text-white font-medium">${competition.prize_pool.toLocaleString()}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Participants</p>
                        <p className="text-white font-medium">{competition.participants_count}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Criteria</p>
                        <p className="text-white font-medium capitalize">{competition.winner_selection_criteria.replace('_', ' ')}</p>
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex items-center gap-2 ml-4">
                    <Button
                      onClick={() => {
                        setSelectedCompetition(competition);
                        fetchParticipants(competition.id);
                      }}
                      variant="outline"
                      size="sm"
                      className="border-gray-600"
                    >
                      <TrendingUp className="h-4 w-4" />
                    </Button>
                    <Button
                      onClick={() => toggleCompetitionStatus(competition.id, competition.is_active)}
                      className={competition.is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'}
                    >
                      {competition.is_active ? <Pause className="h-4 w-4" /> : <Play className="h-4 w-4" />}
                    </Button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default CompetitionManager;
