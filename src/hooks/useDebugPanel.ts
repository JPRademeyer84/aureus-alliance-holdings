import { useState, useEffect } from 'react';

interface DebugFeature {
  feature_key: string;
  feature_name: string;
  feature_description: string;
  config_data: any;
  access_level: string;
}

interface DebugPanelState {
  isOpen: boolean;
  features: DebugFeature[];
  isEnabled: boolean;
  loading: boolean;
}

export const useDebugPanel = () => {
  const [state, setState] = useState<DebugPanelState>({
    isOpen: false,
    features: [],
    isEnabled: false,
    loading: true
  });

  useEffect(() => {
    checkDebugAvailability();
    
    // Add keyboard shortcut for debug panel (Ctrl+Shift+D)
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.shiftKey && event.key === 'D') {
        event.preventDefault();
        if (state.isEnabled) {
          toggleDebugPanel();
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [state.isEnabled]);

  const checkDebugAvailability = async () => {
    try {
      const response = await fetch('/api/admin/debug-config.php?action=active', {
        credentials: 'include'
      });

      // Handle 401 (Unauthorized) gracefully - this is expected when not logged in as admin
      // Enable debug panel with basic features for non-admin users
      if (response.status === 401) {
        setState(prev => ({
          ...prev,
          isEnabled: true, // Enable for all users
          loading: false,
          features: [
            {
              feature_key: 'console_logs',
              feature_name: 'Console Logs',
              feature_description: 'Monitor console output',
              config_data: {},
              access_level: 'user'
            },
            {
              feature_key: 'error_tracking',
              feature_name: 'Error Tracking',
              feature_description: 'Track JavaScript errors',
              config_data: {},
              access_level: 'user'
            },
            {
              feature_key: 'network_monitor',
              feature_name: 'Network Monitor',
              feature_description: 'Monitor network requests',
              config_data: {},
              access_level: 'user'
            }
          ]
        }));
        return;
      }

      const data = await response.json();

      if (data.success) {
        setState(prev => ({
          ...prev,
          features: data.data.features,
          isEnabled: data.data.debug_enabled,
          loading: false
        }));
      } else {
        setState(prev => ({
          ...prev,
          isEnabled: false,
          loading: false
        }));
      }
    } catch (error) {
      // Silently enable debug panel with basic features
      setState(prev => ({
        ...prev,
        isEnabled: true,
        loading: false,
        features: [
          {
            feature_key: 'console_logs',
            feature_name: 'Console Logs',
            feature_description: 'Monitor console output',
            config_data: {},
            access_level: 'user'
          },
          {
            feature_key: 'error_tracking',
            feature_name: 'Error Tracking',
            feature_description: 'Track JavaScript errors',
            config_data: {},
            access_level: 'user'
          }
        ]
      }));
    }
  };

  const toggleDebugPanel = () => {
    if (!state.isEnabled) return;
    
    setState(prev => ({
      ...prev,
      isOpen: !prev.isOpen
    }));
  };

  const openDebugPanel = () => {
    if (!state.isEnabled) return;
    
    setState(prev => ({
      ...prev,
      isOpen: true
    }));
  };

  const closeDebugPanel = () => {
    setState(prev => ({
      ...prev,
      isOpen: false
    }));
  };

  const refreshFeatures = () => {
    checkDebugAvailability();
  };

  return {
    isOpen: state.isOpen,
    features: state.features,
    isEnabled: state.isEnabled,
    loading: state.loading,
    toggleDebugPanel,
    openDebugPanel,
    closeDebugPanel,
    refreshFeatures
  };
};
