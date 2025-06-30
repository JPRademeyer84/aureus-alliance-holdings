
import { WalletProviderName } from "../useWalletConnection";
import { getProviderObject, isWalletProviderAvailable, getWalletProviderDisplayName } from "./walletProviders";
import { logWalletConnection } from "./supabaseLogger";
import { setCurrentProviderState } from "./providerUtils";
import { ConnectWalletHelperParams } from "./types";

const handleSafepalConnection = async (providerObj: any): Promise<string[]> => {
  console.log("SafePal connection attempt with provider:", providerObj);
  console.log("Provider properties:", {
    isSafePal: providerObj.isSafePal,
    isSafeWallet: providerObj.isSafeWallet,
    hasRequest: typeof providerObj.request === 'function',
    hasSendAsync: typeof providerObj.sendAsync === 'function',
    hasEthereum: !!providerObj.ethereum,
    selectedAddress: providerObj.selectedAddress,
    accounts: providerObj.accounts
  });

  // Strategy 1: Direct SafePal identification
  if (providerObj.isSafePal || providerObj.isSafeWallet) {
    console.log("Using SafePal direct identification");
    return await providerObj.request({ method: "eth_requestAccounts" });
  }

  // Strategy 2: Standard request method
  if (typeof providerObj.request === 'function') {
    console.log("Using standard request method");
    return await providerObj.request({ method: "eth_requestAccounts" });
  }

  // Strategy 3: SafePal's ethereum sub-object
  if (providerObj.ethereum && typeof providerObj.ethereum.request === 'function') {
    console.log("Using ethereum sub-object");
    return await providerObj.ethereum.request({ method: "eth_requestAccounts" });
  }

  // Strategy 4: sendAsync method (legacy)
  if (typeof providerObj.sendAsync === 'function') {
    console.log("Using sendAsync method");
    return await new Promise<string[]>((resolve, reject) => {
      try {
        providerObj.sendAsync({
          method: "eth_requestAccounts",
          params: [],
          jsonrpc: "2.0",
          id: Math.floor(Math.random() * 1000)
        }, (error: Error, response: any) => {
          if (error) {
            reject(error);
          } else {
            resolve(response.result || []);
          }
        });
      } catch (syncError) {
        reject(syncError);
      }
    });
  }

  // Strategy 5: Check if accounts are already available
  if (providerObj.selectedAddress) {
    console.log("Using selectedAddress");
    return [providerObj.selectedAddress];
  }

  if (providerObj.accounts && Array.isArray(providerObj.accounts) && providerObj.accounts.length > 0) {
    console.log("Using existing accounts");
    return providerObj.accounts;
  }

  console.log("No connection method available for SafePal");
  return [];
};

const getChainId = async (provider: WalletProviderName, providerObj: any): Promise<string | undefined> => {
  try {
    if (provider === "safepal" && typeof providerObj.request !== 'function') {
      if (typeof providerObj.sendAsync === 'function') {
        return await new Promise((resolve, reject) => {
          providerObj.sendAsync({
            method: "eth_chainId",
            params: [],
            jsonrpc: "2.0",
            id: Math.floor(Math.random() * 1000)
          }, (error: Error, response: any) => {
            if (error) reject(error);
            else resolve(response.result);
          });
        });
      } else if (providerObj.ethereum && typeof providerObj.ethereum.request === 'function') {
        return await providerObj.ethereum.request({ method: "eth_chainId" });
      }
    } else {
      return await providerObj.request({ method: "eth_chainId" });
    }
  } catch (chainError) {
    console.warn("Could not get chain ID:", chainError);
    return undefined;
  }
};

export const connectWallet = async ({
  provider,
  setIsConnecting,
  setConnectionError,
  toast,
  setWalletAddress,
  setCurrentProvider,
  setChainId,
}: ConnectWalletHelperParams): Promise<void> => {
  setIsConnecting(true);
  setConnectionError(null);

  // Clear any existing connection first (for wallet switching)
  setWalletAddress("");
  setCurrentProvider(null);
  setChainId(null);
  
  try {
    const providerObj = getProviderObject(provider);
    const providerName = getWalletProviderDisplayName(provider);
    
    if (!providerObj) {
      throw new Error(`${providerName} wallet not detected. Please install the extension and refresh.`);
    }
    
    console.log(`Attempting to connect to ${providerName} with provider:`, providerObj);
    
    if (!isWalletProviderAvailable(provider)) {
      throw new Error(`${providerName} wallet is not configured properly. Please update your wallet.`);
    }
    
    let accounts: string[] = [];

    try {
      if (provider === "safepal") {
        // For SafePal, try direct connection first (no timeout) to allow user interaction
        console.log("Attempting SafePal connection...");
        try {
          accounts = await handleSafepalConnection(providerObj);
          console.log("SafePal connection successful:", accounts);
        } catch (directError) {
          console.log("Direct SafePal connection failed, trying with timeout:", directError);

          // Create timeout promise that properly handles rejection
          const timeoutPromise = new Promise<string[]>((_, reject) => {
            setTimeout(() => reject(new Error("Connection timed out - please check if your wallet is unlocked and try again")), 30000);
          });

          // If direct connection fails, try with timeout and proper error handling
          try {
            accounts = await Promise.race([
              handleSafepalConnection(providerObj),
              timeoutPromise
            ]);
          } catch (timeoutError) {
            // Handle timeout or connection error properly
            throw timeoutError;
          }
        }
      } else {
        throw new Error("Only SafePal wallet is supported");
      }
      
      if (!accounts || accounts.length === 0) {
        if (typeof providerObj.request === 'function') {
          accounts = await providerObj.request({ method: "eth_accounts" });
        }
      }
      
      console.log(`${providerName} connection attempt returned accounts:`, accounts);
    } catch (error: any) {
      // Only log non-user-rejection errors to avoid console spam
      if (error.code !== 4001) {
        console.error(`Error requesting accounts from ${providerName}:`, error);
      } else {
        console.log(`User cancelled wallet connection for ${providerName}`);
      }

      // Handle specific error codes
      if (error.code === 4001) {
        throw new Error("Connection cancelled - You rejected the wallet connection request. Please try again and approve the connection to continue.");
      } else if (error.code === -32002) {
        throw new Error("Connection pending - Please check your wallet for a pending connection request.");
      } else if (error.code === -32603) {
        throw new Error("Wallet error - Please unlock your wallet and try again.");
      }

      throw error;
    }
    
    if (accounts && accounts.length > 0) {
      setWalletAddress(accounts[0]);
      setCurrentProvider(provider);
      setCurrentProviderState(provider, setCurrentProvider);

      const currentChainId = await getChainId(provider, providerObj);
      if (currentChainId) {
        setChainId(currentChainId);
      }

      // Save wallet connection to localStorage for persistence
      console.log(`Saving wallet connection - Provider: ${provider}, Address: ${accounts[0]}`);
      localStorage.setItem('wallet_address', accounts[0]);
      localStorage.setItem('wallet_provider', provider);
      if (currentChainId) {
        localStorage.setItem('wallet_chain_id', currentChainId);
      }

      await logWalletConnection({
        provider,
        address: accounts[0],
        chainId: currentChainId || "unknown"
      });

      toast({
        title: "Wallet Connected",
        description: `Connected to ${accounts[0].substring(0, 6)}...${accounts[0].substring(accounts[0].length - 4)} (${providerName})`,
      });
    } else {
      throw new Error("No accounts returned from wallet");
    }
  } catch (error: any) {
    // Only log non-user-rejection errors to avoid console spam
    if (error?.code !== 4001 && !error?.message?.includes("Connection cancelled")) {
      console.error("Wallet connection error:", error);
    } else {
      console.log("User cancelled wallet connection");
    }

    let errorMessage = "Failed to connect wallet. Please try again.";
    let toastTitle = "Connection Error";

    if (error instanceof Error) {
      if (error.message.includes("timed out")) {
        errorMessage = `Connection to ${getWalletProviderDisplayName(provider)} timed out. Please check if your wallet is unlocked and try again.`;
        toastTitle = "Connection Timeout";
      } else if (error.message.includes("Connection cancelled") || error.message.includes("User rejected") || error.message.includes("user rejected") || error.code === 4001) {
        errorMessage = "You cancelled the wallet connection. To invest, please connect your wallet by clicking the connect button and approving the request.";
        toastTitle = "Connection Cancelled";
      } else if (error.message.includes("Connection pending") || error.code === -32002) {
        errorMessage = "Please check your wallet for a pending connection request and approve it.";
        toastTitle = "Connection Pending";
      } else if (error.message.includes("Wallet error") || error.code === -32603) {
        errorMessage = "Please unlock your wallet and try connecting again.";
        toastTitle = "Wallet Locked";
      } else if (error.message.includes("wallet_requestPermissions")) {
        errorMessage = "This wallet doesn't support the required permissions. Please try with SafePal wallet.";
        toastTitle = "Wallet Not Supported";
      } else {
        errorMessage = `Connection failed: ${error.message}`;
      }
    }

    setConnectionError(errorMessage);
    toast({
      title: toastTitle,
      description: errorMessage,
      variant: "destructive"
    });
  } finally {
    setIsConnecting(false);
  }
};

// Alias the function for compatibility
export const connectWalletHelper = connectWallet;
