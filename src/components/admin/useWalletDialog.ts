
import { useState } from "react";
import { InvestmentWallet } from "./WalletManager";

interface WalletDialogState {
  isDialogOpen: boolean;
  editingWallet: InvestmentWallet | null;
}

export function useWalletDialog() {
  const [state, setState] = useState<WalletDialogState>({
    isDialogOpen: false,
    editingWallet: null,
  });

  const openDialog = (wallet?: InvestmentWallet) => {
    setState({
      isDialogOpen: true,
      editingWallet: wallet ?? null,
    });
  };

  const closeDialog = () => {
    setState((curr) => ({
      ...curr,
      isDialogOpen: false,
    }));
  };

  return {
    isDialogOpen: state.isDialogOpen,
    editingWallet: state.editingWallet,
    openDialog,
    closeDialog,
  };
}
