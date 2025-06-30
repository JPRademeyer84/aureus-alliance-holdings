
import { WalletProviderName } from "../useWalletConnection";
import { WalletState } from "./types";

export function setCurrentProviderLocal(provider: WalletProviderName): void {
  localStorage.setItem("walletProvider", provider);
}

export function getLocalProvider(): WalletProviderName | null {
  return localStorage.getItem("walletProvider") as WalletProviderName | null;
}

export function setCurrentProviderState(provider: WalletProviderName, setCurrentProvider: (provider: WalletProviderName | null) => void): void {
  setCurrentProviderLocal(provider);
  setCurrentProvider(provider);
}

export function resetProviderState(walletState: WalletState): void {
  const { setWalletAddress, setChainId, setCurrentProvider, setConnectionError } = walletState;
  setWalletAddress("");
  setChainId(null);
  setCurrentProvider(null);
  setConnectionError(null);
  localStorage.removeItem("walletProvider");
}
