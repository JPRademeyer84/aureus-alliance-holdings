import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Shield } from 'lucide-react';
import ApiConfig from '@/config/api';

const AdminAuthTestButton: React.FC = () => {
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

  const testAdminAuth = async () => {
    setIsLoading(true);
    setTestResult('🔄 Testing admin auth API...');

    try {
      console.log('🧪 Testing admin auth API...');
      console.log('🧪 API endpoint:', ApiConfig.endpoints.admin.auth);
      
      // Test 1: OPTIONS preflight request
      console.log('🧪 Step 1: Testing OPTIONS preflight...');
      const optionsResponse = await fetch(ApiConfig.endpoints.admin.auth, {
        method: 'OPTIONS',
        headers: {
          'Content-Type': 'application/json',
          'Origin': window.location.origin
        }
      });
      
      console.log('🧪 OPTIONS response status:', optionsResponse.status);
      console.log('🧪 OPTIONS response headers:', Object.fromEntries(optionsResponse.headers.entries()));
      
      if (!optionsResponse.ok) {
        setTestResult(`❌ OPTIONS preflight failed: ${optionsResponse.status} ${optionsResponse.statusText}`);
        return;
      }

      // Test 2: Actual POST request
      console.log('🧪 Step 2: Testing POST request...');
      const response = await fetch(ApiConfig.endpoints.admin.auth, {
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

      console.log('🧪 POST response status:', response.status);
      console.log('🧪 POST response headers:', Object.fromEntries(response.headers.entries()));

      if (response.ok) {
        const data = await response.json();
        console.log('🧪 POST response data:', data);
        
        if (data.success) {
          setTestResult(`✅ Admin auth working! Admin: ${data.data.admin.username}, Role: ${data.data.admin.role}`);
        } else {
          setTestResult(`❌ Auth failed: ${data.error || data.message}`);
        }
      } else {
        const errorText = await response.text();
        setTestResult(`❌ HTTP Error: ${response.status} ${response.statusText} - ${errorText}`);
      }
    } catch (error) {
      console.error('🧪 Admin auth test error:', error);
      setTestResult(`❌ Network Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed top-28 left-4 z-50 bg-red-600 hover:bg-red-700 text-white p-2 rounded shadow-lg">
      <Button
        onClick={testAdminAuth}
        disabled={isLoading}
        variant="outline"
        size="sm"
        className="text-white border-white hover:bg-red-800 mb-2"
        title="Test admin auth API"
      >
        <Shield className="w-4 h-4 mr-2" />
        {isLoading ? 'Testing...' : 'Test Admin Auth'}
      </Button>
      {testResult && (
        <div className="text-xs mt-1 p-1 bg-black/20 rounded max-w-xs">
          {testResult}
        </div>
      )}
    </div>
  );
};

export default AdminAuthTestButton;
