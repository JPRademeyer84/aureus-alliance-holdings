import React, { createContext, useContext, useState, useCallback } from 'react';

interface DebugInfo {
  component: string;
  action: string;
  data?: any;
  timestamp: Date;
}

interface DebugContextType {
  isDebugMode: boolean;
  toggleDebugMode: () => void;
  logDebug: (component: string, action: string, data?: any) => void;
  debugHistory: DebugInfo[];
  clearDebugHistory: () => void;
  logApiCall: (url: string, method: string, data?: any, response?: any, error?: any) => void;
  logUserAction: (action: string, data?: any) => void;
  logComponentRender: (component: string, props?: any) => void;
}

const DebugContext = createContext<DebugContextType | undefined>(undefined);

export const DebugProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [isDebugMode, setIsDebugMode] = useState(() => {
    return localStorage.getItem('debug-mode') === 'true' || 
           window.location.search.includes('debug=true') ||
           process.env.NODE_ENV === 'development';
  });
  
  const [debugHistory, setDebugHistory] = useState<DebugInfo[]>([]);

  const toggleDebugMode = useCallback(() => {
    const newMode = !isDebugMode;
    setIsDebugMode(newMode);
    localStorage.setItem('debug-mode', newMode.toString());
    console.log(`Debug mode ${newMode ? 'enabled' : 'disabled'}`);
  }, [isDebugMode]);

  const logDebug = useCallback((component: string, action: string, data?: any) => {
    if (!isDebugMode) return;
    
    const debugInfo: DebugInfo = {
      component,
      action,
      data,
      timestamp: new Date()
    };
    
    setDebugHistory(prev => [...prev.slice(-99), debugInfo]); // Keep last 100 entries
    console.log(`[DEBUG] ${component}: ${action}`, data);
  }, [isDebugMode]);

  const clearDebugHistory = useCallback(() => {
    setDebugHistory([]);
    console.log('[DEBUG] History cleared');
  }, []);

  const logApiCall = useCallback((url: string, method: string, data?: any, response?: any, error?: any) => {
    if (!isDebugMode) return;
    
    const apiInfo = {
      url,
      method,
      requestData: data,
      response,
      error,
      timestamp: new Date()
    };
    
    logDebug('API', `${method} ${url}`, apiInfo);
    
    if (error) {
      console.error(`[API ERROR] ${method} ${url}:`, error);
    } else {
      console.log(`[API SUCCESS] ${method} ${url}:`, response);
    }
  }, [isDebugMode, logDebug]);

  const logUserAction = useCallback((action: string, data?: any) => {
    if (!isDebugMode) return;
    
    logDebug('USER', action, data);
  }, [isDebugMode, logDebug]);

  const logComponentRender = useCallback((component: string, props?: any) => {
    if (!isDebugMode) return;
    
    logDebug('RENDER', component, props);
  }, [isDebugMode, logDebug]);

  const value: DebugContextType = {
    isDebugMode,
    toggleDebugMode,
    logDebug,
    debugHistory,
    clearDebugHistory,
    logApiCall,
    logUserAction,
    logComponentRender
  };

  return (
    <DebugContext.Provider value={value}>
      {children}
    </DebugContext.Provider>
  );
};

export const useDebug = (): DebugContextType => {
  const context = useContext(DebugContext);
  if (context === undefined) {
    throw new Error('useDebug must be used within a DebugProvider');
  }
  return context;
};

// Debug hook for components
export const useComponentDebug = (componentName: string) => {
  const { logComponentRender, logUserAction, logDebug } = useDebug();
  
  React.useEffect(() => {
    logComponentRender(componentName);
  }, [componentName, logComponentRender]);
  
  return {
    logAction: (action: string, data?: any) => logUserAction(`${componentName}: ${action}`, data),
    logDebug: (action: string, data?: any) => logDebug(componentName, action, data)
  };
};

// Enhanced fetch wrapper with debug logging
export const debugFetch = async (url: string, options?: RequestInit) => {
  const { logApiCall } = useDebug();
  const method = options?.method || 'GET';
  
  try {
    const response = await fetch(url, options);
    const data = await response.json();
    
    logApiCall(url, method, options?.body, data, response.ok ? null : `HTTP ${response.status}`);
    
    return { response, data };
  } catch (error) {
    logApiCall(url, method, options?.body, null, error);
    throw error;
  }
};
