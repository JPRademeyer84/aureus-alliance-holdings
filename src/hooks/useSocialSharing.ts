import { useState, useCallback } from 'react';
import { useToast } from '@/hooks/use-toast';
import { useUser } from '@/contexts/UserContext';
import ApiConfig from '@/config/api';

interface ShareOptions {
  content: string;
  referralLink: string;
  contentType?: 'referral' | 'achievement' | 'investment' | 'custom' | 'template';
  campaignId?: string;
}

interface ShareStats {
  platform: string;
  share_count: number;
  total_clicks: number;
  total_conversions: number;
  last_shared: string;
}

interface TotalStats {
  total_shares: number;
  total_clicks: number;
  total_conversions: number;
  platforms_used: number;
}

export const useSocialSharing = () => {
  const { toast } = useToast();
  const { user } = useUser();
  const [isSharing, setIsSharing] = useState(false);
  const [shareStats, setShareStats] = useState<ShareStats[]>([]);
  const [totalStats, setTotalStats] = useState<TotalStats | null>(null);
  const [isLoadingStats, setIsLoadingStats] = useState(false);

  const shareToSocial = useCallback(async (platform: string, options: ShareOptions) => {
    if (!user?.id) {
      toast({
        title: "Authentication Required",
        description: "Please log in to share content",
        variant: "destructive"
      });
      return false;
    }

    setIsSharing(true);
    
    try {
      // Generate share URLs using the API
      const response = await fetch(`${ApiConfig.baseUrl}/social/platform-integration.php?action=generate_share_urls`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          content: options.content,
          referral_link: options.referralLink,
          user_id: user.id,
          content_type: options.contentType || 'referral',
          campaign_id: options.campaignId
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
            return false;
          }
        } else {
          // Direct link
          window.open(shareData.url, '_blank');
        }
        
        // Track the share
        await trackShare(platform, options);

        toast({
          title: "Shared!",
          description: `Content shared on ${platform.charAt(0).toUpperCase() + platform.slice(1)}`,
        });
        
        return true;
        
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
      return false;
    } finally {
      setIsSharing(false);
    }
  }, [user?.id, toast]);

  const trackShare = useCallback(async (platform: string, options: ShareOptions) => {
    if (!user?.id) return;

    try {
      await fetch(`${ApiConfig.baseUrl}/social/share-tracking.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: user.id,
          platform: platform,
          content_type: options.contentType || 'referral',
          content: options.content,
          referral_link: options.referralLink,
          campaign_id: options.campaignId
        }),
      });
    } catch (error) {
      console.error('Failed to track share:', error);
    }
  }, [user?.id]);

  const loadShareStats = useCallback(async () => {
    if (!user?.id) return;

    setIsLoadingStats(true);
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/social/share-tracking.php?action=user_stats&user_id=${user.id}`);
      const data = await response.json();
      
      if (data.success) {
        setShareStats(data.platform_stats || []);
        setTotalStats(data.total_stats || null);
      }
    } catch (error) {
      console.error('Failed to load share stats:', error);
      toast({
        title: "Error",
        description: "Failed to load sharing statistics",
        variant: "destructive"
      });
    } finally {
      setIsLoadingStats(false);
    }
  }, [user?.id, toast]);

  const copyToClipboard = useCallback(async (content: string) => {
    try {
      await navigator.clipboard.writeText(content);
      toast({
        title: "Copied!",
        description: "Content copied to clipboard",
      });
      return true;
    } catch (error) {
      console.error('Failed to copy to clipboard:', error);
      toast({
        title: "Copy Failed",
        description: "Failed to copy content to clipboard",
        variant: "destructive"
      });
      return false;
    }
  }, [toast]);

  const generateShareUrls = useCallback(async (options: ShareOptions) => {
    if (!user?.id) return null;

    try {
      const response = await fetch(`${ApiConfig.baseUrl}/social/platform-integration.php?action=generate_share_urls`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          content: options.content,
          referral_link: options.referralLink,
          user_id: user.id,
          content_type: options.contentType || 'referral',
          campaign_id: options.campaignId
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        return data.share_urls;
      } else {
        throw new Error(data.error || 'Failed to generate share URLs');
      }
    } catch (error) {
      console.error('Failed to generate share URLs:', error);
      toast({
        title: "Error",
        description: "Failed to generate share URLs",
        variant: "destructive"
      });
      return null;
    }
  }, [user?.id, toast]);

  const getShareLeaderboard = useCallback(async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/social/share-tracking.php?action=leaderboard`);
      const data = await response.json();
      
      if (data.success) {
        return data.leaderboard || [];
      } else {
        throw new Error(data.error || 'Failed to get leaderboard');
      }
    } catch (error) {
      console.error('Failed to get share leaderboard:', error);
      toast({
        title: "Error",
        description: "Failed to load sharing leaderboard",
        variant: "destructive"
      });
      return [];
    }
  }, [toast]);

  // Platform-specific sharing functions
  const shareToTwitter = useCallback((content: string, referralLink: string, hashtags?: string[]) => {
    const twitterContent = hashtags ? `${content}\n\n${hashtags.map(tag => `#${tag}`).join(' ')}` : content;
    return shareToSocial('twitter', { content: twitterContent, referralLink });
  }, [shareToSocial]);

  const shareToFacebook = useCallback((content: string, referralLink: string) => {
    return shareToSocial('facebook', { content, referralLink });
  }, [shareToSocial]);

  const shareToLinkedIn = useCallback((content: string, referralLink: string) => {
    return shareToSocial('linkedin', { content, referralLink });
  }, [shareToSocial]);

  const shareToWhatsApp = useCallback((content: string, referralLink: string) => {
    const whatsappContent = `${content}\n\n${referralLink}`;
    return shareToSocial('whatsapp', { content: whatsappContent, referralLink });
  }, [shareToSocial]);

  const shareToTelegram = useCallback((content: string, referralLink: string) => {
    return shareToSocial('telegram', { content, referralLink });
  }, [shareToSocial]);

  return {
    // Core sharing functions
    shareToSocial,
    trackShare,
    copyToClipboard,
    generateShareUrls,
    
    // Platform-specific functions
    shareToTwitter,
    shareToFacebook,
    shareToLinkedIn,
    shareToWhatsApp,
    shareToTelegram,
    
    // Statistics functions
    loadShareStats,
    getShareLeaderboard,
    
    // State
    isSharing,
    shareStats,
    totalStats,
    isLoadingStats,
    
    // Computed values
    hasShared: shareStats.length > 0,
    totalShares: totalStats?.total_shares || 0,
    totalClicks: totalStats?.total_clicks || 0,
    totalConversions: totalStats?.total_conversions || 0
  };
};

export default useSocialSharing;
