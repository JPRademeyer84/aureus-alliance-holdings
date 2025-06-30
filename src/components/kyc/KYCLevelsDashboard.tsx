import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';
import KYCLevelBadge from './KYCLevelBadge';
import KYCProgressIndicator from './KYCProgressIndicator';
import KYCBenefitsShowcase from './KYCBenefitsShowcase';
import { 
  TrendingUp, 
  Award, 
  Target, 
  RefreshCw,
  AlertCircle,
  CheckCircle
} from 'lucide-react';

interface KYCLevelsDashboardProps {
  userId?: string;
  className?: string;
}

const KYCLevelsDashboard: React.FC<KYCLevelsDashboardProps> = ({
  userId,
  className = ''
}) => {
  const [isLoading, setIsLoading] = useState(true);
  const [currentLevel, setCurrentLevel] = useState(1);
  const [userLevelData, setUserLevelData] = useState<any>(null);
  const [allLevels, setAllLevels] = useState<any[]>([]);
  const [levelProgress, setLevelProgress] = useState<any[]>([]);
  const [isUpgrading, setIsUpgrading] = useState(false);
  const [isAuthenticated, setIsAuthenticated] = useState(true);
  const { toast } = useToast();

  useEffect(() => {
    fetchKYCData();
  }, [userId]);

  const fetchKYCData = async () => {
    setIsLoading(true);
    try {
      // Fetch all KYC levels (with cache busting)
      const levelsResponse = await fetch(`${ApiConfig.endpoints.kyc.levels}?action=get_levels&_t=${Date.now()}`, {
        credentials: 'include'
      });

      if (levelsResponse.status === 401) {
        // User not authenticated - show login message
        setIsAuthenticated(false);
        toast({
          title: "Authentication Required",
          description: "Please log in to view your KYC levels",
          variant: "destructive"
        });
        return;
      }

      if (levelsResponse.ok) {
        const levelsData = await levelsResponse.json();
        if (levelsData.success) {
          setAllLevels(levelsData.data.levels);
        } else {
          console.error('Levels API error:', levelsData.message);
        }
      }

      // Fetch user's current level (with cache busting)
      const userLevelResponse = await fetch(`${ApiConfig.endpoints.kyc.levels}?action=get_user_level${userId ? `&user_id=${userId}` : ''}&_t=${Date.now()}`, {
        credentials: 'include'
      });

      if (userLevelResponse.status === 401) {
        return; // Already handled above
      }

      if (userLevelResponse.ok) {
        try {
          const userData = await userLevelResponse.json();
          if (userData.success && userData.data) {
            setUserLevelData(userData.data);
            setCurrentLevel(userData.data?.user_level?.current_level || 1);
          } else {
            console.error('User level API error:', userData.message);
            // Set default values to prevent crashes
            setUserLevelData(null);
            setCurrentLevel(1);
          }
        } catch (error) {
          console.error('Failed to parse user level response:', error);
          setUserLevelData(null);
          setCurrentLevel(1);
        }
      } else {
        console.error('User level API failed with status:', userLevelResponse.status);
        setUserLevelData(null);
        setCurrentLevel(1);
      }

      // Fetch progress for all levels (with cache busting)
      const progressResponse = await fetch(`${ApiConfig.endpoints.kyc.levels}?action=get_progress${userId ? `&user_id=${userId}` : ''}&_t=${Date.now()}`, {
        credentials: 'include'
      });

      if (progressResponse.status === 401) {
        return; // Already handled above
      }

      if (progressResponse.ok) {
        try {
          const progressData = await progressResponse.json();
          if (progressData.success && progressData.data && progressData.data.progress) {
            const progressArray = Object.entries(progressData.data.progress).map(([key, value]: [string, any]) => ({
              level: parseInt(key.replace('level_', '')),
              ...value
            }));
            setLevelProgress(progressArray);
          } else {
            console.error('Progress API error:', progressData.message);
            setLevelProgress([]); // Set empty array as fallback
          }
        } catch (error) {
          console.error('Failed to parse progress response:', error);
          setLevelProgress([]); // Set empty array as fallback
        }
      } else {
        console.error('Progress API failed with status:', progressResponse.status);
        setLevelProgress([]); // Set empty array as fallback
      }

    } catch (error) {
      console.error('Failed to fetch KYC data:', error);
      toast({
        title: "Connection Error",
        description: "Unable to connect to the server. Please check your connection and try again.",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleUpgradeLevel = async (targetLevel: number) => {
    setIsUpgrading(true);
    try {
      const response = await fetch(ApiConfig.endpoints.kyc.levels, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'upgrade_level',
          target_level: targetLevel,
          user_id: userId
        })
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: "🎉 Level Upgraded!",
          description: `Congratulations! You've been upgraded to Level ${targetLevel}`,
          variant: "default"
        });
        
        // Refresh data
        await fetchKYCData();
      } else {
        toast({
          title: "Upgrade Failed",
          description: data.message || "Failed to upgrade level",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Failed to upgrade level:', error);
      toast({
        title: "Error",
        description: "Failed to upgrade level",
        variant: "destructive"
      });
    } finally {
      setIsUpgrading(false);
    }
  };

  if (isLoading) {
    return (
      <div className={`flex items-center justify-center py-12 ${className}`}>
        <div className="text-center">
          <RefreshCw className="h-8 w-8 text-gold animate-spin mx-auto mb-4" />
          <p className="text-gray-400">Loading KYC level information...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className={`flex items-center justify-center py-12 ${className}`}>
        <Card className="bg-gray-800 border-gray-700 max-w-md">
          <CardContent className="p-8 text-center">
            <AlertCircle className="h-12 w-12 text-yellow-400 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-white mb-2">Authentication Required</h3>
            <p className="text-gray-400 mb-6">
              Please log in to your account to view your KYC levels and progress.
            </p>
            <Button
              onClick={() => window.location.href = '/login'}
              className="bg-gold hover:bg-gold/80 text-black"
            >
              Go to Login
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  const nextLevel = levelProgress.find(l => l.level === currentLevel + 1);
  const currentLevelProgress = levelProgress.find(l => l.level === currentLevel);

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Header Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-gold/20 rounded-lg">
                <Award className="h-5 w-5 text-gold" />
              </div>
              <div>
                <p className="text-sm text-gray-400">Current Level</p>
                <p className="text-xl font-bold text-white">
                  Level {currentLevel} - {currentLevel === 1 ? 'Basic' : currentLevel === 2 ? 'Intermediate' : 'Advanced'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-500/20 rounded-lg">
                <TrendingUp className="h-5 w-5 text-blue-400" />
              </div>
              <div>
                <p className="text-sm text-gray-400">Overall Progress</p>
                <p className="text-xl font-bold text-white">
                  {currentLevelProgress ? `${currentLevelProgress.progress.toFixed(0)}%` : '0%'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-green-500/20 rounded-lg">
                <CheckCircle className="h-5 w-5 text-green-400" />
              </div>
              <div>
                <p className="text-sm text-gray-400">Requirements Done</p>
                <p className="text-xl font-bold text-white">
                  {currentLevelProgress ? currentLevelProgress.completed_count : 0} / {currentLevelProgress ? currentLevelProgress.total_count : 0}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-yellow-500/20 rounded-lg">
                <Target className="h-5 w-5 text-yellow-400" />
              </div>
              <div>
                <p className="text-sm text-gray-400">Next Milestone</p>
                <p className="text-xl font-bold text-white">
                  Level {Math.min(currentLevel + 1, 3)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Tabs */}
      <Tabs defaultValue="progress" className="w-full">
        <TabsList className="grid w-full grid-cols-3 bg-gray-800">
          <TabsTrigger 
            value="progress" 
            className="data-[state=active]:bg-gold data-[state=active]:text-black"
          >
            Progress
          </TabsTrigger>
          <TabsTrigger 
            value="benefits" 
            className="data-[state=active]:bg-gold data-[state=active]:text-black"
          >
            Benefits
          </TabsTrigger>
          <TabsTrigger 
            value="overview" 
            className="data-[state=active]:bg-gold data-[state=active]:text-black"
          >
            Overview
          </TabsTrigger>
        </TabsList>

        <TabsContent value="progress" className="space-y-6">
          <KYCProgressIndicator
            currentLevel={currentLevel}
            levelProgress={levelProgress}
            onUpgradeLevel={handleUpgradeLevel}
          />
        </TabsContent>

        <TabsContent value="benefits" className="space-y-6">
          <KYCBenefitsShowcase
            levels={allLevels}
            currentLevel={currentLevel}
          />
        </TabsContent>

        <TabsContent value="overview" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Current Status */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Award className="h-5 w-5 text-gold" />
                  Current Status
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-gray-400">KYC Level</span>
                  <KYCLevelBadge level={currentLevel} />
                </div>
                
                {userLevelData?.user_level?.level_1_completed_at && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Level 1 Completed</span>
                    <span className="text-white text-sm">
                      {new Date(userLevelData.user_level.level_1_completed_at).toLocaleDateString()}
                    </span>
                  </div>
                )}
                
                {userLevelData?.user_level?.level_2_completed_at && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Level 2 Completed</span>
                    <span className="text-white text-sm">
                      {new Date(userLevelData.user_level.level_2_completed_at).toLocaleDateString()}
                    </span>
                  </div>
                )}

                {userLevelData?.user_level?.level_3_completed_at && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Level 3 Completed</span>
                    <span className="text-white text-sm">
                      {new Date(userLevelData.user_level.level_3_completed_at).toLocaleDateString()}
                    </span>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Quick Actions */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Target className="h-5 w-5 text-blue-400" />
                  Quick Actions
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <Button
                  onClick={() => fetchKYCData()}
                  variant="outline"
                  className="w-full border-gray-600 text-gray-300 hover:bg-gray-700"
                  disabled={isLoading}
                >
                  <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                  Refresh Status
                </Button>
                
                {nextLevel && nextLevel.can_upgrade && (
                  <Button
                    onClick={() => handleUpgradeLevel(nextLevel.level)}
                    className="w-full bg-gold hover:bg-gold/80 text-black"
                    disabled={isUpgrading}
                  >
                    {isUpgrading ? (
                      <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                    ) : (
                      <TrendingUp className="h-4 w-4 mr-2" />
                    )}
                    Upgrade to Level {nextLevel.level}
                  </Button>
                )}
                
                {currentLevel < 3 && (
                  <div className="p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
                    <div className="flex items-start gap-2">
                      <AlertCircle className="h-4 w-4 text-yellow-400 mt-0.5" />
                      <div>
                        <p className="text-yellow-400 text-sm font-medium">Next Steps</p>
                        <p className="text-gray-300 text-xs">
                          Complete the remaining requirements to unlock Level {currentLevel + 1} benefits.
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default KYCLevelsDashboard;
