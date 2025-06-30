
import { WalletProviderName } from "../useWalletConnection";
import { type toast } from "@/hooks/use-toast";

export interface WalletState {
  setWalletAddress: (address: string) => void;
  setChainId: (chainId: string | null) => void;
  setCurrentProvider: (provider: WalletProviderName | null) => void;
  setConnectionError: (error: string | null) => void;
}

export interface ConnectWalletHelperParams extends WalletState {
  provider: WalletProviderName;
  setIsConnecting: (isConnecting: boolean) => void;
  toast: typeof toast;
}

export interface SwitchChainHelperParams {
  targetChainId: string;
  walletAddress: string | null;
  setChainId: (chainId: string) => void;
  toast: typeof toast;
}

export interface DisconnectWalletHelperParams extends WalletState {
  toast: typeof toast;
}
