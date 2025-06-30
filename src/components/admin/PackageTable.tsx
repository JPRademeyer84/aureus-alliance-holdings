
import React from "react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Edit, Trash, Package } from "lucide-react";

interface PackageTableProps {
  packages: any[];
  isLoading: boolean;
  onEdit: (pkg: any) => void;
  onDelete: (id: string) => void;
  selectedPackages: Set<string>;
  onSelectPackage: (id: string) => void;
  onSelectAll: () => void;
}

const PackageTable: React.FC<PackageTableProps> = ({
  packages,
  isLoading,
  onEdit,
  onDelete,
  selectedPackages,
  onSelectPackage,
  onSelectAll,
}) => {
  if (isLoading) {
    return (
      <div className="flex justify-center p-8 bg-gray-800 rounded-lg border border-gray-700">
        <Package className="h-8 w-8 animate-spin text-blue-400" />
      </div>
    );
  }
  if (packages.length === 0) {
    return (
      <div className="text-center py-8 bg-gray-800 rounded-lg border border-gray-700">
        <Package className="h-12 w-12 mx-auto mb-4 text-gray-400" />
        <h3 className="text-xl font-medium mb-2 text-white">No Packages Yet</h3>
        <p className="text-gray-400 mb-4">
          Click the "Add Package" button to create your first investment package.
        </p>
      </div>
    );
  }
  return (
    <div className="border border-gray-700 rounded-lg overflow-hidden bg-gray-800">
      <Table>
        <TableHeader>
          <TableRow className="border-gray-700 hover:bg-gray-700">
            <TableHead className="text-gray-200 w-12">
              <Checkbox
                checked={packages.length > 0 && selectedPackages.size === packages.length}
                onCheckedChange={onSelectAll}
                className="border-gray-500"
              />
            </TableHead>
            <TableHead className="text-gray-200">Name</TableHead>
            <TableHead className="text-gray-200">Price</TableHead>
            <TableHead className="text-gray-200">Shares</TableHead>
            <TableHead className="text-gray-200">ROI</TableHead>
            <TableHead className="text-gray-200">Dividends (Q/A)</TableHead>
            <TableHead className="text-gray-200">Bonuses</TableHead>
            <TableHead className="text-gray-200">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {packages.map((pkg, index) => (
            <TableRow key={`${pkg.id}-${index}`} className="border-gray-700 hover:bg-gray-700">
              <TableCell>
                <Checkbox
                  checked={selectedPackages.has(pkg.id)}
                  onCheckedChange={() => onSelectPackage(pkg.id)}
                  className="border-gray-500"
                />
              </TableCell>
              <TableCell className="font-medium text-white">{pkg.name || 'N/A'}</TableCell>
              <TableCell className="text-gray-300">${(pkg.price || 0).toLocaleString()}</TableCell>
              <TableCell className="text-gray-300">{(pkg.shares || 0).toLocaleString()}</TableCell>
              <TableCell className="text-gray-300">${(pkg.roi || 0).toLocaleString()}</TableCell>
              <TableCell className="text-gray-300">
                ${(pkg.quarter_dividends || 0).toLocaleString()} / ${(pkg.annual_dividends || 0).toLocaleString()}
              </TableCell>
              <TableCell>
                <div className="flex flex-wrap gap-1">
                  {pkg.bonuses && pkg.bonuses.length > 0 ? (
                    pkg.bonuses.map((b: string, bonusIndex: number) => (
                      <span
                        key={`${pkg.id}-bonus-${bonusIndex}-${b}`}
                        className="px-2 py-0.5 text-xs bg-green-600 text-green-100 rounded-full"
                      >
                        {b}
                      </span>
                    ))
                  ) : (
                    <span className="text-xs text-gray-400">None</span>
                  )}
                </div>
              </TableCell>
              <TableCell>
                <div className="flex space-x-2">
                  <Button
                    variant="outline"
                    size="sm"
                    className="border-gray-600 text-gray-300 hover:bg-gray-600 hover:text-white"
                    onClick={() => onEdit(pkg)}
                  >
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    className="border-gray-600 text-red-400 hover:bg-red-900/20 hover:text-red-300"
                    onClick={() => onDelete(pkg.id)}
                  >
                    <Trash className="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
};

export default PackageTable;
