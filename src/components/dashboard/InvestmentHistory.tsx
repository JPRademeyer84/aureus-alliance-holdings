import React, { useState, useEffect } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ST as T } from '@/components/SimpleTranslator';
import {
  Package,
  ExternalLink,
  RefreshCw,
  TrendingUp,
  Calendar,
  DollarSign,
  Hash
} from "lucide-react";
import {
  ParticipationRecord,
  formatParticipationStatus,
  getBlockExplorerUrl,
  formatCurrency,
  formatDate
} from "@/pages/investment/utils/investmentHistory";

interface ParticipationHistoryProps {
  // No props needed - will get user participation from context/API
}

// Legacy interface name for backward compatibility
interface InvestmentHistoryProps extends ParticipationHistoryProps {}

const InvestmentHistory: React.FC<InvestmentHistoryProps> = () => {
  const [investments, setInvestments] = useState<ParticipationRecord[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadParticipationHistory = async () => {
    setIsLoading(true);
    setError(null);

    try {
      // NUCLEAR OPTION - ROOT LEVEL STANDALONE FILE
      const response = await fetch(`http://localhost/Aureus%201%20-%20Complex/get-my-investments.php?t=${Date.now()}`, {
        method: 'GET'
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('API Response Error:', response.status, errorText);
        throw new Error(`Failed to fetch participation history: ${response.status}`);
      }

      const data = await response.json();
      console.log('Participation history response:', data);

      if (data.success) {
        // Map API response to ParticipationRecord interface
        const mappedParticipations = (data.investments || []).map((investment: any) => ({
          id: investment.id,
          packageName: investment.packageName || 'Unknown Package',
          amount: investment.amount || 0,
          shares: investment.shares || 0,
          reward: investment.roi || 0, // Use ROI from backend
          txHash: investment.txHash || '',
          chainId: investment.chainId || 'polygon',
          walletAddress: investment.walletAddress || '',
          status: investment.status || 'pending',
          createdAt: investment.createdAt,
          updatedAt: investment.updatedAt || investment.createdAt
        }));
        setInvestments(mappedParticipations);
      } else {
        throw new Error(data.message || 'API returned error');
      }
    } catch (err: any) {
      setError(err.message || "Failed to load participation history");
      console.error("Failed to load participation history:", err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    loadParticipationHistory();
  }, []);

  const getTotalParticipation = () => {
    return investments
      .filter(inv => inv.status === 'completed')
      .reduce((total, inv) => total + inv.amount, 0);
  };

  const getTotalReward = () => {
    return investments
      .filter(inv => inv.status === 'completed')
      .reduce((total, inv) => total + inv.reward, 0);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-500/10 border-green-500/30 text-green-400';
      case 'pending':
        return 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400';
      case 'failed':
        return 'bg-red-500/10 border-red-500/30 text-red-400';
      default:
        return 'bg-gray-500/10 border-gray-500/30 text-gray-400';
    }
  };

  // Removed wallet requirement - now shows user's investment history from database

  return (
    <div className="space-y-6">
      {/* Summary Cards */}
      {investments.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card className="bg-[#23243a] border-gold/30">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-2">
                <DollarSign className="h-4 w-4 text-green-400" />
                <span className="text-sm text-gray-300">
                  <T k="total_participated" fallback="Total Participated" />
                </span>
              </div>
              <p className="text-xl font-bold text-white">
                {formatCurrency(getTotalParticipation())}
              </p>
            </CardContent>
          </Card>

          <Card className="bg-[#23243a] border-gold/30">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-2">
                <TrendingUp className="h-4 w-4 text-gold" />
                <span className="text-sm text-gray-300">
                  <T k="expected_reward" fallback="Expected Reward" />
                </span>
              </div>
              <p className="text-xl font-bold text-gold">
                {formatCurrency(getTotalReward())}
              </p>
            </CardContent>
          </Card>

          <Card className="bg-[#23243a] border-gold/30">
            <CardContent className="p-4">
              <div className="flex items-center gap-2 mb-2">
                <Package className="h-4 w-4 text-blue-400" />
                <span className="text-sm text-gray-300">
                  <T k="total_packages" fallback="Total Packages" />
                </span>
              </div>
              <p className="text-xl font-bold text-white">
                {investments.filter(inv => inv.status === 'completed').length}
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Investment History */}
      <Card className="bg-[#23243a] border-gold/30">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-white flex items-center gap-2">
              <Package className="h-5 w-5 text-gold" />
              <T k="participation_history" fallback="Participation History" />
            </CardTitle>
            <Button
              variant="ghost"
              size="sm"
              onClick={loadParticipationHistory}
              disabled={isLoading}
              className="text-gray-400 hover:text-white"
            >
              <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {error ? (
            <div className="text-center py-8">
              <div className="text-red-400 mb-2">
                <T k="error_loading_participations" fallback="⚠️ Error loading participations" />
              </div>
              <p className="text-red-300 text-sm mb-4">{error}</p>
              <Button
                variant="outline"
                size="sm"
                onClick={loadParticipationHistory}
                className="text-red-400 border-red-500/30 hover:bg-red-500/20"
              >
                <T k="try_again" fallback="Try Again" />
              </Button>
            </div>
          ) : isLoading ? (
            <div className="text-center py-8">
              <RefreshCw className="h-8 w-8 text-gold animate-spin mx-auto mb-4" />
              <p className="text-white/70">
                <T k="loading_participation_history" fallback="Loading participation history..." />
              </p>
            </div>
          ) : investments.length === 0 ? (
            <div className="text-center py-8">
              <Package className="h-12 w-12 text-gold/50 mx-auto mb-4" />
              <p className="text-white/70 mb-2">
                <T k="no_participations_yet" fallback="No participations yet" />
              </p>
              <p className="text-white/50 text-sm">
                <T k="participation_history_will_appear" fallback="Your participation history will appear here once you make your first purchase" />
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {investments.map((investment) => {
                const statusInfo = formatParticipationStatus(investment.status);
                
                return (
                  <Card key={investment.id} className="bg-charcoal/50 border-gray-600/30">
                    <CardContent className="p-4">
                      <div className="flex items-start justify-between mb-3">
                        <div>
                          <h3 className="text-white font-semibold text-lg">
                            {investment.packageName}
                          </h3>
                          <div className="flex items-center gap-2 mt-1">
                            <Badge className={getStatusColor(investment.status)}>
                              {statusInfo.text}
                            </Badge>
                            <span className="text-xs text-gray-400">
                              {investment.chainId?.toUpperCase() || 'POLYGON'}
                            </span>
                          </div>
                        </div>
                        <div className="text-right">
                          <p className="text-gold font-semibold text-lg">
                            {formatCurrency(investment.amount)}
                          </p>
                          <p className="text-xs text-gray-400">
                            {investment.shares?.toLocaleString() || '0'} <T k="shares" fallback="shares" />
                          </p>
                        </div>
                      </div>

                      <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <div className="flex items-center gap-1 text-gray-400 mb-1">
                            <TrendingUp className="h-3 w-3" />
                            <span><T k="expected_reward" fallback="Expected Reward" /></span>
                          </div>
                          <p className="text-green-400 font-medium">
                            {formatCurrency(investment.reward)}
                          </p>
                        </div>
                        <div>
                          <div className="flex items-center gap-1 text-gray-400 mb-1">
                            <Calendar className="h-3 w-3" />
                            <span><T k="date" fallback="Date" /></span>
                          </div>
                          <p className="text-white">
                            {formatDate(investment.createdAt)}
                          </p>
                        </div>
                      </div>

                      {investment.txHash && (
                        <div className="mt-3 pt-3 border-t border-gray-600/30">
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1 text-gray-400">
                              <Hash className="h-3 w-3" />
                              <span className="text-xs"><T k="transaction" fallback="Transaction" /></span>
                            </div>
                            <div className="flex items-center gap-2">
                              <code className="text-xs bg-black/30 px-2 py-1 rounded text-gray-300">
                                {investment.txHash.slice(0, 8)}...{investment.txHash.slice(-6)}
                              </code>
                              <Button
                                variant="ghost"
                                size="sm"
                                className="h-6 w-6 p-0 text-gray-400 hover:text-white"
                                onClick={() => {
                                  const explorerUrl = getBlockExplorerUrl(investment.txHash, investment.chainId || 'polygon');
                                  console.log(`Opening transaction link:`, {
                                    txHash: investment.txHash,
                                    chainId: investment.chainId || 'polygon',
                                    explorerUrl
                                  });
                                  window.open(explorerUrl, '_blank');
                                }}
                              >
                                <ExternalLink className="h-3 w-3" />
                              </Button>
                            </div>
                          </div>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                );
              })}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default InvestmentHistory;
