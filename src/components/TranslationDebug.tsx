import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { RefreshCw, Database, Globe, AlertCircle, CheckCircle } from 'lucide-react';

interface DebugInfo {
  database_connected: boolean;
  tables: {
    languages: { exists: boolean; count: number };
    translation_keys: { exists: boolean; count: number };
    translations: { exists: boolean; count: number };
  };
  setup_needed: boolean;
  message: string;
}

const TranslationDebug: React.FC = () => {
  const [debugInfo, setDebugInfo] = useState<DebugInfo | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const checkDatabase = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/test-db.php');
      const data = await response.json();
      
      if (data.success) {
        setDebugInfo(data);
      } else {
        setError(data.message || 'Database check failed');
      }
    } catch (err) {
      setError('Failed to connect to database API');
      console.error('Database check error:', err);
    } finally {
      setLoading(false);
    }
  };

  const setupDatabase = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/setup-translations.php');
      const data = await response.json();
      
      if (data.success) {
        alert('Database setup complete! ' + data.results.join(', '));
        checkDatabase(); // Refresh the status
      } else {
        setError(data.message || 'Setup failed');
      }
    } catch (err) {
      setError('Failed to setup database');
      console.error('Setup error:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    checkDatabase();
  }, []);

  return (
    <Card className="bg-gray-800 border-gray-700 max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle className="text-white flex items-center gap-2">
          <Database className="h-5 w-5" />
          Translation System Debug
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="flex gap-2">
          <Button 
            onClick={checkDatabase} 
            disabled={loading}
            variant="outline"
            className="border-gray-600 text-white hover:bg-gray-700"
          >
            <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
            Check Status
          </Button>
          
          {debugInfo?.setup_needed && (
            <Button 
              onClick={setupDatabase} 
              disabled={loading}
              className="bg-gold hover:bg-gold/80 text-black"
            >
              <Database className="h-4 w-4 mr-2" />
              Setup Database
            </Button>
          )}
        </div>

        {error && (
          <div className="bg-red-900/20 border border-red-500 rounded p-3 flex items-center gap-2">
            <AlertCircle className="h-4 w-4 text-red-400" />
            <span className="text-red-400">{error}</span>
          </div>
        )}

        {debugInfo && (
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              {debugInfo.database_connected ? (
                <CheckCircle className="h-4 w-4 text-green-400" />
              ) : (
                <AlertCircle className="h-4 w-4 text-red-400" />
              )}
              <span className="text-white">
                Database: {debugInfo.database_connected ? 'Connected' : 'Disconnected'}
              </span>
            </div>

            <div className="grid grid-cols-1 gap-3">
              {Object.entries(debugInfo.tables).map(([tableName, tableInfo]) => (
                <div key={tableName} className="bg-gray-700 rounded p-3">
                  <div className="flex items-center justify-between">
                    <span className="text-white font-medium capitalize">
                      {tableName.replace('_', ' ')}
                    </span>
                    <div className="flex items-center gap-2">
                      <Badge 
                        variant={tableInfo.exists ? "default" : "destructive"}
                        className={tableInfo.exists ? "bg-green-600" : "bg-red-600"}
                      >
                        {tableInfo.exists ? 'Exists' : 'Missing'}
                      </Badge>
                      {tableInfo.exists && (
                        <Badge variant="outline" className="text-gray-300 border-gray-500">
                          {tableInfo.count} rows
                        </Badge>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="bg-gray-700 rounded p-3">
              <div className="flex items-center gap-2">
                <Globe className="h-4 w-4 text-blue-400" />
                <span className="text-white font-medium">Status:</span>
                <span className={debugInfo.setup_needed ? "text-yellow-400" : "text-green-400"}>
                  {debugInfo.message}
                </span>
              </div>
            </div>

            {debugInfo.setup_needed && (
              <div className="bg-yellow-900/20 border border-yellow-500 rounded p-3">
                <p className="text-yellow-400 text-sm">
                  ⚠️ Database tables need to be created. Click "Setup Database" to automatically create all required tables and insert default data.
                </p>
              </div>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default TranslationDebug;
