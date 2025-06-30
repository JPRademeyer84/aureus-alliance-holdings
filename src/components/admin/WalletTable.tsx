
import React from "react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Edit, Trash, Wallet } from "lucide-react";
import { InvestmentWallet } from "./WalletManager";

interface Props {
  wallets: InvestmentWallet[];
  isLoading: boolean;
  onEdit: (wallet: InvestmentWallet) => void;
  onDelete: (id: string) => void;
  onToggleActive: (id: string, currentState: boolean) => void;
}

const WalletTable: React.FC<Props> = ({
  wallets,
  isLoading,
  onEdit,
  onDelete,
  onToggleActive
}) => {
  if (isLoading) {
    return (
      <div className="flex justify-center p-8">
        <Wallet className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  if (wallets.length === 0) {
    return (
      <div className="text-center py-12 bg-black/40 rounded-lg border">
        <Wallet className="h-12 w-12 mx-auto mb-4 text-zinc-400" />
        <h3 className="text-xl font-medium mb-2 text-white">No Wallets Yet</h3>
        <p className="text-zinc-200 mb-4">Click the "Add Wallet" button to add your first payment wallet.</p>
      </div>
    );
  }

  return (
    <div className="border rounded-lg overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="text-white bg-black/60">Blockchain</TableHead>
            <TableHead className="text-white bg-black/60">Wallet Address</TableHead>
            <TableHead className="text-white bg-black/60">Status</TableHead>
            <TableHead className="text-white bg-black/60">Last Updated</TableHead>
            <TableHead className="text-white bg-black/60">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {wallets.map((wallet) => (
            <TableRow key={wallet.id} className="hover:bg-black/30">
              <TableCell className="font-medium text-white">{wallet.chain}</TableCell>
              <TableCell>
                <span className="font-mono text-white">
                  {wallet.address.substring(0, 8)}...{wallet.address.substring(wallet.address.length - 6)}
                </span>
              </TableCell>
              <TableCell>
                <span className={`px-2 py-1 rounded text-xs font-medium ${
                  wallet.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                  {wallet.is_active ? 'Active' : 'Inactive'}
                </span>
              </TableCell>
              <TableCell className="text-zinc-300">{new Date(wallet.updated_at).toLocaleString()}</TableCell>
              <TableCell>
                <div className="flex space-x-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => onEdit(wallet)}
                  >
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className={wallet.is_active ? "text-red-500 hover:text-red-700" : "text-green-500 hover:text-green-700"}
                    onClick={() => onToggleActive(wallet.id, wallet.is_active)}
                  >
                    {wallet.is_active ? "Deactivate" : "Activate"}
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="text-red-500 hover:text-red-700"
                    onClick={() => onDelete(wallet.id)}
                  >
                    <Trash className="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
};

export default WalletTable;
