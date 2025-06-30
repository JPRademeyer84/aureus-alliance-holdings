import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { 
  Trophy, 
  DollarSign, 
  Users, 
  Calculator,
  Gift,
  CheckCircle,
  Clock,
  AlertTriangle,
  RefreshCw,
  Crown,
  Medal,
  Award
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface Prize {
  id: string;
  rank: number;
  user_id: string;
  username: string;
  direct_sales_volume: number;
  direct_referrals_count: number;
  prize_amount: number;
  status: 'calculated' | 'distributed' | 'cancelled';
  calculated_at?: string;
  distributed_at?: string;
}

interface Winner {
  rank: number;
  user_id: string;
  username: string;
  full_name: string;
  direct_sales_volume: number;
  direct_referrals_count: number;
  bonus_amount: number;
  qualified: boolean;
}

const PrizeManager: React.FC = () => {
  const [prizes, setPrizes] = useState<Prize[]>([]);
  const [winners, setWinners] = useState<Winner[]>([]);
  const [selectedPrizes, setSelectedPrizes] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [calculating, setCalculating] = useState(false);
  const [distributing, setDistributing] = useState(false);
  const [showCalculateDialog, setShowCalculateDialog] = useState(false);
  const [showDistributeDialog, setShowDistributeDialog] = useState(false);
  const [presaleStatus, setPresaleStatus] = useState<any>(null);
  const { toast } = useToast();

  useEffect(() => {
    loadPrizeData();
  }, []);

  const loadPrizeData = async () => {
    try {
      setLoading(true);
      
      // Load current prize status
      const statusResponse = await fetch(`${ApiConfig.baseUrl}/leaderboard/prize-distribution.php?action=status`);
      const statusData = await statusResponse.json();
      
      if (statusData.success) {
        setPrizes(statusData.prizes || []);
        setPresaleStatus(statusData.presale_status);
      }
      
      // Load current winners
      const winnersResponse = await fetch(`${ApiConfig.baseUrl}/leaderboard/prize-distribution.php?action=winners`);
      const winnersData = await winnersResponse.json();
      
      if (winnersData.success) {
        setWinners(winnersData.winners || []);
      }
      
    } catch (error) {
      console.error('Error loading prize data:', error);
      toast({
        title: 'Error',
        description: 'Failed to load prize data',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const calculateWinners = async () => {
    try {
      setCalculating(true);
      
      const response = await fetch(`${ApiConfig.baseUrl}/leaderboard/prize-distribution.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          admin_id: 'admin' // Should be actual admin ID
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: `Winners calculated successfully. ${data.winners_count} qualified winners found.`,
        });
        
        loadPrizeData();
        setShowCalculateDialog(false);
      } else {
        throw new Error(data.error || 'Failed to calculate winners');
      }
    } catch (error) {
      console.error('Error calculating winners:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to calculate winners',
        variant: 'destructive',
      });
    } finally {
      setCalculating(false);
    }
  };

  const distributePrizes = async () => {
    try {
      setDistributing(true);
      
      const response = await fetch(`${ApiConfig.baseUrl}/leaderboard/prize-distribution.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          admin_id: 'admin', // Should be actual admin ID
          prize_ids: selectedPrizes
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: `Successfully distributed ${data.distributed_count} prizes`,
        });
        
        loadPrizeData();
        setSelectedPrizes([]);
        setShowDistributeDialog(false);
      } else {
        throw new Error(data.error || 'Failed to distribute prizes');
      }
    } catch (error) {
      console.error('Error distributing prizes:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to distribute prizes',
        variant: 'destructive',
      });
    } finally {
      setDistributing(false);
    }
  };

  const getRankIcon = (rank: number) => {
    switch (rank) {
      case 1:
        return <Crown className="w-5 h-5 text-yellow-500" />;
      case 2:
        return <Medal className="w-5 h-5 text-gray-400" />;
      case 3:
        return <Award className="w-5 h-5 text-amber-600" />;
      default:
        return <Trophy className="w-5 h-5 text-blue-500" />;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'calculated':
        return <Badge variant="secondary"><Clock className="w-3 h-3 mr-1" />Calculated</Badge>;
      case 'distributed':
        return <Badge variant="default"><CheckCircle className="w-3 h-3 mr-1" />Distributed</Badge>;
      case 'cancelled':
        return <Badge variant="destructive"><AlertTriangle className="w-3 h-3 mr-1" />Cancelled</Badge>;
      default:
        return <Badge variant="outline">Unknown</Badge>;
    }
  };

  const handlePrizeSelection = (prizeId: string, checked: boolean) => {
    if (checked) {
      setSelectedPrizes(prev => [...prev, prizeId]);
    } else {
      setSelectedPrizes(prev => prev.filter(id => id !== prizeId));
    }
  };

  const selectAllCalculatedPrizes = () => {
    const calculatedPrizes = prizes.filter(p => p.status === 'calculated').map(p => p.id);
    setSelectedPrizes(calculatedPrizes);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <RefreshCw className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading prize data...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Gold Diggers Club Prize Manager</h2>
          <p className="text-muted-foreground">
            Manage and distribute the $250,000 bonus pool to top 10 recruiters
          </p>
        </div>
        
        <Button onClick={loadPrizeData} variant="outline">
          <RefreshCw className="w-4 h-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <DollarSign className="w-4 h-4 text-green-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Prize Pool</p>
                <p className="text-2xl font-bold">$250,000</p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Trophy className="w-4 h-4 text-yellow-500" />
              <div>
                <p className="text-sm text-muted-foreground">Calculated Winners</p>
                <p className="text-2xl font-bold">{prizes.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Gift className="w-4 h-4 text-blue-500" />
              <div>
                <p className="text-sm text-muted-foreground">Distributed</p>
                <p className="text-2xl font-bold">
                  {prizes.filter(p => p.status === 'distributed').length}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Users className="w-4 h-4 text-purple-500" />
              <div>
                <p className="text-sm text-muted-foreground">Current Participants</p>
                <p className="text-2xl font-bold">{winners.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Action Buttons */}
      <div className="flex gap-4">
        <Dialog open={showCalculateDialog} onOpenChange={setShowCalculateDialog}>
          <DialogTrigger asChild>
            <Button>
              <Calculator className="w-4 h-4 mr-2" />
              Calculate Winners
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Calculate Final Winners</DialogTitle>
              <DialogDescription>
                This will calculate the final winners based on current leaderboard standings.
                This action will overwrite any existing calculations.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-sm">
                  <strong>Current qualified participants:</strong> {winners.filter(w => w.qualified).length}
                </p>
                <p className="text-sm">
                  <strong>Total prize amount:</strong> ${winners.filter(w => w.qualified && w.rank <= 10).reduce((sum, w) => sum + w.bonus_amount, 0).toLocaleString()}
                </p>
              </div>
              <div className="flex gap-4">
                <Button onClick={calculateWinners} disabled={calculating} className="flex-1">
                  {calculating ? 'Calculating...' : 'Calculate Winners'}
                </Button>
                <Button variant="outline" onClick={() => setShowCalculateDialog(false)} className="flex-1">
                  Cancel
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>

        {prizes.filter(p => p.status === 'calculated').length > 0 && (
          <>
            <Button onClick={selectAllCalculatedPrizes} variant="outline">
              Select All Calculated
            </Button>
            
            <Dialog open={showDistributeDialog} onOpenChange={setShowDistributeDialog}>
              <DialogTrigger asChild>
                <Button disabled={selectedPrizes.length === 0}>
                  <Gift className="w-4 h-4 mr-2" />
                  Distribute Selected ({selectedPrizes.length})
                </Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Distribute Prizes</DialogTitle>
                  <DialogDescription>
                    This will distribute the selected prizes to winners' commission balances.
                    This action cannot be undone.
                  </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                  <div className="p-4 bg-muted rounded-lg">
                    <p className="text-sm">
                      <strong>Selected prizes:</strong> {selectedPrizes.length}
                    </p>
                    <p className="text-sm">
                      <strong>Total amount:</strong> ${prizes.filter(p => selectedPrizes.includes(p.id)).reduce((sum, p) => sum + p.prize_amount, 0).toLocaleString()}
                    </p>
                  </div>
                  <div className="flex gap-4">
                    <Button onClick={distributePrizes} disabled={distributing} className="flex-1">
                      {distributing ? 'Distributing...' : 'Distribute Prizes'}
                    </Button>
                    <Button variant="outline" onClick={() => setShowDistributeDialog(false)} className="flex-1">
                      Cancel
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </>
        )}
      </div>

      {/* Prizes Table */}
      <Card>
        <CardHeader>
          <CardTitle>Prize Distribution Status</CardTitle>
          <CardDescription>
            Current status of Gold Diggers Club prize distribution
          </CardDescription>
        </CardHeader>
        <CardContent>
          {prizes.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <Trophy className="w-12 h-12 mx-auto mb-4 opacity-50" />
              <p>No prizes calculated yet. Click "Calculate Winners" to begin.</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Select</TableHead>
                  <TableHead>Rank</TableHead>
                  <TableHead>Winner</TableHead>
                  <TableHead>Sales Volume</TableHead>
                  <TableHead>Referrals</TableHead>
                  <TableHead>Prize Amount</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {prizes.map((prize) => (
                  <TableRow key={prize.id}>
                    <TableCell>
                      <Checkbox
                        checked={selectedPrizes.includes(prize.id)}
                        onCheckedChange={(checked) => handlePrizeSelection(prize.id, checked as boolean)}
                        disabled={prize.status !== 'calculated'}
                      />
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        {getRankIcon(prize.rank)}
                        <span className="font-bold">#{prize.rank}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        <div className="font-medium">{prize.username}</div>
                        <div className="text-sm text-muted-foreground">ID: {prize.user_id}</div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="font-medium">${prize.direct_sales_volume.toLocaleString()}</div>
                    </TableCell>
                    <TableCell>
                      <div className="font-medium">{prize.direct_referrals_count}</div>
                    </TableCell>
                    <TableCell>
                      <div className="font-bold text-green-600">${prize.prize_amount.toLocaleString()}</div>
                    </TableCell>
                    <TableCell>{getStatusBadge(prize.status)}</TableCell>
                    <TableCell>
                      <div className="text-sm">
                        {prize.calculated_at && (
                          <div>Calc: {new Date(prize.calculated_at).toLocaleDateString()}</div>
                        )}
                        {prize.distributed_at && (
                          <div>Dist: {new Date(prize.distributed_at).toLocaleDateString()}</div>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default PrizeManager;
