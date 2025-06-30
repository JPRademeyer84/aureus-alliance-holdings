import React, { useState, useEffect } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { useToast } from "@/hooks/use-toast";
import {
  Plus,
  Edit,
  Trash2,
  Eye,
  EyeOff,
  Shield,
  Download,
  RefreshCw,
  AlertTriangle,
  CheckCircle
} from "@/components/SafeIcons";

// Safe wallet icon
const Wallet = ({ className }: { className?: string }) => <span className={className}>👛</span>;
import ApiConfig from "@/config/api";

interface WalletData {
  id: string;
  chain: string;
  masked_address: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface WalletManagementProps {
  adminId: string;
}

const WalletManagement: React.FC<WalletManagementProps> = ({ adminId }) => {
  const { toast } = useToast();
  const [wallets, setWallets] = useState<WalletData[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [editingWallet, setEditingWallet] = useState<string | null>(null);
  const [formData, setFormData] = useState({
    chain: '',
    address: '',
    is_active: true
  });

  const supportedChains = [
    { key: 'ethereum', name: 'Ethereum', icon: '⟠' },
    { key: 'bsc', name: 'BNB Smart Chain', icon: '🟡' },
    { key: 'polygon', name: 'Polygon', icon: '🟣' },
    { key: 'tron', name: 'TRON', icon: '🔴' }
  ];

  useEffect(() => {
    loadWallets();
  }, []);

  const loadWallets = async () => {
    setIsLoading(true);
    console.log('Loading wallets with adminId:', adminId);

    try {
      const requestBody = {
        action: 'list',
        adminId
      };

      console.log('Load wallets request:', requestBody);

      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody)
      });

      console.log('Load wallets response status:', response.status);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('Load wallets response data:', data);

      if (data.success) {
        setWallets(data.data);
      } else {
        throw new Error(data.error || 'Failed to load wallets');
      }
    } catch (error: any) {
      console.error('Load wallets error:', error);
      toast({
        title: "Error",
        description: error.message || "Failed to load wallets",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateWallet = async () => {
    if (!formData.chain || !formData.address) {
      toast({
        title: "Validation Error",
        description: "Chain and address are required",
        variant: "destructive"
      });
      return;
    }

    console.log('Creating wallet with adminId:', adminId);
    console.log('API endpoint:', 'http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php');

    try {
      const requestBody = {
        action: 'create',
        adminId,
        chain: formData.chain,
        address: formData.address
      };

      console.log('Request body:', requestBody);

      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody)
      });

      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        toast({
          title: "Success",
          description: "Wallet created successfully"
        });
        setShowCreateForm(false);
        setFormData({ chain: '', address: '', is_active: true });
        loadWallets();
      } else {
        throw new Error(data.error || 'Unknown error occurred');
      }
    } catch (error: any) {
      console.error('Create wallet error:', error);
      toast({
        title: "Error",
        description: error.message || "Failed to create wallet",
        variant: "destructive"
      });
    }
  };

  const handleUpdateWallet = async (chain: string, updates: any) => {
    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update',
          adminId,
          chain,
          ...updates
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: "Wallet updated successfully"
        });
        loadWallets();
      } else {
        throw new Error(data.error);
      }
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to update wallet",
        variant: "destructive"
      });
    }
  };

  const handleDeleteWallet = async (chain: string) => {
    if (!confirm(`Are you sure you want to delete the ${chain} wallet? This action cannot be undone.`)) {
      return;
    }

    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete',
          adminId,
          chain
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: "Wallet deleted successfully"
        });
        loadWallets();
      } else {
        throw new Error(data.error);
      }
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to delete wallet",
        variant: "destructive"
      });
    }
  };

  const handleBackupWallets = async () => {
    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-wallets.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'backup',
          adminId
        })
      });

      const data = await response.json();
      if (data.success) {
        // Download backup as JSON file
        const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `wallet-backup-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        toast({
          title: "Success",
          description: "Wallet backup downloaded successfully"
        });
      } else {
        throw new Error(data.error);
      }
    } catch (error: any) {
      toast({
        title: "Error",
        description: error.message || "Failed to create backup",
        variant: "destructive"
      });
    }
  };

  const getChainInfo = (chain: string) => {
    return supportedChains.find(c => c.key === chain) || { key: chain, name: chain, icon: '🔗' };
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">Wallet Management</h2>
          <p className="text-gray-400">Manage secure company wallet addresses for payments</p>
        </div>
        <div className="flex gap-2">
          <Button
            onClick={loadWallets}
            variant="outline"
            disabled={isLoading}
            className="border-gold/30 text-white hover:bg-gold/10"
          >
            <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
          <Button
            onClick={handleBackupWallets}
            variant="outline"
            className="border-blue-500/30 text-blue-400 hover:bg-blue-500/10"
          >
            <Download className="h-4 w-4 mr-2" />
            Backup
          </Button>
          <Button
            onClick={() => setShowCreateForm(true)}
            className="bg-gold-gradient text-black font-semibold"
          >
            <Plus className="h-4 w-4 mr-2" />
            Add Wallet
          </Button>
        </div>
      </div>

      {/* Security Notice */}
      <Card className="bg-blue-500/10 border-blue-500/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <Shield className="h-5 w-5 text-blue-400 mt-0.5" />
            <div>
              <h3 className="text-blue-400 font-semibold mb-1">Security Notice</h3>
              <p className="text-blue-300 text-sm">
                Wallet addresses are encrypted and stored securely in the database. Only authorized admins can view or modify them.
                All operations are logged for audit purposes.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Create Wallet Form */}
      {showCreateForm && (
        <Card className="bg-charcoal border-gold/30">
          <CardHeader>
            <CardTitle className="text-white">Add New Wallet</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="chain" className="text-white">Blockchain</Label>
              <select
                id="chain"
                value={formData.chain}
                onChange={(e) => setFormData({ ...formData, chain: e.target.value })}
                className="w-full mt-1 p-2 bg-charcoal border border-gray-600 rounded text-white"
              >
                <option value="">Select blockchain...</option>
                {supportedChains.map(chain => (
                  <option key={chain.key} value={chain.key}>
                    {chain.icon} {chain.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <Label htmlFor="address" className="text-white">Wallet Address</Label>
              <Input
                id="address"
                value={formData.address}
                onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                placeholder="Enter wallet address..."
                className="bg-charcoal border-gray-600 text-white"
              />
            </div>
            <div className="flex gap-2">
              <Button onClick={handleCreateWallet} className="bg-gold-gradient text-black">
                Create Wallet
              </Button>
              <Button 
                onClick={() => {
                  setShowCreateForm(false);
                  setFormData({ chain: '', address: '', is_active: true });
                }}
                variant="outline"
                className="border-gray-600 text-white"
              >
                Cancel
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Wallets List */}
      <div className="grid gap-4">
        {isLoading ? (
          <Card className="bg-charcoal border-gray-600">
            <CardContent className="p-8 text-center">
              <RefreshCw className="h-8 w-8 animate-spin text-gold mx-auto mb-4" />
              <p className="text-white">Loading wallets...</p>
            </CardContent>
          </Card>
        ) : wallets.length === 0 ? (
          <Card className="bg-charcoal border-gray-600">
            <CardContent className="p-8 text-center">
              <Wallet className="h-12 w-12 text-gray-500 mx-auto mb-4" />
              <p className="text-white mb-2">No wallets configured</p>
              <p className="text-gray-400 text-sm">Add wallet addresses to start receiving payments</p>
            </CardContent>
          </Card>
        ) : (
          wallets.map((wallet) => {
            const chainInfo = getChainInfo(wallet.chain);
            return (
              <Card key={wallet.id} className="bg-charcoal border-gray-600">
                <CardContent className="p-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="text-2xl">{chainInfo.icon}</div>
                      <div>
                        <h3 className="text-white font-semibold">{chainInfo.name}</h3>
                        <p className="text-gray-400 text-sm font-mono">{wallet.masked_address}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge className={wallet.is_active ? 'bg-green-500/10 border-green-500/30 text-green-400' : 'bg-red-500/10 border-red-500/30 text-red-400'}>
                        {wallet.is_active ? (
                          <>
                            <CheckCircle className="h-3 w-3 mr-1" />
                            Active
                          </>
                        ) : (
                          <>
                            <AlertTriangle className="h-3 w-3 mr-1" />
                            Inactive
                          </>
                        )}
                      </Badge>
                      <Switch
                        checked={wallet.is_active}
                        onCheckedChange={(checked) => handleUpdateWallet(wallet.chain, { is_active: checked })}
                      />
                      <Button
                        onClick={() => handleDeleteWallet(wallet.chain)}
                        variant="ghost"
                        size="sm"
                        className="text-red-400 hover:text-red-300 hover:bg-red-500/10"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            );
          })
        )}
      </div>
    </div>
  );
};

export default WalletManagement;
