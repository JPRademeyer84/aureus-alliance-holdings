
import React, { createContext, useContext, useState, useEffect } from 'react';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';

interface Admin {
  id: string;
  username: string;
  role: 'super_admin' | 'admin' | 'chat_support';
  email?: string;
  full_name?: string;
  password_change_required?: boolean;
}

interface AdminContextType {
  admin: Admin | null;
  isLoading: boolean;
  loginError: string | null;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
  hasPermission: (requiredRole: 'super_admin' | 'admin' | 'chat_support') => boolean;
  changePassword: (currentPassword: string, newPassword: string) => Promise<boolean>;
}

const AdminContext = createContext<AdminContextType | undefined>(undefined);

export const AdminProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [admin, setAdmin] = useState<Admin | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [loginError, setLoginError] = useState<string | null>(null);
  const { toast } = useToast();

  // Debug logging moved to useEffect to avoid state updates during render
  useEffect(() => {
    console.log('AdminProvider state changed', {
      admin,
      isLoading,
      loginError,
      adminId: admin?.id,
      adminRole: admin?.role
    });
  }, [admin, isLoading, loginError]);

  useEffect(() => {
    // Check localStorage for existing admin session
    const storedAdmin = localStorage.getItem('admin');
    if (storedAdmin) {
      try {
        setAdmin(JSON.parse(storedAdmin));
      } catch (error) {
        console.error('Error parsing stored admin', error);
        localStorage.removeItem('admin');
      }
    }
    setIsLoading(false);
  }, []);

  const login = async (username: string, password: string): Promise<boolean> => {
    try {
      setIsLoading(true);
      setLoginError(null);

      // Call MySQL API for admin authentication
      const response = await fetch(ApiConfig.endpoints.admin.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include cookies for session management
        body: JSON.stringify({
          action: 'login',
          username,
          password
        })
      });

      // Check if response is ok and has content
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const responseText = await response.text();
      if (!responseText.trim()) {
        throw new Error('Empty response from server');
      }

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Failed to parse JSON response:', responseText);
        throw new Error('Invalid JSON response from server');
      }

      if (data.success) {
        const admin = data.data.admin;
        setAdmin(admin);
        localStorage.setItem('admin', JSON.stringify(admin));

        toast({
          title: 'Welcome, Admin',
          description: 'You have successfully logged in.',
        });

        return true;
      } else {
        setLoginError(data.error || 'Invalid credentials. Please try again.');
        toast({
          title: 'Login Failed',
          description: data.error || 'Invalid credentials. Please try again.',
          variant: 'destructive',
        });
        return false;
      }
    } catch (error) {
      console.error('Admin login error:', error);
      setLoginError('An unexpected error occurred. Please try again.');
      toast({
        title: 'Login Error',
        description: 'An unexpected error occurred. Please try again.',
        variant: 'destructive',
      });
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    try {
      // Call logout endpoint to clear PHP session
      await fetch(ApiConfig.endpoints.admin.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'logout'
        })
      });
    } catch (error) {
      console.error('Error during logout:', error);
    }

    setAdmin(null);
    localStorage.removeItem('admin');
    toast({
      title: 'Logged Out',
      description: 'You have been logged out successfully.',
    });
  };

  const changePassword = async (currentPassword: string, newPassword: string): Promise<boolean> => {
    try {
      setIsLoading(true);
      setLoginError(null);

      const response = await fetch(ApiConfig.endpoints.admin.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'change_password',
          current_password: currentPassword,
          new_password: newPassword
        })
      });

      const data = await response.json();

      if (data.success) {
        // Update admin state to remove password change requirement
        if (admin) {
          setAdmin({
            ...admin,
            password_change_required: false
          });
        }
        return true;
      } else {
        setLoginError(data.error || 'Password change failed');
        return false;
      }
    } catch (error) {
      console.error('Password change error:', error);
      setLoginError('An unexpected error occurred during password change');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const hasPermission = (requiredRole: 'super_admin' | 'admin' | 'chat_support'): boolean => {
    if (!admin) return false;

    const roleHierarchy = {
      'super_admin': 3,
      'admin': 2,
      'chat_support': 1
    };

    return (roleHierarchy[admin.role] ?? 0) >= (roleHierarchy[requiredRole] ?? 0);
  };

  return (
    <AdminContext.Provider value={{ admin, isLoading, loginError, login, logout, hasPermission, changePassword }}>
      {children}
    </AdminContext.Provider>
  );
};

export const useAdmin = (): AdminContextType => {
  const context = useContext(AdminContext);
  if (context === undefined) {
    throw new Error('useAdmin must be used within an AdminProvider');
  }
  return context;
};
