import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useAdmin } from '@/contexts/AdminContext';
import ApiConfig from '@/config/api';

const ApiTest: React.FC = () => {
  const [testResult, setTestResult] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(false);
  const { admin } = useAdmin();

  const testApiConnection = async () => {
    setIsLoading(true);
    setTestResult(null);

    try {
      console.log('Testing API connection...');
      const url = `${ApiConfig.baseUrl}/test-user-api.php`;
      console.log('Test URL:', url);

      // Test with timeout
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

      const response = await fetch(url, {
        signal: controller.signal,
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      clearTimeout(timeoutId);

      console.log('Response status:', response.status);
      console.log('Response headers:', Object.fromEntries(response.headers.entries()));

      const responseText = await response.text();
      console.log('Raw response:', responseText);

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        data = {
          success: false,
          message: 'Invalid JSON response',
          raw_response: responseText.substring(0, 500),
          parse_error: parseError.message
        };
      }

      setTestResult({
        status: response.status,
        ok: response.ok,
        data: data,
        url: url,
        timestamp: new Date().toISOString(),
        test_type: 'API Connection Test'
      });

    } catch (error) {
      console.error('API test error:', error);

      let errorDetails = {
        success: false,
        error: error.message || 'Unknown error',
        error_type: error.name || 'Unknown',
        timestamp: new Date().toISOString(),
        test_type: 'API Connection Test'
      };

      if (error.name === 'AbortError') {
        errorDetails.error = 'Request timed out - XAMPP server may not be running';
      } else if (error.message.includes('fetch')) {
        errorDetails.error = 'Network error - Cannot connect to XAMPP server';
        errorDetails.suggestion = 'Please check if XAMPP is running and Apache service is started';
      }

      setTestResult(errorDetails);
    } finally {
      setIsLoading(false);
    }
  };

  const testUserManagementApi = async () => {
    setIsLoading(true);
    setTestResult(null);

    try {
      console.log('Testing User Management API...');
      console.log('Admin context:', admin);

      if (!admin?.id) {
        setTestResult({
          success: false,
          error: 'No admin ID available - admin not authenticated',
          admin_context: admin,
          timestamp: new Date().toISOString()
        });
        setIsLoading(false);
        return;
      }

      const url = `${ApiConfig.endpoints.admin.manageUsers}?admin_id=${admin.id}`;
      console.log('Test URL:', url);

      const response = await fetch(url);
      console.log('Response status:', response.status);

      const responseText = await response.text();
      console.log('Raw response:', responseText);

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        data = {
          success: false,
          message: 'Invalid JSON response',
          raw_response: responseText,
          parse_error: parseError.message
        };
      }

      setTestResult({
        status: response.status,
        ok: response.ok,
        data: data,
        url: url,
        timestamp: new Date().toISOString()
      });

    } catch (error) {
      console.error('User Management API test error:', error);
      setTestResult({
        success: false,
        error: error.message,
        timestamp: new Date().toISOString()
      });
    } finally {
      setIsLoading(false);
    }
  };

  const runFullDiagnostic = async () => {
    setIsLoading(true);
    setTestResult(null);

    const diagnostic = {
      timestamp: new Date().toISOString(),
      test_type: 'Full Diagnostic',
      environment: {
        host: window.location.host,
        protocol: window.location.protocol,
        pathname: window.location.pathname,
        base_url: ApiConfig.baseUrl,
        user_management_endpoint: ApiConfig.endpoints.admin.manageUsers
      },
      admin_context: {
        admin_available: !!admin,
        admin_id: admin?.id || null,
        admin_role: admin?.role || null,
        admin_username: admin?.username || null
      },
      tests: []
    };

    // Test 1: Basic connectivity
    try {
      const response = await fetch(window.location.origin);
      diagnostic.tests.push({
        name: 'Basic Connectivity',
        status: 'PASS',
        details: `Can connect to ${window.location.origin}`
      });
    } catch (error) {
      diagnostic.tests.push({
        name: 'Basic Connectivity',
        status: 'FAIL',
        error: error.message
      });
    }

    // Test 2: XAMPP/API Base
    try {
      const xamppUrl = 'http://localhost/aureus-angel-alliance/';
      const response = await fetch(xamppUrl, {
        method: 'GET',
        signal: AbortSignal.timeout(5000)
      });
      diagnostic.tests.push({
        name: 'XAMPP Base Directory',
        status: response.ok ? 'PASS' : 'FAIL',
        details: `Status: ${response.status}, URL: ${xamppUrl}`
      });
    } catch (error) {
      diagnostic.tests.push({
        name: 'XAMPP Base Directory',
        status: 'FAIL',
        error: error.message,
        suggestion: 'XAMPP may not be running or Apache service is not started'
      });
    }

    // Test 3: API Directory
    try {
      const apiUrl = `${ApiConfig.baseUrl}/test-user-api.php`;
      const response = await fetch(apiUrl, {
        signal: AbortSignal.timeout(5000)
      });
      const text = await response.text();
      diagnostic.tests.push({
        name: 'API Test Endpoint',
        status: response.ok ? 'PASS' : 'FAIL',
        details: `Status: ${response.status}, Response length: ${text.length}`,
        response_preview: text.substring(0, 200)
      });
    } catch (error) {
      diagnostic.tests.push({
        name: 'API Test Endpoint',
        status: 'FAIL',
        error: error.message
      });
    }

    setTestResult(diagnostic);
    setIsLoading(false);
  };

  return (
    <Card className="bg-gray-800 border-gray-700">
      <CardHeader>
        <CardTitle className="text-white">API Connection Test</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex gap-2 flex-wrap">
          <Button
            onClick={testApiConnection}
            disabled={isLoading}
            className="bg-blue-600 hover:bg-blue-700"
          >
            {isLoading ? 'Testing...' : 'Test API Connection'}
          </Button>
          <Button
            onClick={testUserManagementApi}
            disabled={isLoading}
            className="bg-green-600 hover:bg-green-700"
          >
            {isLoading ? 'Testing...' : 'Test User Management API'}
          </Button>
          <Button
            onClick={() => setTestResult({ admin_context: admin, timestamp: new Date().toISOString() })}
            disabled={isLoading}
            className="bg-purple-600 hover:bg-purple-700"
          >
            Show Admin Context
          </Button>
          <Button
            onClick={() => runFullDiagnostic()}
            disabled={isLoading}
            className="bg-orange-600 hover:bg-orange-700"
          >
            {isLoading ? 'Running...' : 'Full Diagnostic'}
          </Button>
        </div>

        {testResult && (
          <div className="mt-4">
            <h3 className="text-white font-semibold mb-2">Test Result:</h3>
            <pre className="bg-gray-900 p-3 rounded text-sm text-gray-300 overflow-auto max-h-96">
              {JSON.stringify(testResult, null, 2)}
            </pre>
          </div>
        )}

        <div className="mt-4 text-sm text-gray-400 space-y-1">
          <p><strong>Base URL:</strong> {ApiConfig.baseUrl}</p>
          <p><strong>User Management Endpoint:</strong> {ApiConfig.endpoints.admin.manageUsers}</p>
          <p><strong>Current Host:</strong> {window.location.host}</p>
          <p><strong>Current Protocol:</strong> {window.location.protocol}</p>
          <p><strong>Admin ID:</strong> {admin?.id || 'Not available'}</p>
          <p><strong>Admin Role:</strong> {admin?.role || 'Not available'}</p>
        </div>
      </CardContent>
    </Card>
  );
};

export default ApiTest;
