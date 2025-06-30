import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { FileCheck } from 'lucide-react';
import ApiConfig from '@/config/api';

const KycTestButton: React.FC = () => {
  const [testResult, setTestResult] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);
  const [isEnabled, setIsEnabled] = useState(false);

  useEffect(() => {
    // Check if debug testing is enabled
    const checkDebugStatus = async () => {
      try {
        const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=active', {
          credentials: 'include'
        });

        if (!response.ok) {
          setIsEnabled(false);
          return;
        }

        const data = await response.json();

        if (data.success) {
          // Check if api_testing feature is enabled
          const apiTestingEnabled = data.data.features.some(
            (feature: any) => feature.feature_key === 'api_testing'
          );
          setIsEnabled(apiTestingEnabled);
        }
      } catch (error) {
        console.error('Error checking debug status:', error);
        setIsEnabled(false);
      }
    };

    checkDebugStatus();
  }, []);

  // Don't render if not enabled
  if (!isEnabled) {
    return null;
  }

  const testKycManagement = async () => {
    setIsLoading(true);
    setTestResult('🔄 Testing KYC management...');

    try {
      console.log('🧪 Step 1: Testing admin login...');
      
      // First, try to login as admin
      const loginResponse = await fetch(ApiConfig.endpoints.admin.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'login',
          username: 'admin',
          password: 'Underdog8406155100085@123!@#'
        })
      });

      console.log('🧪 Login response status:', loginResponse.status);

      if (!loginResponse.ok) {
        setTestResult(`❌ Admin login failed: ${loginResponse.status} ${loginResponse.statusText}`);
        return;
      }

      const loginData = await loginResponse.json();
      console.log('🧪 Login data:', loginData);

      if (!loginData.success) {
        setTestResult(`❌ Admin login failed: ${loginData.error || loginData.message}`);
        return;
      }

      console.log('🧪 Step 2: Testing KYC management API...');
      
      // Now try to access KYC management
      const kycResponse = await fetch(`${ApiConfig.endpoints.admin.kycManagement}?action=get`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include'
      });

      console.log('🧪 KYC response status:', kycResponse.status);
      console.log('🧪 KYC response headers:', Object.fromEntries(kycResponse.headers.entries()));

      if (kycResponse.ok) {
        const kycData = await kycResponse.json();
        console.log('🧪 KYC data:', kycData);
        
        if (kycData.success) {
          const docCount = kycData.data.documents.length;
          const pendingCount = kycData.data.documents.filter((doc: any) => doc.status === 'pending').length;
          setTestResult(`✅ KYC API working! Found ${docCount} documents (${pendingCount} pending)`);
        } else {
          setTestResult(`❌ KYC API error: ${kycData.message}`);
        }
      } else {
        const errorText = await kycResponse.text();
        setTestResult(`❌ KYC HTTP Error: ${kycResponse.status} ${kycResponse.statusText} - ${errorText}`);
      }
    } catch (error) {
      console.error('🧪 KYC test error:', error);
      setTestResult(`❌ Network Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed top-40 left-4 z-50 bg-green-600 hover:bg-green-700 text-white p-2 rounded shadow-lg">
      <Button
        onClick={testKycManagement}
        disabled={isLoading}
        variant="outline"
        size="sm"
        className="text-white border-white hover:bg-green-800 mb-2"
        title="Test KYC management API"
      >
        <FileCheck className="w-4 h-4 mr-2" />
        {isLoading ? 'Testing...' : 'Test KYC API'}
      </Button>
      {testResult && (
        <div className="text-xs mt-1 p-1 bg-black/20 rounded max-w-xs">
          {testResult}
        </div>
      )}
    </div>
  );
};

export default KycTestButton;
