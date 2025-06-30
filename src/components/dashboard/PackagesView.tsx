import React, { useState } from 'react';
import { useInvestmentPackages } from '@/hooks/useInvestmentPackages';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import DatabasePurchasablePlanCard from '@/components/investment/DatabasePurchasablePlanCard';
import MultiPackageSelector from './MultiPackageSelector';
import MultiPackagePurchaseDialog from './MultiPackagePurchaseDialog';
import PurchaseDialog from './PurchaseDialog';
import {
  Package,
  DollarSign,
  TrendingUp,
  Star,
  Search,
  Filter,
  Loader2,
  Plus,
  Target,
  Calendar,
  ShoppingCart,
  Grid3X3
} from 'lucide-react';

const PackagesView: React.FC = () => {
  const { packages, isLoading } = useInvestmentPackages();
  const { translate } = useTranslation();
  const [searchTerm, setSearchTerm] = useState('');
  const [sortBy, setSortBy] = useState('name');
  const [filterBy, setFilterBy] = useState('all');
  const [purchaseDialogOpen, setPurchaseDialogOpen] = useState(false);
  const [selectedPackageForPurchase, setSelectedPackageForPurchase] = useState<any>(null);
  const [viewMode, setViewMode] = useState<'grid' | 'multi'>('multi'); // Default to multi-select mode
  const [multiPurchaseDialogOpen, setMultiPurchaseDialogOpen] = useState(false);
  const [selectedPackagesForPurchase, setSelectedPackagesForPurchase] = useState<any[]>([]);
  const [multiPurchaseTotalAmount, setMultiPurchaseTotalAmount] = useState(0);

  const handlePurchase = (pkg: any) => {
    setSelectedPackageForPurchase(pkg);
    setPurchaseDialogOpen(true);
  };

  const handleClosePurchaseDialog = () => {
    setPurchaseDialogOpen(false);
    setSelectedPackageForPurchase(null);
  };

  const handleMultiPackagePurchase = (selections: any[], totalAmount: number) => {
    console.log('Multi-package purchase:', selections, totalAmount);
    setSelectedPackagesForPurchase(selections);
    setMultiPurchaseTotalAmount(totalAmount);
    setMultiPurchaseDialogOpen(true);
  };

  const handleCloseMultiPurchaseDialog = () => {
    setMultiPurchaseDialogOpen(false);
    setSelectedPackagesForPurchase([]);
    setMultiPurchaseTotalAmount(0);
  };

  // Filter and sort packages
  const filteredPackages = packages
    .filter(pkg => {
      const matchesSearch = pkg.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                           pkg.description?.toLowerCase().includes(searchTerm.toLowerCase());
      
      if (filterBy === 'all') return matchesSearch;
      if (filterBy === 'low') return matchesSearch && pkg.price <= 100;
      if (filterBy === 'medium') return matchesSearch && pkg.price > 100 && pkg.price <= 500;
      if (filterBy === 'high') return matchesSearch && pkg.price > 500;
      
      return matchesSearch;
    })
    .sort((a, b) => {
      switch (sortBy) {
        case 'price_low':
          return a.price - b.price;
        case 'price_high':
          return b.price - a.price;
        case 'roi':
          return b.roi - a.roi;
        case 'shares':
          return b.shares - a.shares;
        default:
          return a.name.localeCompare(b.name);
      }
    });

  // Calculate statistics
  const stats = {
    total: packages.length,
    avgPrice: packages.length > 0 ? packages.reduce((sum, pkg) => sum + pkg.price, 0) / packages.length : 0,
    maxROI: packages.length > 0 ? Math.max(...packages.map(pkg => pkg.roi)) : 0,
    totalShares: packages.reduce((sum, pkg) => sum + pkg.shares, 0)
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-bold text-white">
            <T k="investment_packages" fallback="Investment Packages" />
          </h2>
        </div>
        <div className="text-center py-8">
          <Loader2 className="h-8 w-8 animate-spin text-gold mx-auto" />
          <p className="text-gray-400 mt-2">
            <T k="loading_investment_packages" fallback="Loading investment packages..." />
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">
            <T k="investment_packages" fallback="Investment Packages" />
          </h2>
          <p className="text-gray-400">
            {viewMode === 'multi'
              ? translate('select_multiple_packages_match_amount', 'Select multiple packages to match your investment amount')
              : translate('choose_individual_packages', 'Choose individual packages to invest in')}
          </p>
        </div>
        <div className="flex items-center space-x-3">
          <div className="flex items-center bg-gray-800 rounded-lg p-1">
            <Button
              variant={viewMode === 'multi' ? 'default' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('multi')}
              className={viewMode === 'multi' ? 'bg-gold text-black' : 'text-gray-400 hover:text-white'}
            >
              <ShoppingCart className="h-4 w-4 mr-2" />
              <T k="multi_select" fallback="Multi-Select" />
            </Button>
            <Button
              variant={viewMode === 'grid' ? 'default' : 'ghost'}
              size="sm"
              onClick={() => setViewMode('grid')}
              className={viewMode === 'grid' ? 'bg-gold text-black' : 'text-gray-400 hover:text-white'}
            >
              <Grid3X3 className="h-4 w-4 mr-2" />
              <T k="individual" fallback="Individual" />
            </Button>
          </div>
          <Button variant="outline" className="border-gold/30 text-gold hover:bg-gold/10">
            <Plus className="h-4 w-4 mr-2" />
            <T k="request_custom_package" fallback="Request Custom Package" />
          </Button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center">
              <Package className="h-8 w-8 text-blue-400" />
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-400">
                  <T k="available_packages" fallback="Available Packages" />
                </p>
                <p className="text-2xl font-bold text-white">{stats.total}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center">
              <DollarSign className="h-8 w-8 text-green-400" />
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-400">
                  <T k="average_price" fallback="Average Price" />
                </p>
                <p className="text-2xl font-bold text-white">${stats.avgPrice.toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center">
              <TrendingUp className="h-8 w-8 text-purple-400" />
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-400">
                  <T k="max_roi" fallback="Max ROI" />
                </p>
                <p className="text-2xl font-bold text-white">${stats.maxROI.toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center">
              <Star className="h-8 w-8 text-yellow-400" />
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-400">
                  <T k="total_shares" fallback="Total Shares" />
                </p>
                <p className="text-2xl font-bold text-white">{stats.totalShares.toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Multi-Package Selector or Individual Grid */}
      {viewMode === 'multi' ? (
        <MultiPackageSelector onPurchase={handleMultiPackagePurchase} />
      ) : (
        <>
          {/* Filters and Search */}
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex flex-wrap gap-4">
                <div className="flex items-center space-x-2">
                  <Search className="h-4 w-4 text-gray-400" />
                  <Input
                    placeholder={translate('search_packages', 'Search packages...')}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="w-64 bg-gray-700 border-gray-600 text-white"
                  />
                </div>

                <div className="flex items-center space-x-2">
                  <Filter className="h-4 w-4 text-gray-400" />
                  <Select value={filterBy} onValueChange={setFilterBy}>
                    <SelectTrigger className="w-40 bg-gray-700 border-gray-600 text-white">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-gray-700 border-gray-600">
                      <SelectItem value="all">
                        <T k="all_packages" fallback="All Packages" />
                      </SelectItem>
                      <SelectItem value="low">
                        <T k="under_100" fallback="Under $100" />
                      </SelectItem>
                      <SelectItem value="medium">
                        <T k="between_100_500" fallback="$100 - $500" />
                      </SelectItem>
                      <SelectItem value="high">
                        <T k="over_500" fallback="Over $500" />
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="flex items-center space-x-2">
                  <span className="text-sm text-gray-400">
                    <T k="sort_by" fallback="Sort by:" />
                  </span>
                  <Select value={sortBy} onValueChange={setSortBy}>
                    <SelectTrigger className="w-40 bg-gray-700 border-gray-600 text-white">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-gray-700 border-gray-600">
                      <SelectItem value="name">
                        <T k="name" fallback="Name" />
                      </SelectItem>
                      <SelectItem value="price_low">
                        <T k="price_low_to_high" fallback="Price (Low to High)" />
                      </SelectItem>
                      <SelectItem value="price_high">
                        <T k="price_high_to_low" fallback="Price (High to Low)" />
                      </SelectItem>
                      <SelectItem value="roi">
                        <T k="roi_highest_first" fallback="ROI (Highest First)" />
                      </SelectItem>
                      <SelectItem value="shares">
                        <T k="shares_most_first" fallback="Shares (Most First)" />
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Packages Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {filteredPackages.map((pkg) => (
              <DatabasePurchasablePlanCard
                key={pkg.id}
                package={pkg}
                isExpanded={false}
                onPurchase={() => handlePurchase(pkg)}
              />
            ))}
          </div>

          {filteredPackages.length === 0 && (
            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-8">
                <div className="text-center">
                  <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-white mb-2">
                    <T k="no_packages_found" fallback="No packages found" />
                  </h3>
                  <p className="text-gray-400 mb-4">
                    {searchTerm || filterBy !== 'all'
                      ? translate('try_adjusting_search', 'Try adjusting your search or filter criteria.')
                      : translate('no_packages_currently_available', 'No investment packages are currently available.')}
                  </p>
                  {(searchTerm || filterBy !== 'all') && (
                    <Button
                      onClick={() => {
                        setSearchTerm('');
                        setFilterBy('all');
                      }}
                      variant="outline"
                      className="border-gold/30 text-gold hover:bg-gold/10"
                    >
                      <T k="clear_filters" fallback="Clear Filters" />
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>
          )}
        </>
      )}

      {/* Purchase Dialog */}
      {selectedPackageForPurchase && (
        <PurchaseDialog
          isOpen={purchaseDialogOpen}
          onClose={handleClosePurchaseDialog}
          package={selectedPackageForPurchase}
        />
      )}

      {/* Multi-Package Purchase Dialog */}
      <MultiPackagePurchaseDialog
        isOpen={multiPurchaseDialogOpen}
        onClose={handleCloseMultiPurchaseDialog}
        selections={selectedPackagesForPurchase}
        totalAmount={multiPurchaseTotalAmount}
      />
    </div>
  );
};

export default PackagesView;
