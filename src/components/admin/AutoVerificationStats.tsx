import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import ApiConfig from "@/config/api";
import {
  CheckCircle,
  Clock,
  AlertTriangle,
  TrendingUp,
  RefreshCw,
  Zap,
  Shield,
  Target
} from "lucide-react";

interface VerificationStats {
  total_payments: number;
  auto_approved: number;
  manual_review: number;
  auto_approval_rate: number;
  avg_confidence: number;
  recent_payments: Array<{
    payment_id: string;
    amount_usd: number;
    auto_approved: boolean;
    confidence: number;
    created_at: string;
    reason: string;
  }>;
}

const AutoVerificationStats: React.FC = () => {
  const { toast } = useToast();
  const [stats, setStats] = useState<VerificationStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${ApiConfig.baseUrl}/admin/auto-verification-stats.php`);
      const data = await response.json();
      
      if (data.success) {
        setStats(data.data);
      } else {
        throw new Error(data.error || 'Failed to fetch stats');
      }
    } catch (error) {
      console.error('Failed to fetch auto-verification stats:', error);
      toast({
        title: "Error",
        description: "Failed to load verification statistics",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-white">Loading verification statistics...</div>
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="text-center p-8">
        <div className="text-gray-400">No verification data available</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white">Auto-Verification System</h2>
        <Button 
          onClick={fetchStats} 
          variant="outline" 
          className="text-white border-gray-600"
        >
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="bg-gray-800/50 border-gray-700">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Total Payments</p>
                <p className="text-2xl font-bold text-white">{stats.total_payments}</p>
              </div>
              <Target className="h-8 w-8 text-blue-400" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800/50 border-gray-700">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Auto-Approved</p>
                <p className="text-2xl font-bold text-green-400">{stats.auto_approved}</p>
              </div>
              <Zap className="h-8 w-8 text-green-400" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800/50 border-gray-700">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Manual Review</p>
                <p className="text-2xl font-bold text-yellow-400">{stats.manual_review}</p>
              </div>
              <Clock className="h-8 w-8 text-yellow-400" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800/50 border-gray-700">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Success Rate</p>
                <p className="text-2xl font-bold text-blue-400">{stats.auto_approval_rate}%</p>
              </div>
              <TrendingUp className="h-8 w-8 text-blue-400" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* System Status */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Shield className="h-5 w-5" />
            Auto-Verification System Status
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 className="text-white font-medium mb-3">How It Works</h4>
              <div className="space-y-2 text-sm text-gray-300">
                <div className="flex items-center gap-2">
                  <CheckCircle className="h-4 w-4 text-green-400" />
                  <span>Validates wallet address formats</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="h-4 w-4 text-green-400" />
                  <span>Checks transaction hash format</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="h-4 w-4 text-green-400" />
                  <span>Verifies amount ranges</span>
                </div>
                <div className="flex items-center gap-2">
                  <AlertTriangle className="h-4 w-4 text-yellow-400" />
                  <span>Falls back to manual review if uncertain</span>
                </div>
              </div>
            </div>
            
            <div>
              <h4 className="text-white font-medium mb-3">Auto-Approval Criteria</h4>
              <div className="space-y-2 text-sm text-gray-300">
                <div>• Transaction hash provided (30 points)</div>
                <div>• Sender wallet provided (20 points)</div>
                <div>• Valid wallet format (25 points)</div>
                <div>• Amount ≤ $50,000 (25 points)</div>
                <div className="pt-2 border-t border-gray-600">
                  <strong className="text-white">Auto-approve at ≥80 points</strong>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Recent Payments */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Recent Payment Verifications</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {stats.recent_payments.length > 0 ? (
              stats.recent_payments.map((payment) => (
                <div 
                  key={payment.payment_id}
                  className="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg"
                >
                  <div className="flex items-center gap-3">
                    <div className={`p-2 rounded-full ${
                      payment.auto_approved ? 'bg-green-500/20' : 'bg-yellow-500/20'
                    }`}>
                      {payment.auto_approved ? (
                        <Zap className="h-4 w-4 text-green-400" />
                      ) : (
                        <Clock className="h-4 w-4 text-yellow-400" />
                      )}
                    </div>
                    <div>
                      <div className="text-white font-medium">
                        ${payment.amount_usd.toLocaleString()}
                      </div>
                      <div className="text-gray-400 text-sm">
                        {payment.payment_id}
                      </div>
                    </div>
                  </div>
                  
                  <div className="text-right">
                    <Badge 
                      variant={payment.auto_approved ? "default" : "secondary"}
                      className={payment.auto_approved ? "bg-green-600" : "bg-yellow-600"}
                    >
                      {payment.auto_approved ? 'Auto-Approved' : 'Manual Review'}
                    </Badge>
                    <div className="text-gray-400 text-sm mt-1">
                      {payment.confidence}% confidence
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div className="text-center text-gray-400 py-8">
                No recent payments to display
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AutoVerificationStats;
