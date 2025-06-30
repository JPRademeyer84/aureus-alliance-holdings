
import { DisconnectWalletHelperParams } from "./types";
import { resetProviderState } from "./providerUtils";

export const disconnectWalletHelper = ({
  setWalletAddress,
  setChainId,
  setCurrentProvider,
  setConnectionError,
  toast,
}: DisconnectWalletHelperParams): void => {
  resetProviderState({
    setWalletAddress,
    setChainId,
    setCurrentProvider,
    setConnectionError
  });

  // Clear saved wallet connection from localStorage
  localStorage.removeItem('wallet_address');
  localStorage.removeItem('wallet_provider');
  localStorage.removeItem('wallet_chain_id');

  toast({
    title: "Wallet Disconnected",
    description: "Your wallet has been disconnected.",
  });
};
