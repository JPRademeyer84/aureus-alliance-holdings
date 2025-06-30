
import React from "react";
import DatabasePlanCard from "./DatabasePlanCard";
import { useParticipationPackages } from "@/hooks/useInvestmentPackages";
import { Loader2 } from "lucide-react";
import { ST as T } from '@/components/SimpleTranslator';

const FeaturedPlans = () => {
  const { packages, isLoading, error } = useParticipationPackages();

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-gold" />
        <span className="ml-2 text-white">
          <T k="participation.loading" fallback="Loading participation plans..." />
        </span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center py-12">
        <div className="text-center">
          <div className="text-red-400 mb-2">❌ <T k="investment.error_connection" fallback="Database Connection Error" /></div>
          <div className="text-white text-sm">{error}</div>
          <div className="text-gray-400 text-xs mt-2">
            <T k="investment.error_check_api" fallback="Please check API connection and database setup" />
          </div>
        </div>
      </div>
    );
  }

  if (packages.length === 0) {
    return (
      <div className="flex justify-center items-center py-12">
        <div className="text-center">
          <div className="text-yellow-400 mb-2">⚠️ <T k="participation.no_packages_title" fallback="No Participation Packages Found" /></div>
          <div className="text-gray-400 text-sm">
            <T k="participation.no_packages_desc" fallback="No packages available in the database" />
          </div>
        </div>
      </div>
    );
  }

  // Get featured packages (first 3, or specific ones if we want to mark them as featured)
  const featuredPackages = packages.slice(0, 3);

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
      {featuredPackages.map((pkg, index) => (
        <DatabasePlanCard
          key={pkg.id}
          package={pkg}
          isPopular={pkg.name.toLowerCase() === "gold"}
          showInvestButton
        />
      ))}
    </div>
  );
};

export default FeaturedPlans;
