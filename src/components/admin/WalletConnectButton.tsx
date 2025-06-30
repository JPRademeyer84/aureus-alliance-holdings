
import React from 'react';
import { Button } from '@/components/ui/button';
import { Wallet, Bitcoin, CreditCard } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

// Supported chains for wallet connect feature
const SUPPORT_CONNECT = ["Ethereum", "BNB Chain"];

interface Props {
  chain: string;
  onConnect: (address: string) => void;
}

const WalletConnectButton: React.FC<Props> = ({ chain, onConnect }) => {
  const { toast } = useToast();
  
  if (!SUPPORT_CONNECT.includes(chain)) return null;
  
  const connectWallet = async () => {
    try {
      // Check for SafePal wallet specifically
      const safepal = window.safepal?.ethereum || window.ethereum;

      if (!safepal || (!safepal.isSafePal && !safepal.isSafeWallet)) {
        toast({
          title: "SafePal Wallet Not Found",
          description: "Please install SafePal wallet extension",
          variant: "destructive"
        });
        return;
      }

      // Request account access
      const accounts = await safepal.request({ method: 'eth_requestAccounts' });

      if (accounts && accounts.length > 0) {
        const address = accounts[0];
        toast({
          title: "SafePal Connected",
          description: `Connected to ${address.substring(0, 6)}...${address.substring(address.length - 4)}`
        });
        onConnect(address);
      }
    } catch (error: any) {
      // Only log non-user-rejection errors
      if (error?.code !== 4001) {
        console.error("Wallet connection error:", error);
      } else {
        console.log("User cancelled wallet connection");
      }

      let errorMessage = "Failed to connect wallet";
      let toastTitle = "Connection Error";

      if (error?.code === 4001 || (error instanceof Error && error.message.includes("User rejected"))) {
        errorMessage = "You cancelled the wallet connection. Please try again and approve the connection.";
        toastTitle = "Connection Cancelled";
      } else if (error?.code === -32002) {
        errorMessage = "Please check your SafePal wallet for a pending connection request.";
        toastTitle = "Connection Pending";
      }

      toast({
        title: toastTitle,
        description: errorMessage,
        variant: "destructive"
      });
    }
  };
  
  return (
    <Button
      type="button"
      variant="outline"
      size="sm"
      className="flex items-center gap-1 bg-gold/20 text-gold hover:bg-gold/30"
      onClick={connectWallet}
      title={`Connect your ${chain} wallet`}
    >
      {chain === "Ethereum" ? <CreditCard className="h-4 w-4" /> : null}
      {chain === "BNB Chain" ? <Wallet className="h-4 w-4" /> : null}
      Connect
    </Button>
  );
};

export default WalletConnectButton;
