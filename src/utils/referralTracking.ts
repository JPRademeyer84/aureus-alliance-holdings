import ApiConfig from "@/config/api";

export interface ReferralData {
  referrerUsername: string;
  referrerUserId: string;
  timestamp: string;
  source: 'direct_link' | 'shared_link' | 'social_media';
}

export interface CommissionRecord {
  id: string;
  referrerUserId: string;
  referrerUsername: string;
  referredUserId: string;
  referredUsername: string;
  purchaseAmount: number;
  commissionAmount: number;
  commissionPercentage: number;
  commissionType: 'direct_sales';
  status: 'pending' | 'paid' | 'cancelled';
  transactionHash?: string;
  createdAt: string;
  paidAt?: string;
  packageName?: string;
  phaseId?: number;
}

export interface ReferralStats {
  totalReferrals: number;
  totalCommissions: number;
  pendingCommissions: number;
  paidCommissions: number;
  availableBalance: number;
  thisMonthReferrals: number;
  thisMonthCommissions: number;
  averageCommission: number;
}

// Store referral data in localStorage when user visits via referral link
export const storeReferralData = (username: string, source: 'direct_link' | 'shared_link' | 'social_media' = 'direct_link') => {
  try {
    const referralData: ReferralData = {
      referrerUsername: username,
      referrerUserId: '', // Will be resolved when needed
      timestamp: new Date().toISOString(),
      source
    };
    
    localStorage.setItem('aureus_referral', JSON.stringify(referralData));
    console.log('Referral data stored:', referralData);
    
    // Set expiration (30 days)
    const expiration = new Date();
    expiration.setDate(expiration.getDate() + 30);
    localStorage.setItem('aureus_referral_expires', expiration.toISOString());
    
    return true;
  } catch (error) {
    console.error('Failed to store referral data:', error);
    return false;
  }
};

// Get stored referral data
export const getReferralData = (): ReferralData | null => {
  try {
    const stored = localStorage.getItem('aureus_referral');
    const expires = localStorage.getItem('aureus_referral_expires');
    
    if (!stored || !expires) return null;
    
    // Check if expired
    if (new Date() > new Date(expires)) {
      clearReferralData();
      return null;
    }
    
    return JSON.parse(stored);
  } catch (error) {
    console.error('Failed to get referral data:', error);
    return null;
  }
};

// Clear referral data
export const clearReferralData = () => {
  localStorage.removeItem('aureus_referral');
  localStorage.removeItem('aureus_referral_expires');
};

// Track referral conversion when user makes a purchase
export const trackReferralConversion = async (
  purchaseAmount: number,
  purchaseTransactionHash: string,
  buyerUserId: string,
  buyerUsername: string
): Promise<boolean> => {
  try {
    const referralData = getReferralData();
    if (!referralData) return false;
    
    // Create commission records for up to 3 levels
    const response = await fetch(ApiConfig.endpoints.referrals.track, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        referrerUsername: referralData.referrerUsername,
        referredUserId: buyerUserId,
        referredUsername: buyerUsername,
        purchaseAmount,
        transactionHash: purchaseTransactionHash,
        referralSource: referralData.source,
        referralTimestamp: referralData.timestamp
      })
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Clear referral data after successful tracking
      clearReferralData();
      console.log('Referral conversion tracked successfully');
      return true;
    } else {
      throw new Error(result.error || 'Failed to track referral');
    }
  } catch (error) {
    console.error('Failed to track referral conversion:', error);
    return false;
  }
};

// Get referral statistics for a user (NEW 20% COMMISSION MODEL)
export const getReferralStats = async (userId: string): Promise<ReferralStats> => {
  try {
    const response = await fetch(`${ApiConfig.endpoints.referrals.commissionBalance}?user_id=${userId}`, {
      credentials: 'include'
    });

    if (!response.ok) {
      if (response.status === 401 || response.status === 403) {
        console.warn('User not authenticated for referral stats');
        return {
          totalReferrals: 0,
          totalCommissions: 0,
          pendingCommissions: 0,
          paidCommissions: 0,
          availableBalance: 0,
          thisMonthReferrals: 0,
          thisMonthCommissions: 0,
          averageCommission: 0
        };
      }
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (data.success) {
      const balance = data.balance || {};
      const statistics = data.statistics || {};

      return {
        totalReferrals: statistics.total_referrals || 0,
        totalCommissions: balance.total_earned || 0,
        pendingCommissions: balance.pending_commissions || 0,
        paidCommissions: balance.paid_commissions || 0,
        availableBalance: balance.available_balance || 0,
        thisMonthReferrals: 0, // Will be calculated from monthly breakdown
        thisMonthCommissions: data.monthly_breakdown?.[0]?.total_amount || 0,
        averageCommission: statistics.average_commission || 0
      };
    } else {
      throw new Error(data.error || 'Failed to fetch referral stats');
    }
  } catch (error) {
    console.error('Failed to fetch referral stats:', error);
    return {
      totalReferrals: 0,
      totalCommissions: 0,
      pendingCommissions: 0,
      paidCommissions: 0,
      availableBalance: 0,
      thisMonthReferrals: 0,
      thisMonthCommissions: 0,
      averageCommission: 0
    };
  }
};

// Get referral commission history (NEW 20% COMMISSION MODEL)
export const getReferralHistory = async (userId: string): Promise<CommissionRecord[]> => {
  try {
    const response = await fetch(`${ApiConfig.endpoints.referrals.commissionBalance}?user_id=${userId}`, {
      credentials: 'include'
    });

    if (!response.ok) {
      if (response.status === 401 || response.status === 403) {
        console.warn('User not authenticated for referral history');
        return [];
      }
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (data.success) {
      // Transform recent activity to commission records
      const recentActivity = data.recent_activity || [];
      return recentActivity.map((activity: any) => ({
        id: activity.id || Math.random().toString(),
        referrerUserId: userId,
        referrerUsername: 'Current User',
        referredUserId: activity.referral_user_id || '',
        referredUsername: activity.referral_username || 'Unknown',
        purchaseAmount: activity.investment_amount || 0,
        commissionAmount: activity.commission_amount || 0,
        commissionPercentage: activity.commission_percentage || 20,
        commissionType: 'direct_sales' as const,
        status: activity.status || 'pending',
        createdAt: activity.created_at || new Date().toISOString(),
        packageName: activity.package_name || 'Unknown Package'
      }));
    } else {
      throw new Error(data.error || 'Failed to fetch referral history');
    }
  } catch (error) {
    console.error('Failed to fetch referral history:', error);
    return [];
  }
};

// Calculate commission amounts based on purchase (NEW 20% DIRECT MODEL)
export const calculateCommissions = (purchaseAmount: number) => {
  // New business model: 20% of 15% commission allocation = 3% of total investment
  const commissionAllocation = purchaseAmount * 0.15; // 15% goes to commission pool
  const directCommission = commissionAllocation * 0.20; // 20% of that goes to referrer

  return {
    directSales: {
      commissionAmount: directCommission,
      commissionPercentage: 20, // 20% of commission pool
      effectiveRate: 3, // 3% of total investment
      calculation: `$${purchaseAmount} × 15% × 20% = $${directCommission.toFixed(2)}`
    },
    revenueDistribution: {
      commission: commissionAllocation,
      competition: purchaseAmount * 0.15, // 15% for competition
      npo: purchaseAmount * 0.10, // 10% for NPO
      platform: purchaseAmount * 0.25, // 25% for platform
      mine: purchaseAmount * 0.35 // 35% for mine
    }
  };
};

// Validate username exists (for referral links)
export const validateReferralUsername = async (username: string): Promise<boolean> => {
  try {
    const response = await fetch(`${ApiConfig.endpoints.users.validateUsername}?username=${encodeURIComponent(username)}`);
    const data = await response.json();
    return data.success && data.exists;
  } catch (error) {
    console.error('Failed to validate referral username:', error);
    return false;
  }
};

// Send referral notification email
export const sendReferralNotification = async (
  referrerEmail: string,
  referrerUsername: string,
  referredUsername: string,
  commissionAmount: number,
  nftBonus: number
): Promise<boolean> => {
  try {
    const response = await fetch(ApiConfig.endpoints.notifications.referral, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        referrerEmail,
        referrerUsername,
        referredUsername,
        commissionAmount,
        nftBonus,
        timestamp: new Date().toISOString()
      })
    });
    
    const result = await response.json();
    return result.success;
  } catch (error) {
    console.error('Failed to send referral notification:', error);
    return false;
  }
};

// Get referral leaderboard
export const getReferralLeaderboard = async (limit: number = 10) => {
  try {
    const response = await fetch(`${ApiConfig.endpoints.referrals.leaderboard}?limit=${limit}`);
    const data = await response.json();
    
    if (data.success) {
      return data.data || [];
    } else {
      throw new Error(data.error || 'Failed to fetch leaderboard');
    }
  } catch (error) {
    console.error('Failed to fetch referral leaderboard:', error);
    return [];
  }
};
