
import React, { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Save } from 'lucide-react';
import { InvestmentWallet } from './WalletManager';
import ChainDropdown, { BLOCKCHAINS } from './ChainDropdown';
import WalletConnectButton from './WalletConnectButton';

interface Props {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSave: (
    values: Partial<InvestmentWallet>,
    isEditing: boolean
  ) => Promise<boolean | void>;
  wallet: InvestmentWallet | null;
}

const WalletDialog: React.FC<Props> = ({ open, onOpenChange, onSave, wallet }) => {
  const [chain, setChain] = useState('');
  const [address, setAddress] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (wallet) {
      setChain(wallet.chain);
      setAddress(wallet.address);
      setIsActive(wallet.is_active);
    } else {
      setChain('');
      setAddress('');
      setIsActive(true);
    }
  }, [wallet, open]);

  const handleSave = async () => {
    setSaving(true);
    const result = await onSave(
      {
        ...(wallet ? { id: wallet.id } : {}),
        chain,
        address,
        is_active: isActive,
      },
      !!wallet
    );
    setSaving(false);
    if (result) onOpenChange(false);
  };

  const handleConnect = (connectedAddress?: string) => {
    if (connectedAddress) setAddress(connectedAddress);
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md bg-neutral-900 text-white">
        <DialogHeader>
          <DialogTitle className="text-xl font-bold text-gold">
            {wallet ? 'Edit Wallet' : 'Add New Wallet'}
          </DialogTitle>
          <DialogDescription className="mb-2 text-neutral-300">
            {wallet
              ? 'Update the details of this payment wallet.'
              : 'Add a new wallet address to receive payments.'}
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-4 py-2">
          <div className="space-y-2">
            <label htmlFor="chain" className="text-sm font-medium text-gold">
              Blockchain Network
            </label>
            <ChainDropdown
              value={chain}
              onChange={setChain}
            />
          </div>

          <div className="space-y-2">
            <label htmlFor="address" className="text-sm font-medium text-gold">
              Wallet Address
            </label>
            <div className="flex gap-2 items-center">
              <Input
                id="address"
                value={address}
                autoComplete="off"
                placeholder="Wallet address"
                onChange={(e) => setAddress(e.target.value)}
                className="flex-1 bg-black/40 border-gold text-white"
              />
              <WalletConnectButton
                chain={chain}
                onConnect={handleConnect}
              />
            </div>
          </div>

          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="is_active"
              checked={isActive}
              onChange={(e) => setIsActive(e.target.checked)}
              className="h-4 w-4 accent-gold"
            />
            <label htmlFor="is_active" className="text-sm font-medium text-gold">
              Wallet Active
            </label>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} className="border-gold text-gold" disabled={saving}>
            Cancel
          </Button>
          <Button onClick={handleSave} className="bg-gold-gradient text-black" disabled={saving}>
            <Save className="h-4 w-4 mr-2" />
            {wallet ? 'Update' : 'Add'} Wallet
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default WalletDialog;
