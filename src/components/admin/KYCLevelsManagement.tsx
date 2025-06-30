import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';
import KYCLevelBadge from '../kyc/KYCLevelBadge';
import {
  Users,
  Award,
  RefreshCw,
  Eye,
  Edit,
  AlertTriangle as AlertCircle
} from '@/components/SafeIcons';

// Safe chart and trend icons
const TrendingUp = ({ className }: { className?: string }) => <span className={className}>📈</span>;
const BarChart3 = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const UserCheck = ({ className }: { className?: string }) => <span className={className}>👤✅</span>;

interface User {
  id: number;
  username: string;
  email: string;
  full_name: string;
  current_level: number;
  level_name: string;
  kyc_status: string;
  profile_completion: number;
  created_at: string;
}

interface LevelStats {
  level_distribution: any[];
  completion_stats: any;
  recent_upgrades: any[];
}

const KYCLevelsManagement: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [levelStats, setLevelStats] = useState<LevelStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const { toast } = useToast();

  useEffect(() => {
    fetchData();
  }, [currentPage]);

  const fetchData = async () => {
    setIsLoading(true);
    try {
      // Fetch users with KYC levels
      const usersResponse = await fetch(`${ApiConfig.endpoints.admin.kycLevels}?action=get_all_user_levels&page=${currentPage}&limit=20`, {
        credentials: 'include'
      });

      if (usersResponse.ok) {
        const usersData = await usersResponse.json();
        if (usersData.success) {
          setUsers(usersData.data.users);
          setTotalPages(usersData.data.pagination.total_pages);
        }
      }

      // Fetch level statistics
      const statsResponse = await fetch(`${ApiConfig.endpoints.admin.kycLevels}?action=get_level_statistics`, {
        credentials: 'include'
      });

      if (statsResponse.ok) {
        const statsData = await statsResponse.json();
        if (statsData.success) {
          setLevelStats(statsData.data);
        }
      }

    } catch (error) {
      console.error('Failed to fetch KYC levels data:', error);
      toast({
        title: "Error",
        description: "Failed to load KYC levels data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleUpdateUserLevel = async (userId: number, newLevel: number) => {
    try {
      const response = await fetch(ApiConfig.endpoints.admin.kycLevels, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'update_user_level',
          user_id: userId,
          new_level: newLevel
        })
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: "Success",
          description: `User level updated to Level ${newLevel}`,
          variant: "default"
        });
        fetchData(); // Refresh data
      } else {
        toast({
          title: "Error",
          description: data.message || "Failed to update user level",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Failed to update user level:', error);
      toast({
        title: "Error",
        description: "Failed to update user level",
        variant: "destructive"
      });
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'verified':
        return 'bg-green-500/20 text-green-400';
      case 'pending':
        return 'bg-yellow-500/20 text-yellow-400';
      case 'rejected':
        return 'bg-red-500/20 text-red-400';
      default:
        return 'bg-gray-500/20 text-gray-400';
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <RefreshCw className="h-8 w-8 text-gold animate-spin mx-auto mb-4" />
          <p className="text-gray-400">Loading KYC levels data...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">KYC Levels Management</h1>
          <p className="text-gray-400">Manage user KYC levels and view statistics</p>
        </div>
        <Button
          onClick={fetchData}
          variant="outline"
          className="border-gray-600 text-gray-300 hover:bg-gray-700"
        >
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Statistics Cards */}
      {levelStats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {levelStats.level_distribution.map((level) => (
            <Card key={level.id} className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-blue-500/20 rounded-lg">
                    <Award className="h-5 w-5 text-blue-400" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-400">Level {level.level_number}</p>
                    <p className="text-xl font-bold text-white">{level.user_count}</p>
                    <p className="text-xs text-gray-500">{level.name}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      <Tabs defaultValue="users" className="w-full">
        <TabsList className="grid w-full grid-cols-3 bg-gray-800">
          <TabsTrigger 
            value="users" 
            className="data-[state=active]:bg-gold data-[state=active]:text-black"
          >
            <Users className="h-4 w-4 mr-2" />
            Users
          </TabsTrigger>
          <TabsTrigger 
            value="statistics" 
            className="data-[state=active]:bg-gold data-[state=active]:text-black"
          >
            <BarChart3 className="h-4 w-4 mr-2" />
            Statistics
          </TabsTrigger>
          <TabsTrigger 
            value="recent" 
            className="data-[state=active]:bg-gold data-[state=active]:text-black"
          >
            <TrendingUp className="h-4 w-4 mr-2" />
            Recent Activity
          </TabsTrigger>
        </TabsList>

        <TabsContent value="users" className="space-y-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white">User KYC Levels</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {users.map((user) => (
                  <div key={user.id} className="flex items-center justify-between p-4 bg-gray-700 rounded-lg">
                    <div className="flex items-center gap-4">
                      <div>
                        <h4 className="text-white font-medium">{user.full_name || user.username}</h4>
                        <p className="text-sm text-gray-400">{user.email}</p>
                        <div className="flex items-center gap-2 mt-1">
                          <Badge className={getStatusColor(user.kyc_status)}>
                            {user.kyc_status}
                          </Badge>
                          <span className="text-xs text-gray-500">
                            Profile: {user.profile_completion || 0}%
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-3">
                      <KYCLevelBadge level={user.current_level || 1} size="sm" />
                      
                      <div className="flex gap-1">
                        {[1, 2, 3].map((level) => (
                          <Button
                            key={level}
                            size="sm"
                            variant={user.current_level === level ? "default" : "outline"}
                            onClick={() => handleUpdateUserLevel(user.id, level)}
                            className={`text-xs ${
                              user.current_level === level 
                                ? 'bg-gold text-black' 
                                : 'border-gray-600 text-gray-300 hover:bg-gray-700'
                            }`}
                          >
                            L{level}
                          </Button>
                        ))}
                      </div>
                      
                      <Button
                        size="sm"
                        variant="outline"
                        className="border-gray-600 text-gray-300 hover:bg-gray-700"
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                ))}
              </div>

              {/* Pagination */}
              <div className="flex items-center justify-between mt-6">
                <p className="text-sm text-gray-400">
                  Page {currentPage} of {totalPages}
                </p>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                    disabled={currentPage === 1}
                    className="border-gray-600 text-gray-300 hover:bg-gray-700"
                  >
                    Previous
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                    disabled={currentPage === totalPages}
                    className="border-gray-600 text-gray-300 hover:bg-gray-700"
                  >
                    Next
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="statistics" className="space-y-4">
          {levelStats && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card className="bg-gray-800 border-gray-700">
                <CardHeader>
                  <CardTitle className="text-white">Completion Statistics</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex justify-between">
                      <span className="text-gray-400">Level 1 Completed</span>
                      <span className="text-white">{levelStats.completion_stats.level_1_completed}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-400">Level 2 Completed</span>
                      <span className="text-white">{levelStats.completion_stats.level_2_completed}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-400">Level 3 Completed</span>
                      <span className="text-white">{levelStats.completion_stats.level_3_completed}</span>
                    </div>
                    <div className="flex justify-between border-t border-gray-600 pt-4">
                      <span className="text-gray-400">Total Users</span>
                      <span className="text-white font-medium">{levelStats.completion_stats.total_users}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card className="bg-gray-800 border-gray-700">
                <CardHeader>
                  <CardTitle className="text-white">Level Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {levelStats.level_distribution.map((level) => (
                      <div key={level.id} className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <KYCLevelBadge level={level.level_number} size="sm" />
                          <span className="text-gray-400">{level.name}</span>
                        </div>
                        <span className="text-white font-medium">{level.user_count} users</span>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          )}
        </TabsContent>

        <TabsContent value="recent" className="space-y-4">
          {levelStats && (
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white">Recent Level Upgrades</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {levelStats.recent_upgrades.map((upgrade, index) => (
                    <div key={index} className="flex items-center justify-between p-3 bg-gray-700 rounded">
                      <div className="flex items-center gap-3">
                        <UserCheck className="h-4 w-4 text-green-400" />
                        <div>
                          <p className="text-white text-sm">{upgrade.full_name || upgrade.username}</p>
                          <p className="text-gray-400 text-xs">
                            Upgraded to {upgrade.level_name}
                          </p>
                        </div>
                      </div>
                      <div className="text-right">
                        <KYCLevelBadge level={upgrade.current_level} size="sm" />
                        <p className="text-gray-400 text-xs mt-1">
                          {new Date(upgrade.last_upgrade).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                  ))}
                  
                  {levelStats.recent_upgrades.length === 0 && (
                    <div className="text-center py-8">
                      <AlertCircle className="h-8 w-8 text-gray-400 mx-auto mb-2" />
                      <p className="text-gray-400">No recent upgrades</p>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default KYCLevelsManagement;
