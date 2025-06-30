import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { 
  Trophy, 
  Share2, 
  TrendingUp, 
  Users,
  RefreshCw,
  Crown,
  Medal,
  Award,
  BarChart3,
  ExternalLink
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface LeaderboardEntry {
  username: string;
  full_name: string;
  total_shares: number;
  total_clicks: number;
  total_conversions: number;
  platforms_used: number;
}

const SocialSharingLeaderboard: React.FC = () => {
  const [leaderboard, setLeaderboard] = useState<LeaderboardEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  useEffect(() => {
    loadLeaderboard();
  }, []);

  const loadLeaderboard = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${ApiConfig.baseUrl}/social/share-tracking.php?action=leaderboard`);
      const data = await response.json();
      
      if (data.success) {
        setLeaderboard(data.leaderboard || []);
        setLastUpdated(new Date());
      }
    } catch (error) {
      console.error('Failed to load sharing leaderboard:', error);
    } finally {
      setLoading(false);
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

  const getRankBadge = (rank: number) => {
    if (rank <= 3) {
      const colors = ['bg-yellow-500', 'bg-gray-400', 'bg-amber-600'];
      return <Badge className={`${colors[rank - 1]} text-white`}>#{rank}</Badge>;
    }
    return <Badge variant="outline">#{rank}</Badge>;
  };

  const calculateEngagementRate = (clicks: number, shares: number) => {
    if (shares === 0) return 0;
    return ((clicks / shares) * 100).toFixed(1);
  };

  const calculateConversionRate = (conversions: number, clicks: number) => {
    if (clicks === 0) return 0;
    return ((conversions / clicks) * 100).toFixed(1);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <RefreshCw className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading leaderboard...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Social Sharing Leaderboard</h2>
          <p className="text-muted-foreground">
            Top performers in social media sharing and engagement (Last 30 days)
          </p>
        </div>
        
        <Button onClick={loadLeaderboard} variant="outline">
          <RefreshCw className="w-4 h-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Summary Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Users className="w-4 h-4 text-blue-500" />
              <div>
                <p className="text-sm text-muted-foreground">Active Sharers</p>
                <p className="text-2xl font-bold">{leaderboard.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Share2 className="w-4 h-4 text-green-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Shares</p>
                <p className="text-2xl font-bold">
                  {leaderboard.reduce((sum, entry) => sum + entry.total_shares, 0)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <BarChart3 className="w-4 h-4 text-purple-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Clicks</p>
                <p className="text-2xl font-bold">
                  {leaderboard.reduce((sum, entry) => sum + entry.total_clicks, 0)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <TrendingUp className="w-4 h-4 text-orange-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Conversions</p>
                <p className="text-2xl font-bold">
                  {leaderboard.reduce((sum, entry) => sum + entry.total_conversions, 0)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Leaderboard Table */}
      <Card>
        <CardHeader>
          <CardTitle>Top Social Media Sharers</CardTitle>
          <CardDescription>
            Ranked by total shares, clicks, and conversions in the last 30 days
            {lastUpdated && (
              <span className="block text-xs mt-1">
                Last updated: {lastUpdated.toLocaleString()}
              </span>
            )}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {leaderboard.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <Share2 className="w-12 h-12 mx-auto mb-4 opacity-50" />
              <p>No sharing activity found in the last 30 days</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Rank</TableHead>
                  <TableHead>User</TableHead>
                  <TableHead>Shares</TableHead>
                  <TableHead>Clicks</TableHead>
                  <TableHead>Conversions</TableHead>
                  <TableHead>Platforms</TableHead>
                  <TableHead>Engagement Rate</TableHead>
                  <TableHead>Conversion Rate</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {leaderboard.map((entry, index) => {
                  const rank = index + 1;
                  const engagementRate = calculateEngagementRate(entry.total_clicks, entry.total_shares);
                  const conversionRate = calculateConversionRate(entry.total_conversions, entry.total_clicks);
                  
                  return (
                    <TableRow key={entry.username} className={rank <= 3 ? 'bg-muted/50' : ''}>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          {getRankIcon(rank)}
                          {getRankBadge(rank)}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div>
                          <div className="font-medium">{entry.username}</div>
                          {entry.full_name && (
                            <div className="text-sm text-muted-foreground">{entry.full_name}</div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="font-bold text-blue-600">{entry.total_shares}</div>
                      </TableCell>
                      <TableCell>
                        <div className="font-bold text-green-600">{entry.total_clicks}</div>
                      </TableCell>
                      <TableCell>
                        <div className="font-bold text-purple-600">{entry.total_conversions}</div>
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline">{entry.platforms_used} platforms</Badge>
                      </TableCell>
                      <TableCell>
                        <div className="text-sm">
                          <span className={`font-medium ${parseFloat(engagementRate) > 50 ? 'text-green-600' : parseFloat(engagementRate) > 25 ? 'text-yellow-600' : 'text-red-600'}`}>
                            {engagementRate}%
                          </span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="text-sm">
                          <span className={`font-medium ${parseFloat(conversionRate) > 10 ? 'text-green-600' : parseFloat(conversionRate) > 5 ? 'text-yellow-600' : 'text-red-600'}`}>
                            {conversionRate}%
                          </span>
                        </div>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Performance Insights */}
      {leaderboard.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Performance Insights</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="p-4 bg-muted rounded-lg">
                <h4 className="font-medium mb-2">Top Performer</h4>
                <div className="flex items-center gap-2">
                  <Crown className="w-4 h-4 text-yellow-500" />
                  <span className="font-bold">{leaderboard[0]?.username}</span>
                  <span className="text-sm text-muted-foreground">
                    with {leaderboard[0]?.total_shares} shares
                  </span>
                </div>
              </div>
              
              <div className="p-4 bg-muted rounded-lg">
                <h4 className="font-medium mb-2">Best Engagement</h4>
                {(() => {
                  const bestEngagement = leaderboard.reduce((best, current) => {
                    const currentRate = parseFloat(calculateEngagementRate(current.total_clicks, current.total_shares));
                    const bestRate = parseFloat(calculateEngagementRate(best.total_clicks, best.total_shares));
                    return currentRate > bestRate ? current : best;
                  }, leaderboard[0]);
                  
                  return (
                    <div className="flex items-center gap-2">
                      <TrendingUp className="w-4 h-4 text-green-500" />
                      <span className="font-bold">{bestEngagement?.username}</span>
                      <span className="text-sm text-muted-foreground">
                        with {calculateEngagementRate(bestEngagement?.total_clicks || 0, bestEngagement?.total_shares || 0)}% engagement
                      </span>
                    </div>
                  );
                })()}
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default SocialSharingLeaderboard;
