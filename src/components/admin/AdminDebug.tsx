import React, { useState } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';

const AdminDebug: React.FC = () => {
  const { admin } = useAdmin();
  const { toast } = useToast();
  const [testResult, setTestResult] = useState<string>('');

  const testConnectivity = async () => {
    try {
      setTestResult('🔄 Testing API connectivity...');

      const url = 'http://localhost/api/test-connectivity.php';
      console.log('Testing connectivity URL:', url);

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      console.log('Connectivity response:', response);
      console.log('Response status:', response.status);
      console.log('Response headers:', Object.fromEntries(response.headers.entries()));

      if (!response.ok) {
        const errorText = await response.text();
        console.log('Error text:', errorText);
        setTestResult(`❌ Connectivity HTTP ${response.status}: ${errorText}`);
        return;
      }

      const data = await response.json();
      console.log('Connectivity data:', data);

      if (data.success) {
        setTestResult(`✅ Connectivity Success! Server: ${data.server_info.server_name}:${data.server_info.server_port}`);
      } else {
        setTestResult(`❌ Connectivity Error: ${data.message || 'Unknown error'}`);
      }
    } catch (error) {
      console.error('Connectivity test error:', error);
      setTestResult(`❌ Connectivity Network Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  };

  const testAPI = async () => {
    if (!admin?.id) {
      setTestResult('❌ No admin ID available');
      return;
    }

    try {
      setTestResult('🔄 Testing Users API...');

      const url = `${ApiConfig.endpoints.admin.manageUsers}?admin_id=${admin.id}`;
      console.log('Testing URL:', url);

      const response = await fetch(url);
      console.log('Response:', response);

      if (!response.ok) {
        const errorText = await response.text();
        setTestResult(`❌ HTTP ${response.status}: ${errorText}`);
        return;
      }

      const data = await response.json();
      console.log('Data:', data);

      if (data.success) {
        setTestResult(`✅ Success! Found ${data.data.users.length} users`);
      } else {
        setTestResult(`❌ API Error: ${data.message || data.error}`);
      }
    } catch (error) {
      console.error('Test error:', error);
      setTestResult(`❌ Network Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  };

  const testAuth = async () => {
    try {
      setTestResult('🔄 Testing auth...');
      
      const response = await fetch(ApiConfig.endpoints.admin.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'login',
          username: 'admin',
          password: 'Underdog8406155100085@123!@#'
        })
      });

      const data = await response.json();
      
      if (data.success) {
        setTestResult(`✅ Auth Success! Admin ID: ${data.data.admin.id}`);
      } else {
        setTestResult(`❌ Auth Failed: ${data.error || data.message}`);
      }
    } catch (error) {
      setTestResult(`❌ Auth Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  };

  return (
    <div className="space-y-6">
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Admin Debug Info</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="text-white">
            <h3 className="font-semibold mb-2">Current Admin State:</h3>
            <pre className="bg-gray-900 p-3 rounded text-sm overflow-auto">
              {JSON.stringify(admin, null, 2)}
            </pre>
          </div>
          
          <div className="text-white">
            <h3 className="font-semibold mb-2">API Configuration:</h3>
            <pre className="bg-gray-900 p-3 rounded text-sm overflow-auto">
              {JSON.stringify({
                baseUrl: ApiConfig.baseUrl,
                manageUsers: ApiConfig.endpoints.admin.manageUsers,
                auth: ApiConfig.endpoints.admin.auth
              }, null, 2)}
            </pre>
          </div>
          
          <div className="flex gap-2 flex-wrap">
            <Button onClick={testConnectivity} className="bg-purple-600 hover:bg-purple-700">
              Test Connectivity
            </Button>
            <Button onClick={testAuth} className="bg-blue-600 hover:bg-blue-700">
              Test Auth
            </Button>
            <Button onClick={testAPI} className="bg-green-600 hover:bg-green-700">
              Test Users API
            </Button>
          </div>
          
          {testResult && (
            <div className="text-white">
              <h3 className="font-semibold mb-2">Test Result:</h3>
              <div className="bg-gray-900 p-3 rounded text-sm">
                {testResult}
              </div>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminDebug;
