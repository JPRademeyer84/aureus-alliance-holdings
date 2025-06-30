import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import { useAdmin } from '@/contexts/AdminContext';
import {
  Bug,
  Settings,
  Eye,
  EyeOff,
  Power,
  PowerOff,
  Monitor,
  Database,
  Network,
  Activity,
  Shield,
  Clock,
  Users,
  RefreshCw,
  Save,
  AlertTriangle
} from 'lucide-react';

interface DebugConfig {
  id: string;
  feature_key: string;
  feature_name: string;
  feature_description: string;
  is_enabled: boolean;
  is_visible: boolean;
  access_level: 'admin' | 'developer' | 'support';
  config_data: any;
  allowed_environments: string[];
  created_by_username: string;
  updated_by_username: string;
  created_at: string;
  updated_at: string;
}

interface DebugSession {
  id: string;
  feature_key: string;
  feature_name: string;
  action_type: string;
  user_username: string;
  admin_username: string;
  ip_address: string;
  environment: string;
  created_at: string;
  action_data: any;
}

const DebugManager: React.FC = () => {
  const [configs, setConfigs] = useState<DebugConfig[]>([]);
  const [sessions, setSessions] = useState<DebugSession[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'config' | 'features' | 'sessions'>('config');
  const [editingConfig, setEditingConfig] = useState<DebugConfig | null>(null);
  const { toast } = useToast();
  const { admin } = useAdmin();

  useEffect(() => {
    // Only fetch data if admin is authenticated
    if (admin) {
      fetchDebugConfigs();
      if (activeTab === 'sessions') {
        fetchDebugSessions();
      }
    } else {
      setLoading(false);
      console.log('Debug Manager: No admin authenticated, skipping data fetch');
    }
  }, [activeTab, admin]);

  const fetchDebugConfigs = async () => {
    setLoading(true);
    try {
      console.log('Fetching debug configs...');

      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=list', {
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      console.log('Debug config response status:', response.status);
      console.log('Debug config response headers:', Object.fromEntries(response.headers.entries()));

      if (!response.ok) {
        const errorText = await response.text();
        console.error('Debug config HTTP error:', response.status, response.statusText, errorText);

        if (response.status === 401) {
          toast({
            title: "Authentication Error",
            description: "Admin session expired. Please log in again.",
            variant: "destructive"
          });
          return;
        }

        throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
      }

      const data = await response.json();
      console.log('Debug config response data:', data);

      if (data.success) {
        // Convert database integers to booleans
        const processedConfigs = data.data.map((config: any) => ({
          ...config,
          is_enabled: Boolean(config.is_enabled),
          is_visible: Boolean(config.is_visible)
        }));

        console.log('Debug configs processed:', processedConfigs.map(c => ({
          name: c.feature_name,
          key: c.feature_key,
          enabled: c.is_enabled,
          updated: c.updated_at
        })));

        setConfigs(processedConfigs);
      } else {
        console.error('Debug config API error:', data.error);
        toast({
          title: "Error",
          description: data.error || "Failed to fetch debug configurations",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Error fetching debug configs:', error);
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      console.error('Error details:', {
        name: error instanceof Error ? error.name : 'Unknown',
        message: errorMessage,
        stack: error instanceof Error ? error.stack : 'No stack trace'
      });

      toast({
        title: "Error",
        description: `Failed to fetch debug configurations: ${errorMessage}`,
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchDebugSessions = async () => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=sessions&limit=50', {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setSessions(data.data.sessions || []);
      } else {
        console.error('Sessions API error:', data.error);
        setSessions([]); // Set empty array on error
        toast({
          title: "Error",
          description: data.error || "Failed to fetch debug sessions",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Error fetching debug sessions:', error);
      setSessions([]); // Set empty array on error
      toast({
        title: "Error",
        description: `Failed to fetch debug sessions: ${error instanceof Error ? error.message : 'Unknown error'}`,
        variant: "destructive"
      });
    }
  };

  const toggleFeature = async (featureKey: string, enabled: boolean) => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=toggle', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          feature_key: featureKey,
          enabled: enabled
        })
      });

      const data = await response.json();

      if (data.success) {
        // Update local state immediately
        setConfigs(prevConfigs => prevConfigs.map(config =>
          config.feature_key === featureKey
            ? { ...config, is_enabled: enabled, updated_at: new Date().toISOString() }
            : config
        ));

        // Also refresh from database to ensure consistency
        setTimeout(() => {
          fetchDebugConfigs();
        }, 500);

        toast({
          title: "Success",
          description: `Debug feature ${enabled ? 'enabled' : 'disabled'} successfully`,
        });
      } else {
        toast({
          title: "Error",
          description: data.error || "Failed to toggle debug feature",
          variant: "destructive"
        });

        // Refresh on error to ensure UI is in sync
        fetchDebugConfigs();
      }
    } catch (error) {
      console.error('Error toggling debug feature:', error);
      toast({
        title: "Error",
        description: "Failed to toggle debug feature",
        variant: "destructive"
      });

      // Refresh on error to ensure UI is in sync
      fetchDebugConfigs();
    }
  };

  const updateConfig = async (config: DebugConfig) => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(config)
      });
      
      const data = await response.json();
      
      if (data.success) {
        setConfigs(configs.map(c => 
          c.feature_key === config.feature_key ? config : c
        ));
        setEditingConfig(null);
        
        toast({
          title: "Success",
          description: "Debug configuration updated successfully",
        });
      } else {
        toast({
          title: "Error",
          description: data.error || "Failed to update debug configuration",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Error updating debug config:', error);
      toast({
        title: "Error",
        description: "Failed to update debug configuration",
        variant: "destructive"
      });
    }
  };

  const getFeatureIcon = (featureKey: string) => {
    try {
      switch (featureKey) {
        case 'console_logs': return <Monitor className="w-4 h-4" />;
        case 'network_monitor': return <Network className="w-4 h-4" />;
        case 'system_info': return <Settings className="w-4 h-4" />;
        case 'database_queries': return <Database className="w-4 h-4" />;
        case 'api_testing': return <Activity className="w-4 h-4" />;
        case 'cache_management': return <RefreshCw className="w-4 h-4" />;
        case 'error_tracking': return <AlertTriangle className="w-4 h-4" />;
        case 'performance_metrics': return <Activity className="w-4 h-4" />;
        default: return <Bug className="w-4 h-4" />;
      }
    } catch (error) {
      console.error('Error rendering icon for feature:', featureKey, error);
      return <div className="w-4 h-4 bg-gray-500 rounded" />; // Fallback div
    }
  };

  const getAccessLevelColor = (level: string) => {
    switch (level) {
      case 'admin': return 'text-red-400';
      case 'developer': return 'text-yellow-400';
      case 'support': return 'text-blue-400';
      default: return 'text-gray-400';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <RefreshCw className="w-8 h-8 animate-spin text-gold" />
      </div>
    );
  }

  // Check if admin is authenticated
  if (!admin) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <Shield className="w-16 h-16 mx-auto mb-4 text-gray-500" />
          <h3 className="text-lg font-semibold text-white mb-2">Authentication Required</h3>
          <p className="text-gray-400">Please log in as an admin to access the Debug Manager.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white flex items-center gap-2">
            <Bug className="w-6 h-6 text-gold" />
            Debug Manager
          </h1>
          <p className="text-gray-400 mt-1">
            Control debugging features and monitor debug activity
          </p>
        </div>
        
        <Button
          onClick={() => {
            console.log('Manual refresh triggered');
            if (admin) {
              fetchDebugConfigs();
            }
          }}
          disabled={loading || !admin}
          className="bg-gold-gradient text-black font-semibold"
        >
          <RefreshCw className={`w-4 h-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
          {loading ? 'Refreshing...' : 'Refresh'}
        </Button>
      </div>

      {/* Tabs */}
      <div className="flex space-x-1 bg-gray-800 p-1 rounded-lg">
        <button
          onClick={() => setActiveTab('config')}
          className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
            activeTab === 'config'
              ? 'bg-gold text-black'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          <Settings className="w-4 h-4 inline mr-2" />
          Debug Configuration
        </button>
        <button
          onClick={() => setActiveTab('features')}
          className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
            activeTab === 'features'
              ? 'bg-gold text-black'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          <Monitor className="w-4 h-4 inline mr-2" />
          Debug Features
        </button>
        <button
          onClick={() => setActiveTab('sessions')}
          className={`flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors ${
            activeTab === 'sessions'
              ? 'bg-gold text-black'
              : 'text-gray-400 hover:text-white'
          }`}
        >
          <Users className="w-4 h-4 inline mr-2" />
          Debug Sessions
        </button>
      </div>

      {/* Configuration Tab */}
      {activeTab === 'config' && (
        <div className="space-y-4">
          {/* Global Debug Status */}
          <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-white">Global Debug Status</h3>
                <p className="text-gray-400 text-sm">
                  {configs.filter(c => c.is_enabled).length} of {configs.length} debug features enabled
                </p>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm text-gray-400">Debug System:</span>
                <span className={`px-2 py-1 rounded text-xs font-semibold ${
                  configs.some(c => c.is_enabled) 
                    ? 'bg-green-500/20 text-green-400' 
                    : 'bg-red-500/20 text-red-400'
                }`}>
                  {configs.some(c => c.is_enabled) ? 'ACTIVE' : 'INACTIVE'}
                </span>
              </div>
            </div>
          </div>

          {/* Debug Features */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {configs.length === 0 && (
              <div className="col-span-2 text-center py-8">
                <p className="text-gray-400">No debug configurations found</p>
              </div>
            )}
            {configs.map((config) => (
              <div
                key={config.feature_key}
                className="bg-gray-800 rounded-lg p-4 border border-gray-700"
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="text-gold">
                      {getFeatureIcon(config.feature_key)}
                    </div>
                    <div>
                      <h4 className="font-semibold text-white">{config.feature_name}</h4>
                      <p className="text-sm text-gray-400">{config.feature_description}</p>
                    </div>
                  </div>
                  
                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => toggleFeature(config.feature_key, !config.is_enabled)}
                      className={`p-1 rounded ${
                        config.is_enabled 
                          ? 'text-green-400 hover:bg-green-500/20' 
                          : 'text-gray-500 hover:bg-gray-600'
                      }`}
                      title={config.is_enabled ? 'Disable' : 'Enable'}
                    >
                      {config.is_enabled ? <Power className="w-4 h-4" /> : <PowerOff className="w-4 h-4" />}
                    </button>
                    
                    <button
                      onClick={() => setEditingConfig(config)}
                      className="p-1 rounded text-gray-400 hover:text-white hover:bg-gray-600"
                      title="Edit Configuration"
                    >
                      <Settings className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div className="flex items-center justify-between text-xs">
                  <div className="flex items-center gap-4">
                    <span className={`flex items-center gap-1 ${getAccessLevelColor(config.access_level)}`}>
                      <Shield className="w-3 h-3" />
                      {config.access_level}
                    </span>
                    
                    <span className="flex items-center gap-1 text-gray-400">
                      {config.is_visible ? <Eye className="w-3 h-3" /> : <EyeOff className="w-3 h-3" />}
                      {config.is_visible ? 'Visible' : 'Hidden'}
                    </span>
                  </div>
                  
                  <div className="flex items-center gap-1 text-gray-500">
                    <Clock className="w-3 h-3" />
                    {new Date(config.updated_at).toLocaleDateString()}
                  </div>
                </div>

                {config.allowed_environments && config.allowed_environments.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {config.allowed_environments.map((env) => (
                      <span
                        key={env}
                        className="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded"
                      >
                        {env}
                      </span>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Debug Features Tab */}
      {activeTab === 'features' && (
        <div className="space-y-4">
          {/* Active Features Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {configs.filter(config => config.is_enabled).length === 0 && (
              <div className="col-span-2 text-center py-8">
                <div className="text-gray-500 mb-4">
                  <Monitor className="w-16 h-16 mx-auto mb-4" />
                </div>
                <h3 className="text-lg font-semibold text-white mb-2">No Debug Features Enabled</h3>
                <p className="text-gray-400">Enable debug features in the Configuration tab to see them here.</p>
              </div>
            )}
            {configs.filter(config => config.is_enabled).map((config) => (
              <div
                key={config.feature_key}
                className="bg-gray-800 rounded-lg p-4 border border-gray-700"
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="text-gold">
                      {getFeatureIcon(config.feature_key)}
                    </div>
                    <div>
                      <h4 className="font-semibold text-white">{config.feature_name}</h4>
                      <p className="text-sm text-gray-400">{config.feature_description}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded font-semibold">
                      ACTIVE
                    </span>
                  </div>
                </div>

                <div className="flex items-center justify-between text-sm">
                  <div className="flex items-center gap-4">
                    <span className="text-gray-400">
                      Access: <span className="text-white capitalize">{config.access_level}</span>
                    </span>
                    <span className="text-gray-400">
                      Status: <span className="text-green-400">Running</span>
                    </span>
                  </div>
                  <div className="flex gap-1">
                    <button
                      onClick={() => {
                        // Open debug panel for this feature
                        console.log('Opening debug panel for:', config.feature_key);
                      }}
                      className="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded hover:bg-blue-500/30 transition-colors"
                    >
                      <Eye className="w-3 h-3 inline mr-1" />
                      View
                    </button>
                  </div>
                </div>

                {config.allowed_environments && config.allowed_environments.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {config.allowed_environments.map((env) => (
                      <span
                        key={env}
                        className="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded"
                      >
                        {env}
                      </span>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Sessions Tab */}
      {activeTab === 'sessions' && (
        <div className="space-y-4">
          <div className="bg-gray-800 rounded-lg overflow-hidden">
            <div className="p-4 border-b border-gray-700">
              <h3 className="text-lg font-semibold text-white">Recent Debug Activity</h3>
              <p className="text-gray-400 text-sm">Monitor who is using debug features</p>
            </div>
            
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-700">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Feature</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Action</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">User</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Environment</th>
                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Time</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-700">
                  {sessions.map((session) => (
                    <tr key={session.id} className="hover:bg-gray-700/50">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          {getFeatureIcon(session.feature_key)}
                          <span className="text-white text-sm">{session.feature_name}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <span className="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded">
                          {session.action_type}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="text-gray-300 text-sm">
                          {session.admin_username || session.user_username || 'Anonymous'}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="text-gray-400 text-sm">{session.environment}</span>
                      </td>
                      <td className="px-4 py-3">
                        <span className="text-gray-400 text-sm">
                          {new Date(session.created_at).toLocaleString()}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DebugManager;
