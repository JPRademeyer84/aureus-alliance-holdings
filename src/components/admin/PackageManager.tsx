
import React, { useState, useEffect } from "react";
import { useToast } from "@/hooks/use-toast";
import { Button } from "@/components/ui/button";
import { Plus, Trash2 } from "@/components/SafeIcons";

// Safe checkbox components
const CheckSquare = ({ className }: { className?: string }) => <span className={className}>☑️</span>;
const Square = ({ className }: { className?: string }) => <span className={className}>☐</span>;
import PackageFormDialog from "./PackageFormDialog";
import PackageTable from "./PackageTable";
import ApiConfig from "@/config/api";

export const BONUS_OPTIONS = [
  "Community Discord Access",
  "Guaranteed Common NFT Card",
  "Priority Support",
  "Exclusive Webinars",
  "Aureus Merchandise",
  "Early Product Access",
  "Monthly Q&A with Team",
];

interface ParticipationPackage {
  id: string;
  name: string;
  price: number;
  shares: number;
  commission_percentage: number;
  competition_allocation: number;
  npo_allocation: number;
  platform_allocation: number;
  mine_allocation: number;
  phase_id: number;
  is_active: boolean;
  icon?: string;
  icon_color?: string;
  bonuses: string[];
}

// Updated interface for new business model
interface InvestmentPackage extends ParticipationPackage {
  max_participants?: number;
}

const defaultPackage: Partial<InvestmentPackage> = {
  name: "",
  price: 0,
  shares: 0,
  commission_percentage: 20,
  competition_allocation: 15,
  npo_allocation: 10,
  platform_allocation: 25,
  mine_allocation: 35,
  phase_id: 1,
  is_active: false,
  icon: "star",
  icon_color: "bg-green-500",
  bonuses: [],
};

// Mock packages removed - using real database connection only

const PackageManager: React.FC = () => {
  const [packages, setPackages] = useState<InvestmentPackage[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [formOpen, setFormOpen] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [currentPackage, setCurrentPackage] = useState<Partial<InvestmentPackage>>(defaultPackage);
  const [selectedPackages, setSelectedPackages] = useState<Set<string>>(new Set());
  const { toast } = useToast();

  const fetchPackages = async () => {
    try {
      setIsLoading(true);

      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-packages.php');
      const data = await response.json();

      if (data.success) {
        // Check for duplicate IDs in the fetched data
        const ids = data.data.map((pkg: any) => pkg.id);
        const uniqueIds = new Set(ids);
        if (ids.length !== uniqueIds.size) {
          console.warn('⚠️ Duplicate package IDs detected in API response:', ids);
          // Remove duplicates by ID
          const uniquePackages = data.data.filter((pkg: any, index: number, arr: any[]) =>
            arr.findIndex(p => p.id === pkg.id) === index
          );
          console.log('🔧 Filtered to unique packages:', uniquePackages.length, 'of', data.data.length);
          setPackages(uniquePackages);
        } else {
          setPackages(data.data);
        }
      } else {
        throw new Error(data.error || 'Failed to fetch packages');
      }
    } catch (error) {
      console.error("Error fetching packages:", error);
      let errorMessage = "Failed to load investment packages";

      if (error instanceof Error && (error.message.includes('fetch') || error.message.includes('NetworkError'))) {
        errorMessage = "Cannot connect to API. Please check database connection and CORS configuration.";
      }

      toast({
        title: "Database Connection Error",
        description: errorMessage,
        variant: "destructive",
      });
      // DO NOT fallback to mock data - show the real error
      setPackages([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchPackages();
  }, []);

  const closeForm = () => {
    setFormOpen(false);
    setCurrentPackage(defaultPackage);
    setIsEditing(false);
  };

  const handleEditPackage = (pkg: InvestmentPackage) => {
    setCurrentPackage(pkg);
    setIsEditing(true);
    setFormOpen(true);
  };

  const handleSavePackage = async (pkgData: Partial<InvestmentPackage>) => {
    try {
      const packageData = {
        name: pkgData.name as string,
        price: Number(pkgData.price),
        shares: Number(pkgData.shares),
        commission_percentage: Number(pkgData.commission_percentage || 20),
        competition_allocation: Number(pkgData.competition_allocation || 15),
        npo_allocation: Number(pkgData.npo_allocation || 10),
        platform_allocation: Number(pkgData.platform_allocation || 25),
        mine_allocation: Number(pkgData.mine_allocation || 35),
        phase_id: Number(pkgData.phase_id || 1),
        is_active: Boolean(pkgData.is_active),
        max_participants: pkgData.max_participants ? Number(pkgData.max_participants) : null,
        icon: pkgData.icon || "star",
        icon_color: pkgData.icon_color || "bg-green-500",
        bonuses: pkgData.bonuses ? [...pkgData.bonuses] : [],
      };

      const url = 'http://localhost/Aureus%201%20-%20Complex/api/simple-packages.php';
      const method = isEditing && pkgData.id ? 'PUT' : 'POST';

      if (isEditing && pkgData.id) {
        packageData.id = pkgData.id;
      }

      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(packageData)
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: isEditing ? "Package updated successfully" : "Package added successfully"
        });
        closeForm();
        fetchPackages();
      } else {
        throw new Error(data.error || 'Failed to save package');
      }
    } catch (error) {
      console.error("Error saving package:", error);
      let errorMessage = "Failed to save package";

      if (error instanceof Error) {
        errorMessage = error.message;
      } else if (typeof error === 'string') {
        errorMessage = error;
      }

      // Check if it's a network error
      if (errorMessage.includes('fetch') || errorMessage.includes('NetworkError')) {
        errorMessage = "Cannot connect to API. Please run setup-database.bat to set up the API and database.";
      }

      toast({
        title: "Error",
        description: errorMessage,
        variant: "destructive",
      });
    }
  };

  const handleDeletePackage = async (id: string) => {
    if (!confirm("Are you sure you want to delete this package?")) return;

    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-packages.php', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id })
      });

      const data = await response.json();

      if (data.success) {
        toast({ title: "Success", description: "Package deleted successfully" });
        fetchPackages();
      } else {
        throw new Error(data.error || 'Failed to delete package');
      }
    } catch (error) {
      console.error("Error deleting package:", error);
      toast({
        title: "Error",
        description: "Failed to delete package",
        variant: "destructive",
      });
    }
  };

  const handleBulkDelete = async () => {
    if (selectedPackages.size === 0) {
      toast({
        title: "No Selection",
        description: "Please select packages to delete",
        variant: "destructive",
      });
      return;
    }

    if (!confirm(`Are you sure you want to delete ${selectedPackages.size} selected package(s)?`)) return;

    try {
      const deletePromises = Array.from(selectedPackages).map(id =>
        fetch('http://localhost/Aureus%201%20-%20Complex/api/simple-packages.php', {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ id })
        })
      );

      const responses = await Promise.all(deletePromises);
      const results = await Promise.all(responses.map(r => r.json()));

      const successful = results.filter(r => r.success).length;
      const failed = results.length - successful;

      if (successful > 0) {
        toast({
          title: "Success",
          description: `${successful} package(s) deleted successfully${failed > 0 ? `, ${failed} failed` : ''}`,
        });
        setSelectedPackages(new Set());
        fetchPackages();
      } else {
        throw new Error('All deletions failed');
      }
    } catch (error) {
      console.error("Error bulk deleting packages:", error);
      toast({
        title: "Error",
        description: "Failed to delete selected packages",
        variant: "destructive",
      });
    }
  };

  const handleSelectAll = () => {
    if (selectedPackages.size === packages.length) {
      setSelectedPackages(new Set());
    } else {
      setSelectedPackages(new Set(packages.map(pkg => pkg.id)));
    }
  };

  const handleSelectPackage = (id: string) => {
    const newSelected = new Set(selectedPackages);
    if (newSelected.has(id)) {
      newSelected.delete(id);
    } else {
      newSelected.add(id);
    }
    setSelectedPackages(newSelected);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-semibold text-white">Investment Packages</h2>
          <p className="text-gray-400 mt-1">Manage investment packages with revenue distribution and phase assignment</p>
        </div>
        <div className="flex gap-2">
          {selectedPackages.size > 0 && (
            <>
              <Button
                variant="outline"
                onClick={handleSelectAll}
                className="border-gray-600 text-gray-300 hover:bg-gray-800"
              >
                {selectedPackages.size === packages.length ? (
                  <CheckSquare className="h-4 w-4 mr-2" />
                ) : (
                  <Square className="h-4 w-4 mr-2" />
                )}
                {selectedPackages.size === packages.length ? 'Deselect All' : 'Select All'}
              </Button>
              <Button
                variant="destructive"
                onClick={handleBulkDelete}
                className="bg-red-600 hover:bg-red-700"
              >
                <Trash2 className="h-4 w-4 mr-2" />
                Delete Selected ({selectedPackages.size})
              </Button>
            </>
          )}
          <Button
            className="bg-green-600 hover:bg-green-700 text-white"
            onClick={() => {
              setIsEditing(false);
              setCurrentPackage(defaultPackage);
              setFormOpen(true);
            }}
          >
            <Plus className="h-4 w-4 mr-2" />
            Add Package
          </Button>
        </div>
      </div>

      <PackageFormDialog
        open={formOpen}
        onClose={closeForm}
        onSave={handleSavePackage}
        isEditing={isEditing}
        packageData={currentPackage}
      />

      <PackageTable
        packages={packages}
        isLoading={isLoading}
        onEdit={handleEditPackage}
        onDelete={handleDeletePackage}
        selectedPackages={selectedPackages}
        onSelectPackage={handleSelectPackage}
        onSelectAll={handleSelectAll}
      />
    </div>
  );
};

export default PackageManager;
