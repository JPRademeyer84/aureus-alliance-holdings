import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { User } from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import ApiConfig from '@/config/api';

const ProfileTestButton: React.FC = () => {
  const { user } = useUser();
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

  const testProfileAPI = async () => {
    if (!user?.id) {
      setTestResult('❌ No user logged in');
      return;
    }

    setIsLoading(true);
    setTestResult('🔄 Testing profile API...');

    try {
      console.log('🧪 Testing profile API for user:', user.id);
      
      const response = await fetch(`${ApiConfig.endpoints.users.enhancedProfile}?action=get&user_id=${user.id}`, {
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      console.log('🧪 Response status:', response.status);
      console.log('🧪 Response headers:', Object.fromEntries(response.headers.entries()));

      if (response.ok) {
        const data = await response.json();
        console.log('🧪 Response data:', data);
        
        if (data.success) {
          setTestResult(`✅ Profile API working! User: ${data.data.profile.username}, Completion: ${data.data.profile.profile_completion}%`);
        } else {
          setTestResult(`❌ API returned error: ${data.message}`);
        }
      } else {
        setTestResult(`❌ HTTP Error: ${response.status} ${response.statusText}`);
      }
    } catch (error) {
      console.error('🧪 Profile API test error:', error);
      setTestResult(`❌ Network Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed top-16 left-4 z-50 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded shadow-lg">
      <Button
        onClick={testProfileAPI}
        disabled={isLoading}
        variant="outline"
        size="sm"
        className="text-white border-white hover:bg-blue-800 mb-2"
        title="Test profile API"
      >
        <User className="w-4 h-4 mr-2" />
        {isLoading ? 'Testing...' : 'Test Profile API'}
      </Button>
      {testResult && (
        <div className="text-xs mt-1 p-1 bg-black/20 rounded max-w-xs">
          {testResult}
        </div>
      )}
    </div>
  );
};

export default ProfileTestButton;
