import ApiConfig from "@/config/api";
import { TermsAcceptanceData } from "@/components/investment/TermsAcceptance";

export interface ParticipationRecord {
  id: string;
  packageName: string;
  amount: number;
  shares: number;
  reward: number;
  txHash: string;
  chainId: string;
  walletAddress: string;
  status: 'pending' | 'completed' | 'failed';
  createdAt: string;
  updatedAt: string;
}

// Legacy interface for backward compatibility
export interface InvestmentRecord extends ParticipationRecord {
  roi: number; // Legacy field mapping to reward
}

export interface CreateParticipationParams {
  packageName: string;
  amount: number;
  shares: number;
  reward: number;
  txHash: string;
  chainId: string;
  walletAddress: string;
  userEmail?: string;
  userName?: string;
  termsData?: TermsAcceptanceData | null;
  paymentMethod?: 'wallet' | 'credits';
}

// Legacy interface for backward compatibility
export interface CreateInvestmentParams extends CreateParticipationParams {
  roi: number; // Legacy field mapping to reward
}

export const createParticipationRecord = async (params: CreateParticipationParams): Promise<ParticipationRecord> => {
  try {
    const response = await fetch(ApiConfig.endpoints.participations.create, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // Include session cookies
      body: JSON.stringify({
        packageName: params.packageName,
        amount: params.amount,
        shares: params.shares,
        roi: params.roi,
        txHash: params.txHash,
        chainId: params.chainId,
        walletAddress: params.walletAddress,
        userEmail: params.userEmail || '',
        userName: params.userName || '',
        termsData: params.termsData,
        paymentMethod: params.paymentMethod || 'wallet',
        status: 'pending'
      })
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Failed to create investment record');
    }

    return data.data;
  } catch (error) {
    console.error('Failed to create investment record:', error);
    throw error;
  }
};

export const updateInvestmentStatus = async (
  investmentId: string, 
  status: 'completed' | 'failed',
  txHash?: string
): Promise<void> => {
  try {
    const response = await fetch(ApiConfig.endpoints.investments.update, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // Include session cookies
      body: JSON.stringify({
        id: investmentId,
        status,
        txHash
      })
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Failed to update investment status');
    }
  } catch (error) {
    console.error('Failed to update investment status:', error);
    throw error;
  }
};

export const getParticipationHistory = async (walletAddress: string): Promise<ParticipationRecord[]> => {
  try {
    const response = await fetch(`${ApiConfig.endpoints.investments.history}?wallet=${encodeURIComponent(walletAddress)}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      }
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Failed to fetch investment history');
    }

    return data.data || [];
  } catch (error) {
    console.error('Failed to fetch investment history:', error);
    return [];
  }
};

export const getInvestmentById = async (investmentId: string): Promise<InvestmentRecord | null> => {
  try {
    const response = await fetch(`${ApiConfig.endpoints.investments.get}?id=${encodeURIComponent(investmentId)}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      }
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.error || 'Failed to fetch investment');
    }

    return data.data;
  } catch (error) {
    console.error('Failed to fetch investment:', error);
    return null;
  }
};

export const formatParticipationStatus = (status: string): { text: string; color: string } => {
  switch (status) {
    case 'completed':
      return { text: 'Completed', color: 'text-green-400' };
    case 'pending':
      return { text: 'Pending', color: 'text-yellow-400' };
    case 'failed':
      return { text: 'Failed', color: 'text-red-400' };
    default:
      return { text: 'Unknown', color: 'text-gray-400' };
  }
};

export const getBlockExplorerUrl = (txHash: string, chainId: string): string => {
  console.log(`Getting block explorer URL for txHash: ${txHash}, chainId: ${chainId}`);

  const explorers = {
    '0x1': 'https://etherscan.io/tx/',
    '0x38': 'https://bscscan.com/tx/',
    '0x89': 'https://polygonscan.com/tx/',
    'tron': 'https://tronscan.org/#/transaction/',
    'ethereum': 'https://etherscan.io/tx/',
    'bsc': 'https://bscscan.com/tx/',
    'polygon': 'https://polygonscan.com/tx/'
  };

  const baseUrl = explorers[chainId as keyof typeof explorers];
  const fullUrl = baseUrl ? `${baseUrl}${txHash}` : `https://polygonscan.com/tx/${txHash}`;

  console.log(`Generated explorer URL: ${fullUrl}`);
  return fullUrl;
};

export const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount);
};

export const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Legacy exports for backward compatibility
export const createInvestmentRecord = createParticipationRecord;
export const getInvestmentHistory = getParticipationHistory;
export const formatInvestmentStatus = formatParticipationStatus;
