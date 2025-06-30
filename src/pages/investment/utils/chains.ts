
// Utility file for blockchain chain IDs and metadata

// Define supported chain IDs
export const CHAIN_IDS = {
  ethereum: "0x1", // Ethereum Mainnet
  bnb: "0x38", // BNB Smart Chain Mainnet
  polygon: "0x89", // Polygon Mainnet
  avalanche: "0xa86a", // Avalanche C-Chain
  tron: "0x2b6653dc" // TRON Mainnet
};

// Define a consistent type for all chain metadata
export interface ChainMetadata {
  chainId: string;
  name: string;
  symbol: string;
  decimals: number;
  rpcUrl: string;
  blockExplorerUrl: string;
}

// Chain metadata for adding networks to wallet - making sure all entries have consistent properties
export const CHAINS: Record<string, ChainMetadata> = {
  "0x1": { 
    chainId: "0x1",
    name: "Ethereum Mainnet", 
    symbol: "ETH", 
    decimals: 18,
    rpcUrl: "https://mainnet.infura.io/v3/9aa3d95b3bc440fa88ea12eaa4456161",
    blockExplorerUrl: "https://etherscan.io"
  },
  "0x38": { 
    chainId: "0x38",
    name: "BNB Smart Chain", 
    symbol: "BNB", 
    decimals: 18,
    rpcUrl: "https://bsc-dataseed.binance.org/",
    blockExplorerUrl: "https://bscscan.com"
  },
  "0x89": { 
    chainId: "0x89",
    name: "Polygon Mainnet", 
    symbol: "MATIC",
    decimals: 18,
    rpcUrl: "https://polygon-rpc.com/",
    blockExplorerUrl: "https://polygonscan.com/"
  },
  "0xa86a": {
    chainId: "0xa86a",
    name: "Avalanche C-Chain",
    symbol: "AVAX",
    decimals: 18,
    rpcUrl: "https://api.avax.network/ext/bc/C/rpc",
    blockExplorerUrl: "https://snowtrace.io/"
  },
  "0x2b6653dc": {
    chainId: "0x2b6653dc",
    name: "TRON Mainnet",
    symbol: "TRX",
    decimals: 6,
    rpcUrl: "https://api.trongrid.io",
    blockExplorerUrl: "https://tronscan.org"
  }
};
