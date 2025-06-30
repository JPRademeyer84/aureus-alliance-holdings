import React, { useState, useEffect } from 'react';
import { Shield, AlertTriangle, Activity, Download, RefreshCw } from 'lucide-react';
import { ApiConfig } from '../../config/api';

interface SecurityEvent {
  id: string;
  event_type: string;
  event_subtype: string;
  security_level: 'info' | 'warning' | 'critical' | 'emergency';
  user_id?: number;
  admin_id?: number;
  ip_address: string;
  event_message: string;
  event_timestamp: string;
  event_data: any;
}

interface SecurityAlert {
  id: string;
  alert_type: string;
  alert_level: 'warning' | 'critical' | 'emergency';
  alert_message: string;
  alert_data: any;
  acknowledged: boolean;
  created_at: string;
}

interface DashboardData {
  events_summary: any;
  active_alerts: any[];
  recent_critical: SecurityEvent[];
  trends: any[];
  top_ips: any[];
}

const SecurityDashboard: React.FC = () => {
  const [dashboardData, setDashboardData] = useState<DashboardData | null>(null);
  const [events, setEvents] = useState<SecurityEvent[]>([]);
  const [alerts, setAlerts] = useState<SecurityAlert[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'events' | 'alerts'>('overview');
  const [autoRefresh, setAutoRefresh] = useState(true);

  useEffect(() => {
    loadDashboardData();
    
    if (autoRefresh) {
      const interval = setInterval(loadDashboardData, 30000); // Refresh every 30 seconds
      return () => clearInterval(interval);
    }
  }, [autoRefresh]);

  const loadDashboardData = async () => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.securityMonitoring}?action=dashboard`, {
        credentials: 'include'
      });
      
      if (response.ok) {
        const data = await response.json();
        setDashboardData(data.data);
      }
    } catch (error) {
      console.error('Failed to load dashboard data:', error);
    }
  };

  const loadEvents = async () => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.securityMonitoring}?action=events&limit=100`, {
        credentials: 'include'
      });
      
      if (response.ok) {
        const data = await response.json();
        setEvents(data.data);
      }
    } catch (error) {
      console.error('Failed to load events:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadAlerts = async () => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.securityMonitoring}?action=alerts`, {
        credentials: 'include'
      });
      
      if (response.ok) {
        const data = await response.json();
        setAlerts(data.data);
      }
    } catch (error) {
      console.error('Failed to load alerts:', error);
    }
  };

  const acknowledgeAlert = async (alertId: string) => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.securityMonitoring}?action=acknowledge_alert`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ alert_id: alertId })
      });
      
      if (response.ok) {
        loadAlerts(); // Refresh alerts
        loadDashboardData(); // Refresh dashboard
      }
    } catch (error) {
      console.error('Failed to acknowledge alert:', error);
    }
  };

  const exportData = async (format: 'json' | 'csv') => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.securityMonitoring}?action=export&format=${format}`, {
        credentials: 'include'
      });
      
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `security_events_${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      }
    } catch (error) {
      console.error('Failed to export data:', error);
    }
  };

  const getLevelColor = (level: string) => {
    switch (level) {
      case 'emergency': return 'text-red-600 bg-red-100';
      case 'critical': return 'text-red-500 bg-red-50';
      case 'warning': return 'text-yellow-600 bg-yellow-100';
      case 'info': return 'text-blue-600 bg-blue-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const formatTimestamp = (timestamp: string) => {
    return new Date(timestamp).toLocaleString();
  };

  useEffect(() => {
    if (activeTab === 'events') {
      loadEvents();
    } else if (activeTab === 'alerts') {
      loadAlerts();
    }
  }, [activeTab]);

  if (loading && !dashboardData) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <Shield className="h-8 w-8 text-blue-600" />
          <h1 className="text-2xl font-bold text-gray-900">Security Monitoring</h1>
        </div>
        
        <div className="flex items-center space-x-3">
          <label className="flex items-center space-x-2">
            <input
              type="checkbox"
              checked={autoRefresh}
              onChange={(e) => setAutoRefresh(e.target.checked)}
              className="rounded border-gray-300"
            />
            <span className="text-sm text-gray-600">Auto-refresh</span>
          </label>
          
          <button
            onClick={loadDashboardData}
            className="flex items-center space-x-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            <RefreshCw className="h-4 w-4" />
            <span>Refresh</span>
          </button>
          
          <div className="flex space-x-2">
            <button
              onClick={() => exportData('json')}
              className="flex items-center space-x-2 px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
            >
              <Download className="h-4 w-4" />
              <span>JSON</span>
            </button>
            <button
              onClick={() => exportData('csv')}
              className="flex items-center space-x-2 px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
            >
              <Download className="h-4 w-4" />
              <span>CSV</span>
            </button>
          </div>
        </div>
      </div>

      {/* Active Alerts */}
      {dashboardData?.active_alerts && dashboardData.active_alerts.length > 0 && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center space-x-2 mb-3">
            <AlertTriangle className="h-5 w-5 text-red-600" />
            <h2 className="text-lg font-semibold text-red-800">Active Security Alerts</h2>
          </div>
          <div className="space-y-2">
            {dashboardData.active_alerts.map((alert: any) => (
              <div key={alert.alert_level} className="flex items-center justify-between bg-white p-3 rounded border">
                <div>
                  <span className={`px-2 py-1 rounded text-xs font-medium ${getLevelColor(alert.alert_level)}`}>
                    {alert.alert_level.toUpperCase()}
                  </span>
                  <span className="ml-2 text-gray-900">{alert.count} alerts</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Navigation Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {[
            { id: 'overview', label: 'Overview', icon: Activity },
            { id: 'events', label: 'Security Events', icon: Shield },
            { id: 'alerts', label: 'Alerts', icon: AlertTriangle }
          ].map(({ id, label, icon: Icon }) => (
            <button
              key={id}
              onClick={() => setActiveTab(id as any)}
              className={`flex items-center space-x-2 py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <Icon className="h-4 w-4" />
              <span>{label}</span>
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && dashboardData && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Recent Critical Events */}
          <div className="bg-white rounded-lg border p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Critical Events</h3>
            <div className="space-y-3">
              {dashboardData.recent_critical.slice(0, 5).map((event) => (
                <div key={event.id} className="flex items-start space-x-3 p-3 bg-red-50 rounded border">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${getLevelColor(event.security_level)}`}>
                        {event.security_level.toUpperCase()}
                      </span>
                      <span className="text-sm text-gray-600">{event.event_type}</span>
                    </div>
                    <p className="text-sm text-gray-900 mt-1">{event.event_message}</p>
                    <p className="text-xs text-gray-500 mt-1">
                      {formatTimestamp(event.event_timestamp)} • {event.ip_address}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Top IP Addresses */}
          <div className="bg-white rounded-lg border p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Top IP Addresses (24h)</h3>
            <div className="space-y-3">
              {dashboardData.top_ips.slice(0, 5).map((ip) => (
                <div key={ip.ip_address} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                  <div>
                    <p className="font-medium text-gray-900">{ip.ip_address}</p>
                    <p className="text-sm text-gray-600">{ip.event_count} events</p>
                  </div>
                  {ip.critical_count > 0 && (
                    <span className="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">
                      {ip.critical_count} critical
                    </span>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {activeTab === 'events' && (
        <div className="bg-white rounded-lg border">
          <div className="p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Security Events</h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Level
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Message
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      IP Address
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Timestamp
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {events.map((event) => (
                    <tr key={event.id}>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 py-1 rounded text-xs font-medium ${getLevelColor(event.security_level)}`}>
                          {event.security_level.toUpperCase()}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {event.event_type}
                        {event.event_subtype && (
                          <div className="text-xs text-gray-500">{event.event_subtype}</div>
                        )}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {event.event_message}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {event.ip_address}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {formatTimestamp(event.event_timestamp)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'alerts' && (
        <div className="bg-white rounded-lg border">
          <div className="p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Security Alerts</h3>
            <div className="space-y-4">
              {alerts.map((alert) => (
                <div key={alert.id} className="border rounded-lg p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center space-x-2 mb-2">
                        <span className={`px-2 py-1 rounded text-xs font-medium ${getLevelColor(alert.alert_level)}`}>
                          {alert.alert_level.toUpperCase()}
                        </span>
                        <span className="text-sm font-medium text-gray-900">{alert.alert_type}</span>
                      </div>
                      <p className="text-gray-700 mb-2">{alert.alert_message}</p>
                      <p className="text-xs text-gray-500">{formatTimestamp(alert.created_at)}</p>
                    </div>
                    
                    {!alert.acknowledged && (
                      <button
                        onClick={() => acknowledgeAlert(alert.id)}
                        className="ml-4 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                      >
                        Acknowledge
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SecurityDashboard;
