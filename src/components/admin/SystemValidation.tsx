import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  PlayCircle,
  CheckCircle,
  XCircle,
  AlertTriangle,
  RefreshCw,
  Users,
  DollarSign,
  Shield,
  Clock,
  TrendingUp,
  Database
} from 'lucide-react';

interface WorkflowTestResults {
  workflow_test: {
    step_1_create_users: any;
    step_2_referral_tracking: any;
    step_3_investment_creation: any;
    step_4_commission_activation: any;
    step_5_withdrawal_request: any;
    step_6_security_verification: any;
  };
  workflow_summary: {
    overall_status: string;
    steps_completed: number;
    total_steps: number;
    test_completed_at: string;
    referrer_username: string;
    referred_username: string;
  };
}

const SystemValidation: React.FC = () => {
  const { toast } = useToast();
  
  const [testResults, setTestResults] = useState<WorkflowTestResults | null>(null);
  const [isRunning, setIsRunning] = useState(false);
  const [lastTestTime, setLastTestTime] = useState<string | null>(null);

  const runCompleteWorkflowTest = async () => {
    setIsRunning(true);
    try {
      const response = await fetch('/api/test/complete-workflow-test.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setTestResults(data.test_results);
        setLastTestTime(data.test_results.workflow_summary.test_completed_at);
        
        const overallStatus = data.test_results.workflow_summary.overall_status;
        if (overallStatus === 'COMPLETE_SUCCESS') {
          toast({
            title: "🎉 Complete Workflow Test: SUCCESS",
            description: `All ${data.test_results.workflow_summary.steps_completed} steps completed successfully!`,
          });
        } else {
          toast({
            title: "⚠️ Workflow Test: Partial Success",
            description: `${data.test_results.workflow_summary.steps_completed}/${data.test_results.workflow_summary.total_steps} steps completed`,
            variant: "destructive"
          });
        }
      } else {
        throw new Error(data.message || 'Workflow test failed');
      }
    } catch (error) {
      console.error('Workflow test failed:', error);
      toast({
        title: "❌ Workflow Test Failed",
        description: error instanceof Error ? error.message : "Failed to run complete workflow test",
        variant: "destructive"
      });
    } finally {
      setIsRunning(false);
    }
  };

  const getStepIcon = (status: string) => {
    switch (status) {
      case 'SUCCESS':
        return <CheckCircle className="h-5 w-5 text-green-400" />;
      case 'FAILED':
        return <XCircle className="h-5 w-5 text-red-400" />;
      default:
        return <AlertTriangle className="h-5 w-5 text-gray-400" />;
    }
  };

  const getStepColor = (status: string) => {
    switch (status) {
      case 'SUCCESS':
        return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'FAILED':
        return 'bg-red-500/20 text-red-400 border-red-500/30';
      default:
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  const workflowSteps = [
    {
      key: 'step_1_create_users',
      title: 'Create Test Users',
      description: 'Create referrer (JPRademeyer) and referred (TestUser) accounts',
      icon: <Users className="h-4 w-4" />
    },
    {
      key: 'step_2_referral_tracking',
      title: 'Referral Link Tracking',
      description: 'Simulate referral link visit and session storage',
      icon: <TrendingUp className="h-4 w-4" />
    },
    {
      key: 'step_3_investment_creation',
      title: 'Investment & Commission Creation',
      description: 'Create investment and generate commissions with security updates',
      icon: <DollarSign className="h-4 w-4" />
    },
    {
      key: 'step_4_commission_activation',
      title: 'Commission Activation',
      description: 'Activate pending commissions and make them available for withdrawal',
      icon: <PlayCircle className="h-4 w-4" />
    },
    {
      key: 'step_5_withdrawal_request',
      title: 'Withdrawal Request',
      description: 'Submit withdrawal request with business hours validation',
      icon: <Clock className="h-4 w-4" />
    },
    {
      key: 'step_6_security_verification',
      title: 'Security Integrity Check',
      description: 'Verify balance integrity and security system functionality',
      icon: <Shield className="h-4 w-4" />
    }
  ];

  if (isRunning) {
    return (
      <div className="flex items-center justify-center py-12">
        <RefreshCw className="h-8 w-8 animate-spin text-gold" />
        <span className="ml-3 text-white">Running complete end-to-end workflow test...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Test Control */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle className="text-white flex items-center gap-2">
              <Database className="h-5 w-5 text-gold" />
              Complete Referral System Validation
            </CardTitle>
            <Button 
              onClick={runCompleteWorkflowTest} 
              disabled={isRunning}
              className="bg-gold-gradient text-black hover:bg-gold-gradient/90"
            >
              {isRunning ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : <PlayCircle className="h-4 w-4 mr-2" />}
              Run Complete Test
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-gray-300 mb-4">
            This test validates the complete end-to-end referral workflow: from referral link visit 
            to commission creation, activation, and withdrawal processing with full security verification.
          </p>
          {lastTestTime && (
            <p className="text-sm text-gray-400">
              Last test completed: {new Date(lastTestTime).toLocaleString()}
            </p>
          )}
        </CardContent>
      </Card>

      {/* Test Results Summary */}
      {testResults && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              {testResults.workflow_summary.overall_status === 'COMPLETE_SUCCESS' ? 
                <CheckCircle className="h-5 w-5 text-green-400" /> : 
                <AlertTriangle className="h-5 w-5 text-orange-400" />
              }
              Workflow Test Summary
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div className="text-center">
                <div className="text-3xl font-bold text-white mb-2">
                  {testResults.workflow_summary.steps_completed}/{testResults.workflow_summary.total_steps}
                </div>
                <div className="text-sm text-gray-400">Steps Completed</div>
              </div>
              <div className="text-center">
                <Badge className={testResults.workflow_summary.overall_status === 'COMPLETE_SUCCESS' ? 
                  'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400'}>
                  {testResults.workflow_summary.overall_status}
                </Badge>
                <div className="text-sm text-gray-400 mt-2">Overall Status</div>
              </div>
              <div className="text-center">
                <div className="text-lg font-bold text-blue-400 mb-2">
                  {testResults.workflow_summary.referrer_username}
                </div>
                <div className="text-sm text-gray-400">Test Referrer</div>
              </div>
              <div className="text-center">
                <div className="text-lg font-bold text-purple-400 mb-2">
                  {testResults.workflow_summary.referred_username}
                </div>
                <div className="text-sm text-gray-400">Test Referred User</div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Detailed Step Results */}
      {testResults && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {workflowSteps.map((step, index) => {
            const stepResult = testResults.workflow_test[step.key as keyof typeof testResults.workflow_test];
            const status = stepResult?.status || 'UNKNOWN';
            
            return (
              <Card key={step.key} className={`bg-gray-800 border-2 ${getStepColor(status)}`}>
                <CardHeader>
                  <CardTitle className="text-white flex items-center gap-2 text-lg">
                    {getStepIcon(status)}
                    Step {index + 1}: {step.title}
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-gray-300 text-sm mb-4">{step.description}</p>
                  
                  <div className="space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-gray-400 text-sm">Status:</span>
                      <Badge className={getStepColor(status)} variant="outline">
                        {status}
                      </Badge>
                    </div>
                    
                    {stepResult?.error && (
                      <div className="bg-red-500/10 border border-red-500/20 rounded p-2">
                        <p className="text-red-400 text-sm">{stepResult.error}</p>
                      </div>
                    )}
                    
                    {status === 'SUCCESS' && stepResult && (
                      <div className="bg-green-500/10 border border-green-500/20 rounded p-2">
                        <div className="text-green-400 text-sm space-y-1">
                          {step.key === 'step_3_investment_creation' && (
                            <>
                              <div>Investment: ${stepResult.investment_amount}</div>
                              <div>USDT Commission: ${stepResult.level_1_usdt}</div>
                              <div>NFT Commission: {stepResult.level_1_nft} packs</div>
                            </>
                          )}
                          {step.key === 'step_4_commission_activation' && (
                            <>
                              <div>Available USDT: ${stepResult.available_usdt}</div>
                              <div>Available NFT: {stepResult.available_nft} packs</div>
                            </>
                          )}
                          {step.key === 'step_5_withdrawal_request' && (
                            <>
                              <div>Withdrawal: ${stepResult.withdrawal_amount}</div>
                              <div>Status: {stepResult.withdrawal_result?.status}</div>
                            </>
                          )}
                          {step.key === 'step_6_security_verification' && (
                            <>
                              <div>Integrity Valid: {stepResult.integrity_valid ? 'YES' : 'NO'}</div>
                              <div>Final USDT: ${stepResult.final_balance?.available_usdt_balance || 0}</div>
                            </>
                          )}
                        </div>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      {/* Instructions */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Test Instructions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-gray-300 space-y-2">
            <p><strong>This test validates:</strong></p>
            <ul className="list-disc list-inside space-y-1 ml-4">
              <li>Complete referral link → investment → commission workflow</li>
              <li>Security system integrity and balance verification</li>
              <li>Commission creation and activation process</li>
              <li>Withdrawal request and business hours validation</li>
              <li>End-to-end data consistency and audit trails</li>
            </ul>
            <p className="mt-4"><strong>Expected Result:</strong> All 6 steps should complete successfully with COMPLETE_SUCCESS status.</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default SystemValidation;
