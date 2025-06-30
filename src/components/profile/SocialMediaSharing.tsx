import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import { useUser } from '@/contexts/UserContext';
import {
  Share2,
  Twitter,
  Facebook,
  Linkedin,
  MessageCircle,
  Copy,
  CheckCircle,
  Star,
  TrendingUp,
  Users,
  BarChart3,
  ExternalLink,
  RefreshCw
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface SocialMediaSharingProps {
  profile: any;
  onShare?: (platform: string, content: string) => void;
}

interface ShareStats {
  platform: string;
  share_count: number;
  total_clicks: number;
  total_conversions: number;
  last_shared: string;
}

const SocialMediaSharing: React.FC<SocialMediaSharingProps> = ({ profile, onShare }) => {
  const { toast } = useToast();
  const { user } = useUser();
  const [customMessage, setCustomMessage] = useState('');
  const [sharedPlatforms, setSharedPlatforms] = useState<string[]>([]);
  const [shareStats, setShareStats] = useState<ShareStats[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [totalStats, setTotalStats] = useState<any>(null);

  const baseUrl = 'https://aureusangels.com';
  const referralLink = `${baseUrl}/${profile.username}`;

  useEffect(() => {
    if (user?.id) {
      loadShareStats();
    }
  }, [user?.id]);

  const loadShareStats = async () => {
    try {
      setIsLoading(true);
      const response = await fetch(`${ApiConfig.baseUrl}/social/share-tracking.php?action=user_stats&user_id=${user?.id}`);
      const data = await response.json();

      if (data.success) {
        setShareStats(data.platform_stats || []);
        setTotalStats(data.total_stats || {});
      }
    } catch (error) {
      console.error('Failed to load share stats:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const shareTemplates = {
    achievement: `🎉 Just achieved ${profile.profile_completion}% profile completion on Aureus Angels! 

💎 Unlocked exclusive benefits:
✅ Gold Diggers Club access
✅ $250K bonus pool eligibility  
✅ Higher commission rates
✅ VIP support

Join me in this amazing opportunity: ${referralLink}

#AureusAngels #GoldDiggersClub #Investment #Opportunity`,

    kyc_verified: `🔐 Identity verified on Aureus Angels! 

✅ KYC verification complete
✅ $10,000+ withdrawal limits unlocked
✅ Premium investment packages available
✅ Enhanced security & trust

Ready to start your journey? ${referralLink}

#AureusAngels #KYCVerified #TrustedInvestor #Opportunity`,

    referral: `💰 Earning passive income with Aureus Angels!

🚀 Join my team and unlock:
• Multiple income streams
• Generous commission structure  
• Professional support system
• Proven investment strategies

Start your journey: ${referralLink}

#PassiveIncome #AureusAngels #Investment #TeamBuilding`,

    milestone: `🎯 Another milestone reached with Aureus Angels!

📊 My Stats:
💵 Total Invested: $${profile.total_invested || 0}
💰 Commissions Earned: $${profile.total_commissions || 0}
👥 Team Members: ${profile.referral_count || 0}

Join my successful team: ${referralLink}

#Success #AureusAngels #Investment #TeamGrowth`
  };

  const handleShare = async (platform: string, content: string, contentType: string = 'referral') => {
    try {
      // Generate share URLs using the API
      const response = await fetch(`${ApiConfig.baseUrl}/social/platform-integration.php?action=generate_share_urls`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          content: content,
          referral_link: referralLink,
          user_id: user?.id,
          content_type: contentType
        }),
      });

      const data = await response.json();

      if (data.success && data.share_urls[platform]) {
        const shareData = data.share_urls[platform];

        if (shareData.method === 'popup') {
          // Open share window
          const shareWindow = window.open(
            shareData.url,
            '_blank',
            'width=600,height=400,scrollbars=yes,resizable=yes'
          );

          // Check if window was blocked
          if (!shareWindow) {
            toast({
              title: "Popup Blocked",
              description: "Please allow popups for this site to share content",
              variant: "destructive"
            });
            return;
          }
        } else {
          // Direct link
          window.open(shareData.url, '_blank');
        }

        // Mark as shared
        if (!sharedPlatforms.includes(platform)) {
          setSharedPlatforms([...sharedPlatforms, platform]);
        }

        // Track the share
        await trackShare(platform, content, contentType);

        // Call callback if provided
        if (onShare) {
          onShare(platform, content);
        }

        toast({
          title: "Shared!",
          description: `Content shared on ${platform.charAt(0).toUpperCase() + platform.slice(1)}`,
        });

        // Reload stats
        loadShareStats();

      } else {
        throw new Error(data.error || 'Failed to generate share URL');
      }
    } catch (error) {
      console.error('Share failed:', error);
      toast({
        title: "Share Failed",
        description: error instanceof Error ? error.message : "Failed to share content",
        variant: "destructive"
      });
    }
  };

  const trackShare = async (platform: string, content: string, contentType: string) => {
    try {
      await fetch(`${ApiConfig.baseUrl}/social/share-tracking.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: user?.id,
          platform: platform,
          content_type: contentType,
          content: content,
          referral_link: referralLink
        }),
      });
    } catch (error) {
      console.error('Failed to track share:', error);
    }
  };

  const copyToClipboard = (content: string) => {
    navigator.clipboard.writeText(content);
    toast({
      title: "Copied!",
      description: "Content copied to clipboard",
    });
  };

  const getSuggestedTemplate = () => {
    if (profile.kyc_status === 'verified' && profile.profile_completion >= 100) {
      return shareTemplates.achievement;
    } else if (profile.kyc_status === 'verified') {
      return shareTemplates.kyc_verified;
    } else if ((profile.total_invested || 0) > 0) {
      return shareTemplates.milestone;
    } else {
      return shareTemplates.referral;
    }
  };

  const platforms = [
    { id: 'twitter', name: 'Twitter', icon: Twitter, color: 'bg-blue-500' },
    { id: 'facebook', name: 'Facebook', icon: Facebook, color: 'bg-blue-600' },
    { id: 'linkedin', name: 'LinkedIn', icon: Linkedin, color: 'bg-blue-700' },
    { id: 'whatsapp', name: 'WhatsApp', icon: MessageCircle, color: 'bg-green-500' },
    { id: 'telegram', name: 'Telegram', icon: MessageCircle, color: 'bg-blue-400' }
  ];

  const getPlatformIcon = (platform: string) => {
    switch (platform) {
      case 'twitter': return <Twitter className="w-4 h-4" />;
      case 'facebook': return <Facebook className="w-4 h-4" />;
      case 'linkedin': return <Linkedin className="w-4 h-4" />;
      case 'whatsapp': return <MessageCircle className="w-4 h-4" />;
      case 'telegram': return <MessageCircle className="w-4 h-4" />;
      default: return <Share2 className="w-4 h-4" />;
    }
  };

  return (
    <div className="space-y-6">
      {/* Sharing Statistics */}
      {totalStats && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <BarChart3 className="h-5 w-5 text-gold" />
              Sharing Statistics
              <Button
                variant="ghost"
                size="sm"
                onClick={loadShareStats}
                disabled={isLoading}
                className="ml-auto"
              >
                <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
              </Button>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
              <div className="bg-gray-700 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-gold">{totalStats.total_shares || 0}</div>
                <div className="text-sm text-gray-400">Total Shares</div>
              </div>
              <div className="bg-gray-700 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-blue-400">{totalStats.total_clicks || 0}</div>
                <div className="text-sm text-gray-400">Total Clicks</div>
              </div>
              <div className="bg-gray-700 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-green-400">{totalStats.total_conversions || 0}</div>
                <div className="text-sm text-gray-400">Conversions</div>
              </div>
              <div className="bg-gray-700 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-purple-400">{totalStats.platforms_used || 0}</div>
                <div className="text-sm text-gray-400">Platforms Used</div>
              </div>
            </div>

            {shareStats.length > 0 && (
              <div className="space-y-2">
                <h4 className="text-white font-medium">Platform Breakdown</h4>
                {shareStats.map((stat) => (
                  <div key={stat.platform} className="flex items-center justify-between p-2 bg-gray-700 rounded">
                    <div className="flex items-center gap-2">
                      {getPlatformIcon(stat.platform)}
                      <span className="text-white capitalize">{stat.platform}</span>
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                      <span className="text-gray-400">{stat.share_count} shares</span>
                      <span className="text-blue-400">{stat.total_clicks} clicks</span>
                      <span className="text-green-400">{stat.total_conversions} conversions</span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Sharing Progress */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Share2 className="h-5 w-5 text-gold" />
            Social Media Sharing Progress
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
            {platforms.map((platform) => {
              const isShared = sharedPlatforms.includes(platform.id);
              const Icon = platform.icon;
              
              return (
                <div key={platform.id} className="text-center">
                  <div className={`w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center ${
                    isShared ? platform.color : 'bg-gray-700'
                  }`}>
                    {isShared ? (
                      <CheckCircle className="w-6 h-6 text-white" />
                    ) : (
                      <Icon className="w-6 h-6 text-gray-400" />
                    )}
                  </div>
                  <p className={`text-xs ${isShared ? 'text-green-400' : 'text-gray-400'}`}>
                    {platform.name}
                  </p>
                  {isShared && (
                    <Badge className="bg-green-500/20 text-green-400 border-green-500/30 text-xs mt-1">
                      Shared
                    </Badge>
                  )}
                </div>
              );
            })}
          </div>
          
          {sharedPlatforms.length > 0 && (
            <div className="mt-4 p-3 bg-green-500/10 border border-green-500/30 rounded">
              <p className="text-green-400 text-sm font-medium">
                🎉 Great job! You've shared on {sharedPlatforms.length} platform{sharedPlatforms.length > 1 ? 's' : ''}
              </p>
              <p className="text-green-300 text-xs mt-1">
                Keep sharing to maximize your reach and earn more referrals!
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Quick Share Templates */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Quick Share Templates</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {Object.entries(shareTemplates).map(([key, template]) => {
            const isRecommended = key === (
              profile.kyc_status === 'verified' && profile.profile_completion >= 100 ? 'achievement' :
              profile.kyc_status === 'verified' ? 'kyc_verified' :
              (profile.total_invested || 0) > 0 ? 'milestone' : 'referral'
            );
            
            return (
              <div key={key} className={`border rounded-lg p-4 ${
                isRecommended ? 'border-gold/30 bg-gold/5' : 'border-gray-700'
              }`}>
                <div className="flex items-center justify-between mb-2">
                  <h4 className={`font-medium ${isRecommended ? 'text-gold' : 'text-white'}`}>
                    {key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                  </h4>
                  {isRecommended && (
                    <Badge className="bg-gold/20 text-gold border-gold/30 text-xs">
                      Recommended
                    </Badge>
                  )}
                </div>
                
                <div className="bg-gray-900 rounded p-3 mb-3">
                  <p className="text-gray-300 text-sm whitespace-pre-line">
                    {template}
                  </p>
                </div>
                
                <div className="flex flex-wrap gap-2">
                  {platforms.map((platform) => {
                    const Icon = platform.icon;
                    return (
                      <Button
                        key={platform.id}
                        size="sm"
                        onClick={() => handleShare(platform.id, template)}
                        className={`${platform.color} hover:opacity-90 text-white`}
                      >
                        <Icon className="w-4 h-4 mr-1" />
                        {platform.name}
                      </Button>
                    );
                  })}
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => copyToClipboard(template)}
                    className="border-gray-600 text-gray-300 hover:bg-gray-700"
                  >
                    <Copy className="w-4 h-4 mr-1" />
                    Copy
                  </Button>
                </div>
              </div>
            );
          })}
        </CardContent>
      </Card>

      {/* Custom Message */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Create Custom Message</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Textarea
            placeholder="Write your custom message here... Your referral link will be automatically added."
            value={customMessage}
            onChange={(e) => setCustomMessage(e.target.value)}
            className="bg-gray-700 border-gray-600 text-white min-h-[100px]"
          />
          
          {customMessage && (
            <div className="bg-gray-900 rounded p-3">
              <p className="text-gray-300 text-sm whitespace-pre-line">
                {customMessage}
                {customMessage && '\n\n'}
                Join me: {referralLink}
              </p>
            </div>
          )}
          
          <div className="flex flex-wrap gap-2">
            {platforms.map((platform) => {
              const Icon = platform.icon;
              return (
                <Button
                  key={platform.id}
                  size="sm"
                  onClick={() => handleShare(platform.id, customMessage + '\n\nJoin me: ' + referralLink)}
                  disabled={!customMessage.trim()}
                  className={`${platform.color} hover:opacity-90 text-white disabled:opacity-50`}
                >
                  <Icon className="w-4 h-4 mr-1" />
                  {platform.name}
                </Button>
              );
            })}
            <Button
              size="sm"
              variant="outline"
              onClick={() => copyToClipboard(customMessage + '\n\nJoin me: ' + referralLink)}
              disabled={!customMessage.trim()}
              className="border-gray-600 text-gray-300 hover:bg-gray-700 disabled:opacity-50"
            >
              <Copy className="w-4 h-4 mr-1" />
              Copy
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default SocialMediaSharing;
