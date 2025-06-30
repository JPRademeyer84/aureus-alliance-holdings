import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/hooks/use-toast';
import { useAdmin } from '@/contexts/AdminContext';
import ApiConfig from '@/config/api';
import { Plus, Edit, Trash2, Star, TrendingUp, DollarSign, Award, X, Save, Shield } from 'lucide-react';

interface CommissionPlan {
  id: string;
  plan_name: string;
  description: string;
  is_active: boolean;
  is_default: boolean;
  level_1_usdt_percent: number;
  level_1_nft_percent: number;
  level_2_usdt_percent: number;
  level_2_nft_percent: number;
  level_3_usdt_percent: number;
  level_3_nft_percent: number;
  nft_pack_price: number;
  nft_total_supply: number;
  nft_remaining_supply: number;
  max_levels: number;
  minimum_investment: number;
  commission_cap: number | null;
  created_at: string;
  updated_at: string;
  usage_stats: {
    total_transactions: number;
    total_usdt_paid: number;
    total_nft_paid: number;
    pending_transactions: number;
  };
}

interface CommissionStats {
  plans: {
    total: number;
    active: number;
    default: number;
  };
  transactions: {
    total: number;
    pending: number;
    paid: number;
    total_usdt_commissions: number;
    total_nft_commissions: number;
  };
}

const CommissionPlansManager: React.FC = () => {
  const [plans, setPlans] = useState<CommissionPlan[]>([]);
  const [stats, setStats] = useState<CommissionStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [editingPlan, setEditingPlan] = useState<CommissionPlan | null>(null);
  const [formData, setFormData] = useState({
    plan_name: '',
    description: '',
    is_active: true,
    level_1_usdt_percent: 12,
    level_1_nft_percent: 12,
    level_2_usdt_percent: 5,
    level_2_nft_percent: 5,
    level_3_usdt_percent: 3,
    level_3_nft_percent: 3,
    nft_pack_price: 5,
    nft_total_supply: 200000,
    max_levels: 3,
    minimum_investment: 0,
    commission_cap: ''
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();
  const { admin } = useAdmin();

  useEffect(() => {
    // Only fetch data if admin is authenticated
    if (admin) {
      fetchPlans();
      fetchStats();
    } else {
      setIsLoading(false);
      console.log('Commission Plans Manager: No admin authenticated, skipping data fetch');
    }
  }, [admin]);

  const fetchPlans = async () => {
    if (!admin) {
      console.log('Commission Plans: No admin authenticated, skipping fetch');
      setIsLoading(false);
      return;
    }

    try {
      console.log('🔍 Fetching commission plans with admin:', admin.username);

      const response = await fetch(ApiConfig.endpoints.admin.commissionPlans, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // Include session cookies
        body: JSON.stringify({ action: 'list' })
      });

      console.log('🔍 Commission plans response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Commission plans HTTP error:', response.status, response.statusText, errorText);

        if (response.status === 401) {
          toast({
            title: "Authentication Error",
            description: "Admin session expired. Please log in again.",
            variant: "destructive"
          });
          return;
        }

        throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
      }

      const data = await response.json();
      console.log('🔍 Commission plans response data:', data);

      if (data.success) {
        setPlans(data.data);
        console.log('✅ Commission plans loaded successfully:', data.data.length, 'plans');
      } else {
        throw new Error(data.error || data.message || 'Failed to fetch commission plans');
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      console.error('❌ Failed to fetch commission plans:', errorMessage);
      console.error('🔍 Full error details:', error);
      toast({
        title: "Error",
        description: `Failed to load commission plans: ${errorMessage}`,
        variant: "destructive",
      });
    } finally {
      setIsLoading(false);
    }
  };

  const fetchStats = async () => {
    if (!admin) {
      console.log('Commission Stats: No admin authenticated, skipping fetch');
      return;
    }

    try {
      console.log('🔍 Fetching commission stats with admin:', admin.username);

      const response = await fetch(ApiConfig.endpoints.admin.commissionPlans, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // Include session cookies
        body: JSON.stringify({ action: 'stats' })
      });

      console.log('🔍 Commission stats response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Commission stats HTTP error:', response.status, response.statusText, errorText);

        if (response.status === 401) {
          toast({
            title: "Authentication Error",
            description: "Admin session expired. Please log in again.",
            variant: "destructive"
          });
          return;
        }

        throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
      }

      const data = await response.json();
      console.log('🔍 Commission stats response data:', data);

      if (data.success) {
        setStats(data.data);
        console.log('✅ Commission stats loaded successfully');
      } else {
        throw new Error(data.error || data.message || 'Failed to fetch commission stats');
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      console.error('❌ Failed to fetch commission stats:', errorMessage);
      console.error('🔍 Full error details:', error);

      toast({
        title: "Error",
        description: `Failed to fetch commission stats: ${errorMessage}`,
        variant: "destructive"
      });
    }
  };

  const setDefaultPlan = async (planId: string) => {
    try {
      const response = await fetch(ApiConfig.endpoints.admin.commissionPlans, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // Include session cookies
        body: JSON.stringify({
          action: 'set_default',
          plan_id: planId
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: "Default commission plan updated",
        });
        fetchPlans();
      } else {
        throw new Error(data.error || data.message || 'Failed to set default plan');
      }
    } catch (error) {
      console.error('Failed to set default plan:', error);
      toast({
        title: "Error",
        description: "Failed to set default plan",
        variant: "destructive",
      });
    }
  };

  const deletePlan = async (plan: CommissionPlan) => {
    // Confirm deletion
    const confirmDelete = window.confirm(
      `Are you sure you want to delete the commission plan "${plan.plan_name}"?\n\n` +
      `This action cannot be undone. The plan will only be deleted if it has no active transactions.`
    );

    if (!confirmDelete) return;

    try {
      console.log('🗑️ Deleting commission plan:', plan.plan_name);

      const response = await fetch(ApiConfig.endpoints.admin.commissionPlans, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // Include session cookies
        body: JSON.stringify({
          action: 'delete',
          plan_id: plan.id
        })
      });

      console.log('🔍 Delete response status:', response.status);

      const data = await response.json();
      console.log('🔍 Delete response data:', data);

      if (data.success) {
        toast({
          title: "Success",
          description: `Commission plan "${plan.plan_name}" deleted successfully`,
        });
        fetchPlans(); // Refresh the list
        fetchStats(); // Refresh stats
      } else {
        throw new Error(data.error || data.message || 'Failed to delete commission plan');
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      console.error('❌ Failed to delete commission plan:', errorMessage);
      console.error('🔍 Full error details:', error);

      toast({
        title: "Error",
        description: errorMessage.includes('default')
          ? "Cannot delete the default commission plan"
          : errorMessage.includes('transactions')
          ? "Cannot delete plan with existing transactions"
          : "Failed to delete commission plan",
        variant: "destructive",
      });
    }
  };

  const resetForm = () => {
    setFormData({
      plan_name: '',
      description: '',
      is_active: true,
      level_1_usdt_percent: 12,
      level_1_nft_percent: 12,
      level_2_usdt_percent: 5,
      level_2_nft_percent: 5,
      level_3_usdt_percent: 3,
      level_3_nft_percent: 3,
      nft_pack_price: 5,
      nft_total_supply: 200000,
      max_levels: 3,
      minimum_investment: 0,
      commission_cap: ''
    });
    setEditingPlan(null);
    setShowCreateForm(false);
  };

  const openEditForm = (plan: CommissionPlan) => {
    setFormData({
      plan_name: plan.plan_name,
      description: plan.description,
      is_active: plan.is_active,
      level_1_usdt_percent: plan.level_1_usdt_percent,
      level_1_nft_percent: plan.level_1_nft_percent,
      level_2_usdt_percent: plan.level_2_usdt_percent,
      level_2_nft_percent: plan.level_2_nft_percent,
      level_3_usdt_percent: plan.level_3_usdt_percent,
      level_3_nft_percent: plan.level_3_nft_percent,
      nft_pack_price: plan.nft_pack_price,
      nft_total_supply: plan.nft_total_supply,
      max_levels: plan.max_levels,
      minimum_investment: plan.minimum_investment,
      commission_cap: plan.commission_cap?.toString() || ''
    });
    setEditingPlan(plan);
    setShowCreateForm(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      const admin = JSON.parse(localStorage.getItem('admin') || '{}');
      const submitData = {
        ...formData,
        commission_cap: formData.commission_cap ? parseFloat(formData.commission_cap) : null,
        nft_remaining_supply: editingPlan ? editingPlan.nft_remaining_supply : formData.nft_total_supply
      };

      const requestData = editingPlan
        ? { action: 'update', plan_id: editingPlan.id, ...submitData }
        : { action: 'create', ...submitData };

      const response = await fetch(ApiConfig.endpoints.admin.commissionPlans, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include', // Include session cookies
        body: JSON.stringify(requestData)
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: editingPlan ? "Commission plan updated successfully" : "Commission plan created successfully",
        });
        resetForm();
        fetchPlans();
      } else {
        throw new Error(data.error || data.message || 'Failed to save commission plan');
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      console.error('Failed to save commission plan:', errorMessage, error);
      toast({
        title: "Error",
        description: "Failed to save commission plan",
        variant: "destructive",
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  const formatNumber = (num: number) => {
    return new Intl.NumberFormat('en-US').format(num);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold-400"></div>
      </div>
    );
  }

  // Check if admin is authenticated
  if (!admin) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <Shield className="w-16 h-16 mx-auto mb-4 text-gray-500" />
          <h3 className="text-lg font-semibold text-white mb-2">Authentication Required</h3>
          <p className="text-gray-400">Please log in as an admin to access Commission Plans.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-white">Commission Plans</h1>
          <p className="text-gray-400 mt-1">Manage referral commission structures</p>
        </div>
        <Button
          onClick={() => setShowCreateForm(true)}
          className="bg-gold-gradient text-black hover:opacity-90"
        >
          <Plus className="w-4 h-4 mr-2" />
          Create Plan
        </Button>
      </div>

      {/* Statistics Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">Total Plans</p>
                  <p className="text-2xl font-bold text-white">{stats.plans.total}</p>
                </div>
                <TrendingUp className="w-8 h-8 text-blue-400" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">Total Transactions</p>
                  <p className="text-2xl font-bold text-white">{formatNumber(stats.transactions.total)}</p>
                </div>
                <Award className="w-8 h-8 text-green-400" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">USDT Commissions</p>
                  <p className="text-2xl font-bold text-white">{formatCurrency(stats.transactions.total_usdt_commissions)}</p>
                </div>
                <DollarSign className="w-8 h-8 text-yellow-400" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">NFT Bonuses</p>
                  <p className="text-2xl font-bold text-white">{formatNumber(stats.transactions.total_nft_commissions)}</p>
                </div>
                <Star className="w-8 h-8 text-purple-400" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Commission Plans List */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {plans.map((plan) => (
          <Card key={plan.id} className="bg-gray-800 border-gray-700">
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle className="text-white flex items-center gap-2">
                    {plan.plan_name}
                    {plan.is_default && (
                      <Badge className="bg-gold-gradient text-black">
                        <Star className="w-3 h-3 mr-1" />
                        Default
                      </Badge>
                    )}
                    {plan.is_active ? (
                      <Badge className="bg-green-900 text-green-300">Active</Badge>
                    ) : (
                      <Badge className="bg-red-900 text-red-300">Inactive</Badge>
                    )}
                  </CardTitle>
                  <p className="text-gray-400 text-sm mt-1">{plan.description}</p>
                </div>
                <div className="flex gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => openEditForm(plan)}
                    className="text-blue-400 hover:text-blue-300"
                    title="Edit Plan"
                  >
                    <Edit className="w-4 h-4" />
                  </Button>
                  {!plan.is_default && (
                    <>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setDefaultPlan(plan.id)}
                        className="text-yellow-400 hover:text-yellow-300"
                        title="Set as Default"
                      >
                        <Star className="w-4 h-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => deletePlan(plan)}
                        className="text-red-400 hover:text-red-300"
                        title="Delete Plan"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </>
                  )}
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {/* Commission Structure */}
                <div>
                  <h4 className="text-white font-semibold mb-2">Commission Structure</h4>
                  <div className="grid grid-cols-3 gap-4 text-sm">
                    <div className="bg-gray-700/50 rounded-lg p-3">
                      <p className="text-gray-400">Level 1</p>
                      <p className="text-white font-semibold">{plan.level_1_usdt_percent}% USDT</p>
                      <p className="text-white font-semibold">{plan.level_1_nft_percent}% NFT</p>
                    </div>
                    <div className="bg-gray-700/50 rounded-lg p-3">
                      <p className="text-gray-400">Level 2</p>
                      <p className="text-white font-semibold">{plan.level_2_usdt_percent}% USDT</p>
                      <p className="text-white font-semibold">{plan.level_2_nft_percent}% NFT</p>
                    </div>
                    <div className="bg-gray-700/50 rounded-lg p-3">
                      <p className="text-gray-400">Level 3</p>
                      <p className="text-white font-semibold">{plan.level_3_usdt_percent}% USDT</p>
                      <p className="text-white font-semibold">{plan.level_3_nft_percent}% NFT</p>
                    </div>
                  </div>
                </div>

                {/* Usage Statistics */}
                <div>
                  <h4 className="text-white font-semibold mb-2">Usage Statistics</h4>
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p className="text-gray-400">Total Transactions</p>
                      <p className="text-white font-semibold">{formatNumber(plan.usage_stats.total_transactions)}</p>
                    </div>
                    <div>
                      <p className="text-gray-400">Pending</p>
                      <p className="text-yellow-400 font-semibold">{formatNumber(plan.usage_stats.pending_transactions)}</p>
                    </div>
                    <div>
                      <p className="text-gray-400">USDT Paid</p>
                      <p className="text-green-400 font-semibold">{formatCurrency(plan.usage_stats.total_usdt_paid)}</p>
                    </div>
                    <div>
                      <p className="text-gray-400">NFT Paid</p>
                      <p className="text-purple-400 font-semibold">{formatNumber(plan.usage_stats.total_nft_paid)}</p>
                    </div>
                  </div>
                </div>

                {/* NFT Pack Info */}
                <div className="bg-gray-700/30 rounded-lg p-3">
                  <div className="flex justify-between items-center text-sm">
                    <span className="text-gray-400">NFT Pack Price:</span>
                    <span className="text-white font-semibold">{formatCurrency(plan.nft_pack_price)}</span>
                  </div>
                  <div className="flex justify-between items-center text-sm mt-1">
                    <span className="text-gray-400">Remaining Supply:</span>
                    <span className="text-white font-semibold">
                      {formatNumber(plan.nft_remaining_supply)} / {formatNumber(plan.nft_total_supply)}
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {plans.length === 0 && (
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-8 text-center">
            <TrendingUp className="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-white mb-2">No Commission Plans</h3>
            <p className="text-gray-400 mb-4">Create your first commission plan to start managing referral rewards.</p>
            <Button
              onClick={() => setShowCreateForm(true)}
              className="bg-gold-gradient text-black hover:opacity-90"
            >
              <Plus className="w-4 h-4 mr-2" />
              Create First Plan
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Create/Edit Form Modal */}
      {showCreateForm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between mb-6">
              <h2 className="text-2xl font-bold text-white">
                {editingPlan ? 'Edit Commission Plan' : 'Create Commission Plan'}
              </h2>
              <Button
                variant="ghost"
                onClick={resetForm}
                className="text-gray-400 hover:text-white"
              >
                <X className="w-5 h-5" />
              </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Basic Information */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="plan_name" className="text-white">Plan Name *</Label>
                  <Input
                    id="plan_name"
                    value={formData.plan_name}
                    onChange={(e) => setFormData({...formData, plan_name: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    required
                  />
                </div>
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_active"
                    checked={formData.is_active}
                    onCheckedChange={(checked) => setFormData({...formData, is_active: checked})}
                  />
                  <Label htmlFor="is_active" className="text-white">Active Plan</Label>
                </div>
              </div>

              <div>
                <Label htmlFor="description" className="text-white">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData({...formData, description: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                  rows={3}
                />
              </div>

              {/* Commission Structure */}
              <div>
                <h3 className="text-xl font-semibold text-white mb-4">Commission Structure</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  {/* Level 1 */}
                  <div className="bg-gray-700/50 rounded-lg p-4">
                    <h4 className="text-white font-semibold mb-3">Level 1 Commission</h4>
                    <div className="space-y-3">
                      <div>
                        <Label className="text-gray-300">USDT Percentage</Label>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.level_1_usdt_percent}
                          onChange={(e) => setFormData({...formData, level_1_usdt_percent: parseFloat(e.target.value) || 0})}
                          className="bg-gray-600 border-gray-500 text-white"
                        />
                      </div>
                      <div>
                        <Label className="text-gray-300">NFT Percentage</Label>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.level_1_nft_percent}
                          onChange={(e) => setFormData({...formData, level_1_nft_percent: parseFloat(e.target.value) || 0})}
                          className="bg-gray-600 border-gray-500 text-white"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Level 2 */}
                  <div className="bg-gray-700/50 rounded-lg p-4">
                    <h4 className="text-white font-semibold mb-3">Level 2 Commission</h4>
                    <div className="space-y-3">
                      <div>
                        <Label className="text-gray-300">USDT Percentage</Label>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.level_2_usdt_percent}
                          onChange={(e) => setFormData({...formData, level_2_usdt_percent: parseFloat(e.target.value) || 0})}
                          className="bg-gray-600 border-gray-500 text-white"
                        />
                      </div>
                      <div>
                        <Label className="text-gray-300">NFT Percentage</Label>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.level_2_nft_percent}
                          onChange={(e) => setFormData({...formData, level_2_nft_percent: parseFloat(e.target.value) || 0})}
                          className="bg-gray-600 border-gray-500 text-white"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Level 3 */}
                  <div className="bg-gray-700/50 rounded-lg p-4">
                    <h4 className="text-white font-semibold mb-3">Level 3 Commission</h4>
                    <div className="space-y-3">
                      <div>
                        <Label className="text-gray-300">USDT Percentage</Label>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.level_3_usdt_percent}
                          onChange={(e) => setFormData({...formData, level_3_usdt_percent: parseFloat(e.target.value) || 0})}
                          className="bg-gray-600 border-gray-500 text-white"
                        />
                      </div>
                      <div>
                        <Label className="text-gray-300">NFT Percentage</Label>
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          value={formData.level_3_nft_percent}
                          onChange={(e) => setFormData({...formData, level_3_nft_percent: parseFloat(e.target.value) || 0})}
                          className="bg-gray-600 border-gray-500 text-white"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* NFT Configuration */}
              <div>
                <h3 className="text-xl font-semibold text-white mb-4">NFT Configuration</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label className="text-white">NFT Pack Price (USD)</Label>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={formData.nft_pack_price}
                      onChange={(e) => setFormData({...formData, nft_pack_price: parseFloat(e.target.value) || 0})}
                      className="bg-gray-700 border-gray-600 text-white"
                    />
                  </div>
                  <div>
                    <Label className="text-white">Total NFT Supply</Label>
                    <Input
                      type="number"
                      min="0"
                      value={formData.nft_total_supply}
                      onChange={(e) => setFormData({...formData, nft_total_supply: parseInt(e.target.value) || 0})}
                      className="bg-gray-700 border-gray-600 text-white"
                    />
                  </div>
                </div>
              </div>

              {/* Additional Settings */}
              <div>
                <h3 className="text-xl font-semibold text-white mb-4">Additional Settings</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <Label className="text-white">Max Levels</Label>
                    <Input
                      type="number"
                      min="1"
                      max="10"
                      value={formData.max_levels}
                      onChange={(e) => setFormData({...formData, max_levels: parseInt(e.target.value) || 3})}
                      className="bg-gray-700 border-gray-600 text-white"
                    />
                  </div>
                  <div>
                    <Label className="text-white">Minimum Investment (USD)</Label>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={formData.minimum_investment}
                      onChange={(e) => setFormData({...formData, minimum_investment: parseFloat(e.target.value) || 0})}
                      className="bg-gray-700 border-gray-600 text-white"
                    />
                  </div>
                  <div>
                    <Label className="text-white">Commission Cap (USD, optional)</Label>
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={formData.commission_cap}
                      onChange={(e) => setFormData({...formData, commission_cap: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="No cap"
                    />
                  </div>
                </div>
              </div>

              {/* Form Actions */}
              <div className="flex justify-end gap-4 pt-4 border-t border-gray-700">
                <Button
                  type="button"
                  variant="outline"
                  onClick={resetForm}
                  className="border-gray-600 text-gray-300 hover:bg-gray-700"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  disabled={isSubmitting}
                  className="bg-gold-gradient text-black hover:opacity-90"
                >
                  {isSubmitting ? (
                    <>
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-black mr-2"></div>
                      Saving...
                    </>
                  ) : (
                    <>
                      <Save className="w-4 h-4 mr-2" />
                      {editingPlan ? 'Update Plan' : 'Create Plan'}
                    </>
                  )}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default CommissionPlansManager;
