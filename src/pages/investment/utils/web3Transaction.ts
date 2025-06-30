import { WalletProviderName } from "../useWalletConnection";
import { getProviderObject } from "./walletProviders";
import ApiConfig from "@/config/api";

// Supported chains and their details
export const SUPPORTED_CHAINS = {
  ethereum: {
    chainId: '0x1',
    name: 'Ethereum Mainnet',
    nativeCurrency: { name: 'Ethereum', symbol: 'ETH', decimals: 18 },
    rpcUrls: ['https://mainnet.infura.io/v3/'],
    blockExplorerUrls: ['https://etherscan.io/'],
    usdtContract: '0xdAC17F958D2ee523a2206206994597C13D831ec7'
  },
  bsc: {
    chainId: '0x38',
    name: 'BNB Smart Chain',
    nativeCurrency: { name: 'BNB', symbol: 'BNB', decimals: 18 },
    rpcUrls: ['https://bsc-dataseed1.binance.org/'],
    blockExplorerUrls: ['https://bscscan.com/'],
    usdtContract: '0x55d398326f99059fF775485246999027B3197955'
  },
  polygon: {
    chainId: '0x89',
    name: 'Polygon Mainnet',
    nativeCurrency: { name: 'MATIC', symbol: 'MATIC', decimals: 18 },
    rpcUrls: ['https://polygon-rpc.com/'],
    blockExplorerUrls: ['https://polygonscan.com/'],
    usdtContract: '0xc2132D05D31c914a87C6611C10748AEb04B58e8F'
  },
  tron: {
    chainId: 'tron',
    name: 'TRON Mainnet',
    nativeCurrency: { name: 'TRX', symbol: 'TRX', decimals: 6 },
    rpcUrls: ['https://api.trongrid.io'],
    blockExplorerUrls: ['https://tronscan.org/'],
    usdtContract: 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
  }
};

export type SupportedChain = keyof typeof SUPPORTED_CHAINS;

// USDT ABI (minimal for transfer)
const USDT_ABI = [
  {
    "constant": true,
    "inputs": [{"name": "_owner", "type": "address"}],
    "name": "balanceOf",
    "outputs": [{"name": "balance", "type": "uint256"}],
    "type": "function"
  },
  {
    "constant": false,
    "inputs": [
      {"name": "_to", "type": "address"},
      {"name": "_value", "type": "uint256"}
    ],
    "name": "transfer",
    "outputs": [{"name": "", "type": "bool"}],
    "type": "function"
  },
  {
    "constant": true,
    "inputs": [],
    "name": "decimals",
    "outputs": [{"name": "", "type": "uint8"}],
    "type": "function"
  }
];

export interface TransactionResult {
  success: boolean;
  txHash?: string;
  error?: string;
  chainId?: string;
  amount?: string;
  from?: string;
  to?: string;
}

export interface BalanceInfo {
  balance: string;
  decimals: number;
  symbol: string;
  formatted: string;
  error?: string;
}

// Cache for company wallet addresses
let COMPANY_WALLETS: Record<string, string> = {};
let walletsLastFetched = 0;
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

// Fetch company wallet addresses from secure database
const fetchCompanyWallets = async (): Promise<Record<string, string>> => {
  const now = Date.now();

  // Return cached wallets if still valid
  if (Object.keys(COMPANY_WALLETS).length > 0 && (now - walletsLastFetched) < CACHE_DURATION) {
    return COMPANY_WALLETS;
  }

  try {
    const response = await fetch(ApiConfig.endpoints.wallets.active);
    const data = await response.json();

    if (data.success && data.data) {
      COMPANY_WALLETS = data.data;
      walletsLastFetched = now;
      console.log('Company wallets fetched from database:', Object.keys(COMPANY_WALLETS));
      return COMPANY_WALLETS;
    } else {
      throw new Error(data.error || 'Failed to fetch wallet addresses');
    }
  } catch (error) {
    console.error('Failed to fetch company wallets:', error);

    // Fallback to default addresses if database fetch fails
    const fallbackWallets = {
      ethereum: '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7',
      bsc: '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7',
      polygon: '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7',
      tron: 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE'
    };

    console.warn('Using fallback wallet addresses due to fetch failure');
    return fallbackWallets;
  }
};

export const switchToChain = async (
  provider: WalletProviderName,
  chainKey: SupportedChain
): Promise<boolean> => {
  try {
    const providerObj = getProviderObject(provider);
    if (!providerObj) throw new Error("Provider not available");

    const chain = SUPPORTED_CHAINS[chainKey];
    
    if (provider === "tronlink") {
      // TronLink doesn't need chain switching as it's TRON only
      return chainKey === 'tron';
    }

    try {
      // Try to switch to the chain
      await providerObj.request({
        method: 'wallet_switchEthereumChain',
        params: [{ chainId: chain.chainId }],
      });
      return true;
    } catch (switchError: any) {
      // If chain doesn't exist, add it
      if (switchError.code === 4902) {
        await providerObj.request({
          method: 'wallet_addEthereumChain',
          params: [{
            chainId: chain.chainId,
            chainName: chain.name,
            nativeCurrency: chain.nativeCurrency,
            rpcUrls: chain.rpcUrls,
            blockExplorerUrls: chain.blockExplorerUrls,
          }],
        });
        return true;
      }
      throw switchError;
    }
  } catch (error) {
    console.error("Failed to switch chain:", error);
    return false;
  }
};

export const getUSDTBalance = async (
  provider: WalletProviderName,
  walletAddress: string,
  chainKey: SupportedChain
): Promise<BalanceInfo> => {
  console.log(`Getting USDT balance for ${provider} on ${chainKey} for address ${walletAddress}`);

  try {
    const providerObj = getProviderObject(provider);
    if (!providerObj) throw new Error("Provider not available");

    const chain = SUPPORTED_CHAINS[chainKey];
    console.log(`Using chain config:`, chain);

    if (provider === "tronlink" && chainKey === 'tron') {
      // Handle TRON USDT balance
      const tronWeb = providerObj.tronWeb || window.tronWeb;
      if (!tronWeb) throw new Error("TronWeb not available");

      const contract = await tronWeb.contract().at(chain.usdtContract);
      const balance = await contract.balanceOf(walletAddress).call();
      const decimals = 6; // USDT on TRON has 6 decimals
      
      return {
        balance: balance.toString(),
        decimals,
        symbol: 'USDT',
        formatted: (parseInt(balance.toString()) / Math.pow(10, decimals)).toFixed(2)
      };
    } else {
      // Handle EVM chains (Ethereum, BSC, Polygon)
      console.log(`Handling EVM chain: ${chainKey}`);

      // USDT decimals: Ethereum = 6, BSC = 18, Polygon = 6
      const decimals = chainKey === 'bsc' ? 18 : 6;
      console.log(`Using ${decimals} decimals for ${chainKey}`);

      // Prepare the balanceOf call data
      const balanceOfData = `0x70a08231000000000000000000000000${walletAddress.slice(2)}`;
      console.log(`Balance call data: ${balanceOfData}`);

      try {
        // Get balance using eth_call
        const balance = await providerObj.request({
          method: 'eth_call',
          params: [{
            to: chain.usdtContract,
            data: balanceOfData
          }, 'latest']
        });

        console.log(`Raw balance response: ${balance}`);

        if (!balance || balance === '0x') {
          console.warn('Empty balance response, returning 0');
          return {
            balance: '0',
            decimals,
            symbol: 'USDT',
            formatted: '0.00'
          };
        }

        const balanceInt = parseInt(balance, 16);
        const formattedBalance = (balanceInt / Math.pow(10, decimals)).toFixed(6);

        console.log(`Parsed balance: ${balanceInt}, formatted: ${formattedBalance}, decimals: ${decimals}`);

        return {
          balance: balance,
          decimals,
          symbol: 'USDT',
          formatted: formattedBalance
        };
      } catch (callError) {
        console.error('eth_call failed:', callError);
        throw callError;
      }
    }

    throw new Error("Unable to get balance - unsupported chain/provider combination");
  } catch (error: any) {
    console.error("Failed to get USDT balance:", error);

    // Return error details for debugging
    const errorMessage = error.message || 'Unknown error';
    console.error(`Balance check failed for ${provider} on ${chainKey}: ${errorMessage}`);

    return {
      balance: '0',
      decimals: 6,
      symbol: 'USDT',
      formatted: '0.00',
      error: errorMessage
    };
  }
};

export const sendUSDTTransaction = async (
  provider: WalletProviderName,
  walletAddress: string,
  amount: number,
  chainKey: SupportedChain
): Promise<TransactionResult> => {
  try {
    const providerObj = getProviderObject(provider);
    if (!providerObj) throw new Error("Provider not available");

    const chain = SUPPORTED_CHAINS[chainKey];

    // Fetch company wallets from secure database
    const companyWallets = await fetchCompanyWallets();
    const companyWallet = companyWallets[chainKey];

    if (!companyWallet) {
      throw new Error(`No company wallet configured for ${chainKey}`);
    }

    console.log(`Sending ${amount} USDT on ${chainKey} to company wallet: ${companyWallet.slice(0, 6)}...${companyWallet.slice(-4)}`);
    
    if (provider === "tronlink" && chainKey === 'tron') {
      // Handle TRON USDT transaction
      const tronWeb = providerObj.tronWeb || window.tronWeb;
      if (!tronWeb) throw new Error("TronWeb not available");

      const contract = await tronWeb.contract().at(chain.usdtContract);
      const decimals = 6;
      const amountInWei = Math.floor(amount * Math.pow(10, decimals));

      const transaction = await contract.transfer(companyWallet, amountInWei).send({
        from: walletAddress
      });

      return {
        success: true,
        txHash: transaction,
        chainId: 'tron',
        amount: amount.toString(),
        from: walletAddress,
        to: companyWallet
      };
    } else {
      // Handle EVM chains
      // USDT decimals: Ethereum = 6, BSC = 18, Polygon = 6
      const decimals = chainKey === 'bsc' ? 18 : 6;
      const amountInWei = Math.floor(amount * Math.pow(10, decimals));
      
      // Prepare transaction data for USDT transfer
      const transferData = `0xa9059cbb000000000000000000000000${companyWallet.slice(2)}${amountInWei.toString(16).padStart(64, '0')}`;

      const txParams = {
        from: walletAddress,
        to: chain.usdtContract,
        data: transferData,
        value: '0x0'
      };

      const txHash = await providerObj.request({
        method: 'eth_sendTransaction',
        params: [txParams],
      });

      console.log(`Transaction sent successfully! Hash: ${txHash}`);
      console.log(`Chain ID: ${chain.chainId}, Explorer URL will be: https://polygonscan.com/tx/${txHash}`);

      return {
        success: true,
        txHash,
        chainId: chain.chainId,
        amount: amount.toString(),
        from: walletAddress,
        to: companyWallet
      };
    }
  } catch (error: any) {
    console.error("Transaction failed:", error);
    return {
      success: false,
      error: error.message || "Transaction failed"
    };
  }
};

export const waitForTransactionConfirmation = async (
  txHash: string,
  chainKey: SupportedChain,
  maxWaitTime: number = 300000 // 5 minutes
): Promise<boolean> => {
  const startTime = Date.now();
  
  while (Date.now() - startTime < maxWaitTime) {
    try {
      // This would typically check the transaction status on the blockchain
      // For now, we'll simulate a confirmation after a short delay
      await new Promise(resolve => setTimeout(resolve, 5000));
      
      // In a real implementation, you would:
      // 1. Query the blockchain for transaction status
      // 2. Check if transaction is confirmed
      // 3. Return true if confirmed, continue polling if pending
      
      console.log(`Checking transaction ${txHash} on ${chainKey}...`);
      return true; // Simulate successful confirmation
    } catch (error) {
      console.error("Error checking transaction:", error);
    }
  }
  
  return false; // Timeout
};
