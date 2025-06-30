import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import {
  Play,
  Pause,
  Settings,
  BarChart3,
  DollarSign,
  Users,
  Trophy,
  Heart,
  Pickaxe,
  Monitor,
  RefreshCw,
  Edit,
  Save,
  X
} from 'lucide-react';

interface Phase {
  id: number;
  phase_number: number;
  name: string;
  description: string;
  is_active: boolean;
  start_date: string | null;
  end_date: string | null;
  total_packages_available: number;
  packages_sold: number;
  total_revenue: number;
  commission_paid: number;
  competition_pool: number;
  npo_fund: number;
  platform_fund: number;
  mine_fund: number;
  revenue_distribution: {
    commission: number;
    competition: number;
    platform: number;
    npo: number;
    mine: number;
  };
  created_at: string;
  updated_at: string;
}

interface PhaseStats {
  total_phases: number;
  active_phases: number;
  total_revenue: number;
  total_packages_sold: number;
  current_phase: Phase | null;
}

const PhaseManager: React.FC = () => {
  const [phases, setPhases] = useState<Phase[]>([]);
  const [stats, setStats] = useState<PhaseStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [editingPhase, setEditingPhase] = useState<Phase | null>(null);
  const [isProcessing, setIsProcessing] = useState<number | null>(null);
  const { toast } = useToast();

  useEffect(() => {
    fetchPhases();
  }, []);

  const fetchPhases = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('/api/admin/phases.php', {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setPhases(data.phases || []);
        setStats(data.stats || null);
      } else {
        throw new Error(data.error || 'Failed to fetch phases');
      }
    } catch (error) {
      console.error('Failed to fetch phases:', error);
      toast({
        title: "Error",
        description: "Failed to load phase data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const togglePhaseStatus = async (phaseId: number, currentStatus: boolean) => {
    setIsProcessing(phaseId);
    try {
      const response = await fetch('/api/admin/phases.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'toggle_status',
          phase_id: phaseId,
          is_active: !currentStatus
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: `Phase ${!currentStatus ? 'activated' : 'deactivated'} successfully`,
        });
        fetchPhases(); // Refresh data
      } else {
        throw new Error(data.error || 'Failed to update phase status');
      }
    } catch (error) {
      console.error('Failed to toggle phase status:', error);
      toast({
        title: "Error",
        description: "Failed to update phase status",
        variant: "destructive"
      });
    } finally {
      setIsProcessing(null);
    }
  };

  const updatePhase = async (phase: Phase) => {
    setIsProcessing(phase.id);
    try {
      const response = await fetch('/api/admin/phases.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'update',
          phase_id: phase.id,
          name: phase.name,
          description: phase.description,
          total_packages_available: phase.total_packages_available
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Phase updated successfully",
        });
        setEditingPhase(null);
        fetchPhases(); // Refresh data
      } else {
        throw new Error(data.error || 'Failed to update phase');
      }
    } catch (error) {
      console.error('Failed to update phase:', error);
      toast({
        title: "Error",
        description: "Failed to update phase",
        variant: "destructive"
      });
    } finally {
      setIsProcessing(null);
    }
  };

  const getStatusBadge = (isActive: boolean) => {
    return (
      <Badge className={isActive ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600'}>
        {isActive ? 'Active' : 'Inactive'}
      </Badge>
    );
  };

  const getRevenueIcon = (type: string) => {
    switch (type) {
      case 'commission': return <DollarSign className="h-4 w-4 text-green-400" />;
      case 'competition': return <Trophy className="h-4 w-4 text-yellow-400" />;
      case 'npo': return <Heart className="h-4 w-4 text-purple-400" />;
      case 'platform': return <Monitor className="h-4 w-4 text-blue-400" />;
      case 'mine': return <Pickaxe className="h-4 w-4 text-orange-400" />;
      default: return <BarChart3 className="h-4 w-4 text-gray-400" />;
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
          <h1 className="text-3xl font-bold text-white">Phase Management</h1>
          <p className="text-gray-400">Manage 20-phase system with manual activation controls</p>
        </div>
        <Button onClick={fetchPhases} variant="outline" className="border-gray-600">
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Stats Overview */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Settings className="h-8 w-8 text-blue-400" />
                <div>
                  <p className="text-sm text-gray-400">Total Phases</p>
                  <p className="text-2xl font-bold text-white">{stats.total_phases}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Play className="h-8 w-8 text-green-400" />
                <div>
                  <p className="text-sm text-gray-400">Active Phases</p>
                  <p className="text-2xl font-bold text-white">{stats.active_phases}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <DollarSign className="h-8 w-8 text-gold" />
                <div>
                  <p className="text-sm text-gray-400">Total Revenue</p>
                  <p className="text-2xl font-bold text-white">${stats.total_revenue.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Users className="h-8 w-8 text-purple-400" />
                <div>
                  <p className="text-sm text-gray-400">Packages Sold</p>
                  <p className="text-2xl font-bold text-white">{stats.total_packages_sold.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Current Active Phase */}
      {stats?.current_phase && (
        <Card className="bg-gradient-to-r from-green-500/10 to-green-600/10 border-green-500/30">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Play className="h-5 w-5 text-green-400" />
              Current Active Phase
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <h3 className="text-lg font-semibold text-white">{stats.current_phase.name}</h3>
                <p className="text-gray-300">{stats.current_phase.description}</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-green-400">{stats.current_phase.packages_sold}</p>
                <p className="text-sm text-gray-400">Packages Sold</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-green-400">${stats.current_phase.total_revenue.toLocaleString()}</p>
                <p className="text-sm text-gray-400">Revenue Generated</p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Phases List */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">All Phases (1-20)</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {phases.map((phase) => (
              <div key={phase.id} className="bg-gray-700/50 rounded-lg p-4">
                {editingPhase?.id === phase.id ? (
                  // Edit Mode
                  <div className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="text-sm text-gray-400">Phase Name</label>
                        <Input
                          value={editingPhase.name}
                          onChange={(e) => setEditingPhase({...editingPhase, name: e.target.value})}
                          className="bg-gray-800 border-gray-600 text-white"
                        />
                      </div>
                      <div>
                        <label className="text-sm text-gray-400">Available Packages</label>
                        <Input
                          type="number"
                          value={editingPhase.total_packages_available}
                          onChange={(e) => setEditingPhase({...editingPhase, total_packages_available: parseInt(e.target.value) || 0})}
                          className="bg-gray-800 border-gray-600 text-white"
                        />
                      </div>
                    </div>
                    <div>
                      <label className="text-sm text-gray-400">Description</label>
                      <Textarea
                        value={editingPhase.description}
                        onChange={(e) => setEditingPhase({...editingPhase, description: e.target.value})}
                        className="bg-gray-800 border-gray-600 text-white"
                        rows={2}
                      />
                    </div>
                    <div className="flex gap-2">
                      <Button
                        onClick={() => updatePhase(editingPhase)}
                        disabled={isProcessing === phase.id}
                        className="bg-green-600 hover:bg-green-700"
                      >
                        <Save className="h-4 w-4 mr-2" />
                        Save
                      </Button>
                      <Button
                        onClick={() => setEditingPhase(null)}
                        variant="outline"
                        className="border-gray-600"
                      >
                        <X className="h-4 w-4 mr-2" />
                        Cancel
                      </Button>
                    </div>
                  </div>
                ) : (
                  // View Mode
                  <div className="flex items-center justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-lg font-semibold text-white">
                          Phase {phase.phase_number}: {phase.name}
                        </h3>
                        {getStatusBadge(phase.is_active)}
                      </div>
                      <p className="text-gray-300 text-sm mb-3">{phase.description}</p>
                      
                      {/* Phase Statistics */}
                      <div className="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                        <div className="flex items-center gap-2">
                          {getRevenueIcon('commission')}
                          <div>
                            <p className="text-gray-400">Commission</p>
                            <p className="text-white font-medium">${phase.commission_paid.toLocaleString()}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {getRevenueIcon('competition')}
                          <div>
                            <p className="text-gray-400">Competition</p>
                            <p className="text-white font-medium">${phase.competition_pool.toLocaleString()}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {getRevenueIcon('npo')}
                          <div>
                            <p className="text-gray-400">NPO Fund</p>
                            <p className="text-white font-medium">${phase.npo_fund.toLocaleString()}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {getRevenueIcon('platform')}
                          <div>
                            <p className="text-gray-400">Platform</p>
                            <p className="text-white font-medium">${phase.platform_fund.toLocaleString()}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          {getRevenueIcon('mine')}
                          <div>
                            <p className="text-gray-400">Mine Setup</p>
                            <p className="text-white font-medium">${phase.mine_fund.toLocaleString()}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-2 ml-4">
                      <Button
                        onClick={() => setEditingPhase(phase)}
                        variant="outline"
                        size="sm"
                        className="border-gray-600"
                      >
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button
                        onClick={() => togglePhaseStatus(phase.id, phase.is_active)}
                        disabled={isProcessing === phase.id}
                        className={phase.is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'}
                      >
                        {phase.is_active ? <Pause className="h-4 w-4" /> : <Play className="h-4 w-4" />}
                      </Button>
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default PhaseManager;
