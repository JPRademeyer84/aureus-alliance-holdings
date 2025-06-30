import { useCallback } from 'react';
import { useDebug } from '@/contexts/DebugContext';

export const useDebugUtils = (componentName: string) => {
  const { logDebug, logApiCall, logUserAction, isDebugMode } = useDebug();

  const debugLog = useCallback((action: string, data?: any) => {
    if (isDebugMode) {
      logDebug(componentName, action, data);
    }
  }, [componentName, logDebug, isDebugMode]);

  const debugApiCall = useCallback(async (
    url: string, 
    options?: RequestInit,
    description?: string
  ) => {
    const method = options?.method || 'GET';
    const startTime = performance.now();
    
    try {
      debugLog(`API Call Start: ${description || method} ${url}`, options);
      
      const response = await fetch(url, options);
      const endTime = performance.now();
      const duration = Math.round(endTime - startTime);
      
      let data;
      try {
        data = await response.json();
      } catch {
        data = await response.text();
      }
      
      const logData = {
        url,
        method,
        status: response.status,
        statusText: response.statusText,
        duration: `${duration}ms`,
        requestData: options?.body,
        responseData: data,
        headers: Object.fromEntries(response.headers.entries())
      };
      
      if (response.ok) {
        logApiCall(url, method, options?.body, data, null);
        debugLog(`API Call Success: ${description || method} ${url}`, logData);
      } else {
        const error = `HTTP ${response.status}: ${response.statusText}`;
        logApiCall(url, method, options?.body, data, error);
        debugLog(`API Call Error: ${description || method} ${url}`, logData);
      }
      
      return { response, data };
    } catch (error) {
      const endTime = performance.now();
      const duration = Math.round(endTime - startTime);
      
      const logData = {
        url,
        method,
        duration: `${duration}ms`,
        requestData: options?.body,
        error: error instanceof Error ? error.message : String(error)
      };
      
      logApiCall(url, method, options?.body, null, error);
      debugLog(`API Call Failed: ${description || method} ${url}`, logData);
      
      throw error;
    }
  }, [debugLog, logApiCall]);

  const debugUserAction = useCallback((action: string, data?: any) => {
    if (isDebugMode) {
      logUserAction(`${componentName}: ${action}`, data);
    }
  }, [componentName, logUserAction, isDebugMode]);

  const debugError = useCallback((error: Error | string, context?: any) => {
    const errorMessage = error instanceof Error ? error.message : error;
    const errorStack = error instanceof Error ? error.stack : undefined;
    
    console.error(`[${componentName}] Error:`, errorMessage, context);
    
    debugLog('Error', {
      message: errorMessage,
      stack: errorStack,
      context
    });
  }, [componentName, debugLog]);

  const debugWarning = useCallback((message: string, data?: any) => {
    console.warn(`[${componentName}] Warning:`, message, data);
    debugLog('Warning', { message, data });
  }, [componentName, debugLog]);

  const debugInfo = useCallback((message: string, data?: any) => {
    if (isDebugMode) {
      console.info(`[${componentName}] Info:`, message, data);
      debugLog('Info', { message, data });
    }
  }, [componentName, debugLog, isDebugMode]);

  const debugPerformance = useCallback((label: string, fn: () => any) => {
    if (!isDebugMode) return fn();
    
    const startTime = performance.now();
    console.time(`[${componentName}] ${label}`);
    
    try {
      const result = fn();
      const endTime = performance.now();
      const duration = Math.round(endTime - startTime);
      
      console.timeEnd(`[${componentName}] ${label}`);
      debugLog('Performance', { 
        operation: label, 
        duration: `${duration}ms`,
        result: typeof result
      });
      
      return result;
    } catch (error) {
      console.timeEnd(`[${componentName}] ${label}`);
      debugError(error as Error, { operation: label });
      throw error;
    }
  }, [componentName, debugLog, debugError, isDebugMode]);

  const debugState = useCallback((stateName: string, oldValue: any, newValue: any) => {
    if (isDebugMode) {
      console.log(`[${componentName}] State Change - ${stateName}:`, {
        from: oldValue,
        to: newValue
      });
      
      debugLog('State Change', {
        state: stateName,
        oldValue,
        newValue,
        changed: oldValue !== newValue
      });
    }
  }, [componentName, debugLog, isDebugMode]);

  const debugRender = useCallback((props?: any, reason?: string) => {
    if (isDebugMode) {
      console.log(`[${componentName}] Render${reason ? ` (${reason})` : ''}:`, props);
      debugLog('Render', { props, reason });
    }
  }, [componentName, debugLog, isDebugMode]);

  const createDebugTimer = useCallback((label: string) => {
    if (!isDebugMode) return { start: () => {}, end: () => {} };
    
    let startTime: number;
    
    return {
      start: () => {
        startTime = performance.now();
        console.time(`[${componentName}] ${label}`);
      },
      end: () => {
        const endTime = performance.now();
        const duration = Math.round(endTime - startTime);
        console.timeEnd(`[${componentName}] ${label}`);
        debugLog('Timer', { label, duration: `${duration}ms` });
        return duration;
      }
    };
  }, [componentName, debugLog, isDebugMode]);

  return {
    debugLog,
    debugApiCall,
    debugUserAction,
    debugError,
    debugWarning,
    debugInfo,
    debugPerformance,
    debugState,
    debugRender,
    createDebugTimer,
    isDebugMode
  };
};
