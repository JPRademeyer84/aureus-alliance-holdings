
import { useState, useEffect } from 'react';
import ApiConfig from '@/config/api';

export interface ParticipationPackage {
  id: string;
  name: string;
  price: number;
  shares: number;
  reward?: number; // New field
  roi?: number; // Legacy field for backward compatibility
  annual_dividends: number;
  quarter_dividends: number;
  icon?: string;
  icon_color?: string;
  bonuses: string[];
}

// Legacy interface for backward compatibility
export interface InvestmentPackage extends ParticipationPackage {
  roi: number; // Legacy field mapping to reward
}

// Note: This hook fetches real participation packages from the database API
// No mock data is used - all packages come from /api/packages/index.php

export const useParticipationPackages = () => {
  const [packages, setPackages] = useState<ParticipationPackage[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedPackageIndex, setSelectedPackageIndex] = useState(0);

  useEffect(() => {
    const fetchPackages = async () => {
      try {
        setIsLoading(true);
        setError(null);
        console.log('Fetching investment packages...');

        // Call MySQL API to fetch packages
        const response = await fetch(ApiConfig.endpoints.packages.index);
        const data = await response.json();

        if (data.success) {
          console.log('Fetched packages from API:', data.data);
          setPackages(data.data);

          // Set default to the gold package if it exists
          const defaultIndex = data.data.findIndex(
            pkg => pkg.name.toLowerCase() === 'gold'
          );
          const finalIndex = defaultIndex >= 0 ? defaultIndex : 0;
          console.log('Setting default package index to:', finalIndex);
          setSelectedPackageIndex(finalIndex);
        } else {
          throw new Error(data.error || 'Failed to fetch packages');
        }
      } catch (error) {
        console.error("Error fetching participation packages:", error);
        const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
        setError(errorMessage);
        setPackages([]);
        setSelectedPackageIndex(0);
      } finally {
        setIsLoading(false);
      }
    };

    fetchPackages();
  }, []);

  return {
    packages,
    isLoading,
    error,
    selectedPackageIndex,
    setSelectedPackageIndex
  };
};

// Legacy export for backward compatibility
export const useInvestmentPackages = useParticipationPackages;
