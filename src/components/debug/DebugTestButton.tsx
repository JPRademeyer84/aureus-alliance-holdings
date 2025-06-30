import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { TestTube } from 'lucide-react';

const DebugTestButton: React.FC = () => {
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
  const runDebugTests = () => {
    console.log('🧪 Debug Test: Starting debug system tests...');
    
    // Test console logging
    console.log('✅ Test 1: Console.log is working');
    console.info('ℹ️ Test 2: Console.info is working');
    console.warn('⚠️ Test 3: Console.warn is working');
    
    // Test network monitoring
    setTimeout(() => {
      console.log('🌐 Test 4: Testing network monitoring...');
      fetch('/api/debug.php')
        .then(response => response.json())
        .then(data => {
          console.log('✅ Test 5: Network request successful:', data);
        })
        .catch(error => {
          console.error('❌ Test 5: Network request failed:', error);
        });
    }, 500);
    
    // Test error tracking
    setTimeout(() => {
      console.log('🔥 Test 6: Testing error tracking...');
      try {
        // Intentionally cause an error for testing
        const testObj: any = null;
        testObj.nonExistentProperty.someMethod();
      } catch (error) {
        console.error('❌ Test 6: Caught test error:', error);
      }
    }, 1000);
    
    // Test user actions
    setTimeout(() => {
      console.log('👤 Test 7: Testing user action logging...');
      console.log('🎯 Debug Test: All tests completed!');
    }, 1500);
  };

  return (
    <Button
      onClick={runDebugTests}
      variant="outline"
      size="sm"
      className="fixed top-4 left-4 z-50 bg-purple-600 hover:bg-purple-700 text-white border-purple-500"
      title="Run debug system tests"
    >
      <TestTube className="w-4 h-4 mr-2" />
      Test Debug
    </Button>
  );
};

export default DebugTestButton;
