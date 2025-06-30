import React, { createContext, useContext, useState, useEffect } from 'react';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';

interface User {
  id: string;
  username: string;
  email: string;
  full_name?: string;
  whatsapp_number?: string;
  telegram_username?: string;
  twitter_handle?: string;
  instagram_handle?: string;
  linkedin_profile?: string;
  created_at: string;
  updated_at?: string;
}

interface UserContextType {
  user: User | null;
  isLoading: boolean;
  loginError: string | null;
  registerError: string | null;
  login: (email: string, password: string) => Promise<boolean>;
  register: (username: string, email: string, password: string) => Promise<boolean>;
  logout: () => void;
  updateUser: (userData: Partial<User>) => void;
  refreshUser: () => Promise<void>;
  isAuthenticated: boolean;
}

const UserContext = createContext<UserContextType | undefined>(undefined);

export const UserProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [loginError, setLoginError] = useState<string | null>(null);
  const [registerError, setRegisterError] = useState<string | null>(null);
  const { toast } = useToast();

  // Check for existing user session on mount
  useEffect(() => {
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
      try {
        setUser(JSON.parse(savedUser));
      } catch (error) {
        console.error('Error parsing saved user:', error);
        localStorage.removeItem('user');
      }
    }
  }, []);

  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      setIsLoading(true);
      setLoginError(null);

      const response = await fetch(ApiConfig.endpoints.users.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include cookies for session management
        body: JSON.stringify({
          action: 'login',
          email,
          password
        })
      });

      const data = await response.json();

      if (data.success) {
        const userData = data.data.user;
        setUser(userData);
        localStorage.setItem('user', JSON.stringify(userData));

        toast({
          title: 'Welcome back!',
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
      console.error('User login error:', error);
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

  const register = async (username: string, email: string, password: string): Promise<boolean> => {
    try {
      setIsLoading(true);
      setRegisterError(null);

      const response = await fetch(ApiConfig.endpoints.users.auth, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include cookies for session management
        body: JSON.stringify({
          action: 'register',
          username,
          email,
          password
        })
      });

      const data = await response.json();

      if (data.success) {
        const userData = data.data.user;
        setUser(userData);
        localStorage.setItem('user', JSON.stringify(userData));

        toast({
          title: 'Welcome to Aureus Alliance!',
          description: 'Your account has been created successfully.',
        });

        return true;
      } else {
        setRegisterError(data.error || 'Registration failed. Please try again.');
        toast({
          title: 'Registration Failed',
          description: data.error || 'Registration failed. Please try again.',
          variant: 'destructive',
        });
        return false;
      }
    } catch (error) {
      console.error('User registration error:', error);
      setRegisterError('An unexpected error occurred. Please try again.');
      toast({
        title: 'Registration Error',
        description: 'An unexpected error occurred. Please try again.',
        variant: 'destructive',
      });
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem('user');
    toast({
      title: 'Logged out',
      description: 'You have been successfully logged out.',
    });
  };

  const updateUser = (userData: Partial<User>) => {
    if (user) {
      const updatedUser = { ...user, ...userData };
      setUser(updatedUser);
      localStorage.setItem('user', JSON.stringify(updatedUser));
    }
  };

  const refreshUser = async () => {
    if (!user?.id) return;

    try {
      // Use a simple endpoint to get current user data
      const response = await fetch('http://localhost/aureus-angel-alliance/api/debug/get-current-user.php', {
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data && data.data.user) {
          setUser(data.data.user);
          localStorage.setItem('user', JSON.stringify(data.data.user));
        }
      }
    } catch (error) {
      console.error('Failed to refresh user data:', error);
    }
  };

  const value: UserContextType = {
    user,
    isLoading,
    loginError,
    registerError,
    login,
    register,
    logout,
    updateUser,
    refreshUser,
    isAuthenticated: !!user,
  };

  // Debug logging for authentication state changes
  React.useEffect(() => {
    console.log('UserContext state changed:', {
      user: !!user,
      userId: user?.id,
      isAuthenticated: !!user,
      isLoading
    });
  }, [user, isLoading]);

  return (
    <UserContext.Provider value={value}>
      {children}
    </UserContext.Provider>
  );
};

export const useUser = (): UserContextType => {
  const context = useContext(UserContext);
  if (context === undefined) {
    throw new Error('useUser must be used within a UserProvider');
  }
  return context;
};
