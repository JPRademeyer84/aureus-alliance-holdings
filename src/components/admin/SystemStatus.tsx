import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Shield,
  Database,
  Clock,
  AlertTriangle,
  CheckCircle,
  XCircle,
  RefreshCw,
  Activity,
  TrendingUp,
  Users,
  DollarSign
} from 'lucide-react';

interface SystemTestResults {
  database_tables: Record<string, string>;
  security_system: Record<string, string>;
  business_hours: Record<string, any>;
  api_endpoints: Record<string, string>;
  commission_calculation: Record<string, any>;
  authentication: Record<string, string>;
  database_performance: Record<string, any>;
  frontend_integration: Record<string, string>;
  security_audit: Record<string, any>;
  system_health: {
    health_checks: Record<string, boolean>;
    health_score_percentage: number;
    overall_status: string;
    recommendations: string;
  };
}

const SystemStatus: React.FC = () => {
  const { toast } = useToast();
  
  const [testResults, setTestResults] = useState<SystemTestResults | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [lastTestTime, setLastTestTime] = useState<string | null>(null);

  useEffect(() => {
    runSystemTest();
  }, []);

  const runSystemTest = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('/api/test/referral-system-test.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setTestResults(data.test_results);
        setLastTestTime(data.test_completed_at);
        
        const healthScore = data.test_results.system_health.health_score_percentage;
        if (healthScore >= 90) {
          toast({
            title: "System Status: Excellent",
            description: `All systems operational (${healthScore}% health score)`,
          });
        } else if (healthScore >= 70) {
          toast({
            title: "System Status: Good",
            description: `Most systems operational (${healthScore}% health score)`,
          });
        } else {
          toast({
            title: "System Status: Issues Detected",
            description: `Some systems need attention (${healthScore}% health score)`,
            variant: "destructive"
          });
        }
      } else {
        throw new Error(data.message || 'System test failed');
      }
    } catch (error) {
      console.error('System test failed:', error);
      toast({
        title: "System Test Failed",
        description: error instanceof Error ? error.message : "Failed to run system test",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const getStatusIcon = (status: string | boolean) => {
    if (typeof status === 'boolean') {
      return status ? <CheckCircle className="h-4 w-4 text-green-400" /> : <XCircle className="h-4 w-4 text-red-400" />;
    }
    
    switch (status.toLowerCase()) {
      case 'exists':
      case 'working':
      case 'connected':
      case 'active':
      case 'valid':
      case 'excellent':
      case 'success':
        return <CheckCircle className="h-4 w-4 text-green-400" />;
      case 'missing':
      case 'failed':
      case 'inactive':
      case 'invalid':
      case 'error':
        return <XCircle className="h-4 w-4 text-red-400" />;
      case 'good':
      case 'within_hours':
        return <Activity className="h-4 w-4 text-blue-400" />;
      case 'outside_hours':
        return <Clock className="h-4 w-4 text-yellow-400" />;
      default:
        return <AlertTriangle className="h-4 w-4 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string | boolean) => {
    if (typeof status === 'boolean') {
      return status ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400';
    }
    
    switch (status.toLowerCase()) {
      case 'exists':
      case 'working':
      case 'connected':
      case 'active':
      case 'valid':
      case 'excellent':
      case 'success':
        return 'bg-green-500/20 text-green-400';
      case 'missing':
      case 'failed':
      case 'inactive':
      case 'invalid':
      case 'error':
        return 'bg-red-500/20 text-red-400';
      case 'good':
      case 'within_hours':
        return 'bg-blue-500/20 text-blue-400';
      case 'outside_hours':
        return 'bg-yellow-500/20 text-yellow-400';
      default:
        return 'bg-gray-500/20 text-gray-400';
    }
  };

  if (isLoading && !testResults) {
    return (
      <div className="flex items-center justify-center py-12">
        <RefreshCw className="h-8 w-8 animate-spin text-gold" />
        <span className="ml-3 text-white">Running comprehensive system test...</span>
      </div>
    );
  }

  if (!testResults) {
    return (
      <div className="text-center py-12">
        <AlertTriangle className="h-12 w-12 text-red-400 mx-auto mb-4" />
        <p className="text-white mb-4">Failed to load system status</p>
        <Button onClick={runSystemTest} className="bg-gold-gradient text-black">
          <RefreshCw className="h-4 w-4 mr-2" />
          Retry Test
        </Button>
      </div>
    );
  }

  const healthScore = testResults.system_health.health_score_percentage;

  return (
    <div className="space-y-6">
      {/* System Health Overview */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle className="text-white flex items-center gap-2">
              <Shield className="h-5 w-5 text-gold" />
              System Health Overview
            </CardTitle>
            <div className="flex gap-2">
              <Badge className={getStatusColor(testResults.system_health.overall_status)}>
                {testResults.system_health.overall_status}
              </Badge>
              <Button 
                onClick={runSystemTest} 
                disabled={isLoading}
                variant="outline" 
                className="border-gray-600"
              >
                {isLoading ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : <RefreshCw className="h-4 w-4 mr-2" />}
                Refresh
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div className="text-center">
              <div className="text-3xl font-bold text-white mb-2">{healthScore}%</div>
              <div className="text-sm text-gray-400">Health Score</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-bold text-green-400 mb-2">
                {Object.values(testResults.system_health.health_checks).filter(Boolean).length}
              </div>
              <div className="text-sm text-gray-400">Systems Online</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-bold text-red-400 mb-2">
                {Object.values(testResults.system_health.health_checks).filter(v => !v).length}
              </div>
              <div className="text-sm text-gray-400">Issues Detected</div>
            </div>
            <div className="text-center">
              <div className="text-3xl font-bold text-blue-400 mb-2">
                {testResults.database_performance.commission_records_count || 0}
              </div>
              <div className="text-sm text-gray-400">Commission Records</div>
            </div>
          </div>

          {lastTestTime && (
            <p className="text-sm text-gray-400 text-center">
              Last tested: {new Date(lastTestTime).toLocaleString()}
            </p>
          )}
        </CardContent>
      </Card>

      {/* Critical System Checks */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Database Tables */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Database className="h-5 w-5 text-blue-400" />
              Database Tables
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {Object.entries(testResults.database_tables).map(([table, status]) => (
                <div key={table} className="flex items-center justify-between">
                  <span className="text-gray-300 text-sm">{table}</span>
                  <div className="flex items-center gap-2">
                    {getStatusIcon(status)}
                    <Badge className={getStatusColor(status)} variant="outline">
                      {status}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Security System */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Shield className="h-5 w-5 text-green-400" />
              Security System
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {Object.entries(testResults.security_system).map(([check, status]) => (
                <div key={check} className="flex items-center justify-between">
                  <span className="text-gray-300 text-sm">{check.replace('_', ' ')}</span>
                  <div className="flex items-center gap-2">
                    {getStatusIcon(status)}
                    <Badge className={getStatusColor(status)} variant="outline">
                      {status}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Business Hours */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Clock className="h-5 w-5 text-yellow-400" />
              Business Hours
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-gray-300 text-sm">Current Status</span>
                <div className="flex items-center gap-2">
                  {getStatusIcon(testResults.business_hours.current_status)}
                  <Badge className={getStatusColor(testResults.business_hours.current_status)} variant="outline">
                    {testResults.business_hours.current_status}
                  </Badge>
                </div>
              </div>
              {testResults.business_hours.next_business_day && (
                <div className="text-sm text-gray-400">
                  Next business day: {new Date(testResults.business_hours.next_business_day).toLocaleString()}
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* API Endpoints */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Activity className="h-5 w-5 text-purple-400" />
              API Endpoints
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {Object.entries(testResults.api_endpoints).slice(0, 5).map(([endpoint, status]) => (
                <div key={endpoint} className="flex items-center justify-between">
                  <span className="text-gray-300 text-sm truncate">{endpoint.split('/').pop()}</span>
                  <div className="flex items-center gap-2">
                    {getStatusIcon(status)}
                    <Badge className={getStatusColor(status)} variant="outline">
                      {status}
                    </Badge>
                  </div>
                </div>
              ))}
              <div className="text-sm text-gray-400 text-center pt-2">
                {Object.values(testResults.api_endpoints).filter(s => s === 'EXISTS').length} of {Object.keys(testResults.api_endpoints).length} endpoints available
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Performance Metrics */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-gold" />
            Performance Metrics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-white mb-2">
                {testResults.database_performance.query_time_ms}ms
              </div>
              <div className="text-sm text-gray-400">Database Query Time</div>
              <Badge className={getStatusColor(testResults.database_performance.performance_rating)} variant="outline">
                {testResults.database_performance.performance_rating}
              </Badge>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-white mb-2">
                {testResults.security_audit.recent_logs_count || 0}
              </div>
              <div className="text-sm text-gray-400">Security Events (1h)</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-white mb-2">
                {Object.values(testResults.commission_calculation).filter((calc: any) => calc.calculation_valid).length}
              </div>
              <div className="text-sm text-gray-400">Commission Levels Working</div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Recommendations */}
      {testResults.system_health.recommendations && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-orange-400" />
              Recommendations
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-gray-300">{testResults.system_health.recommendations}</p>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default SystemStatus;
