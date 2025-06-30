import { useEffect, useState } from 'react';
import { useParams, useLocation } from 'react-router-dom';
import { 
  storeReferralData, 
  getReferralData, 
  validateReferralUsername,
  trackReferralConversion,
  getReferralStats,
  getReferralHistory,
  type ReferralStats,
  type CommissionRecord
} from '@/utils/referralTracking';
import { useToast } from '@/hooks/use-toast';

export const useReferralTracking = () => {
  const { username } = useParams<{ username: string }>();
  const location = useLocation();
  const { toast } = useToast();
  const [isValidReferral, setIsValidReferral] = useState<boolean | null>(null);
  const [referralData, setReferralData] = useState(getReferralData());

  // Check for referral tracking on page load
  useEffect(() => {
    const checkReferral = async () => {
      // Check if this is a username-based referral link
      if (username && location.pathname === `/${username}`) {
        console.log('Checking referral username:', username);

        try {
          // Call backend API to track referral visit
          const response = await fetch('/api/referrals/track-visit.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
              username: username,
              source: 'direct_link'
            })
          });

          const data = await response.json();

          if (data.success) {
            setIsValidReferral(true);
            // Also store in localStorage as backup
            const stored = storeReferralData(username, 'direct_link');
            if (stored) {
              setReferralData(getReferralData());
            }

            toast({
              title: "Referral Tracked! 🎉",
              description: `You were referred by ${username}. Complete a purchase to earn them rewards!`,
              duration: 5000,
            });
          } else {
            setIsValidReferral(false);
            console.log('Invalid referral username:', username, data.error);
          }
        } catch (error) {
          console.error('Failed to track referral:', error);
          setIsValidReferral(false);
        }
      }
      
      // Check for query parameter referrals (legacy support)
      const urlParams = new URLSearchParams(location.search);
      const refParam = urlParams.get('ref');
      
      if (refParam && !referralData) {
        const isValid = await validateReferralUsername(refParam);
        if (isValid) {
          const stored = storeReferralData(refParam, 'shared_link');
          if (stored) {
            setReferralData(getReferralData());
            toast({
              title: "Referral Tracked! 🎉",
              description: `You were referred by ${refParam}. Complete a purchase to earn them rewards!`,
              duration: 5000,
            });
          }
        }
      }
    };

    checkReferral();
  }, [username, location, toast]);

  return {
    isValidReferral,
    referralData,
    hasActiveReferral: !!referralData
  };
};

export const useReferralStats = (userId: string | undefined) => {
  const [stats, setStats] = useState<ReferralStats>({
    totalReferrals: 0,
    totalCommissions: 0,
    pendingCommissions: 0,
    paidCommissions: 0,
    availableBalance: 0,
    thisMonthReferrals: 0,
    thisMonthCommissions: 0,
    averageCommission: 0
  });
  const [history, setHistory] = useState<CommissionRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchReferralData = async () => {
    if (!userId) {
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const [statsData, historyData] = await Promise.all([
        getReferralStats(userId),
        getReferralHistory(userId)
      ]);

      setStats(statsData);
      setHistory(historyData);
    } catch (err: any) {
      setError(err.message || 'Failed to fetch referral data');
      console.error('Failed to fetch referral data:', err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchReferralData();
  }, [userId]);

  return {
    stats,
    history,
    isLoading,
    error,
    refetch: fetchReferralData
  };
};

export const useReferralConversion = () => {
  const [isTracking, setIsTracking] = useState(false);

  const trackConversion = async (
    purchaseAmount: number,
    transactionHash: string,
    buyerUserId: string,
    buyerUsername: string
  ): Promise<boolean> => {
    setIsTracking(true);
    
    try {
      const success = await trackReferralConversion(
        purchaseAmount,
        transactionHash,
        buyerUserId,
        buyerUsername
      );
      
      return success;
    } catch (error) {
      console.error('Failed to track referral conversion:', error);
      return false;
    } finally {
      setIsTracking(false);
    }
  };

  return {
    trackConversion,
    isTracking
  };
};
