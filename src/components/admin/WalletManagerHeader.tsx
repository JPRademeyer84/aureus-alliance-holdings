
import React from "react";
import { Button } from "@/components/ui/button";
import { Plus } from "lucide-react";

interface Props {
  onAdd: () => void;
}

const WalletManagerHeader: React.FC<Props> = ({ onAdd }) => (
  <div className="flex justify-between items-center mb-6">
    <h2 className="text-2xl font-semibold text-neutral-100">Payment Wallets</h2>
    <Button
      className="bg-green-600 hover:bg-green-700 text-white"
      onClick={onAdd}
    >
      <Plus className="h-4 w-4 mr-2" />
      Add Wallet
    </Button>
  </div>
);

export default WalletManagerHeader;
