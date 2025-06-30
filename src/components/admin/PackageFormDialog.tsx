
import React, { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Save } from "lucide-react";
import { usePackageValidation } from "./usePackageValidation";
import PackageFormFields from "./PackageFormFields";
import PackageFormError from "./PackageFormError";

interface PackageFormDialogProps {
  open: boolean;
  onClose: () => void;
  onSave: (formData: any) => Promise<void>;
  isEditing: boolean;
  packageData: Partial<any>;
}

const PackageFormDialog: React.FC<PackageFormDialogProps> = ({
  open,
  onClose,
  onSave,
  isEditing,
  packageData,
}) => {
  const [formState, setFormState] = useState({ ...packageData });
  const [selectedBonuses, setSelectedBonuses] = useState<string[]>(packageData.bonuses || []);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const { validate } = usePackageValidation();

  useEffect(() => {
    setFormState({ ...packageData });
    setSelectedBonuses(packageData.bonuses || []);
    setErrorMessage(null);
  }, [packageData, open]);

  const doSave = async () => {
    const validationError = validate({
      name: formState.name,
      price: formState.price,
      shares: formState.shares,
      roi: formState.roi,
      annual_dividends: formState.annual_dividends,
      quarter_dividends: formState.quarter_dividends,
    });
    if (validationError) {
      setErrorMessage(validationError);
      return;
    }
    setErrorMessage(null);
    await onSave({ ...formState, bonuses: selectedBonuses });
  };

  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-xl bg-neutral-900 text-white">
        <DialogHeader>
          <DialogTitle className="text-xl font-bold text-gold">
            {isEditing ? "Edit Package" : "Add New Package"}
          </DialogTitle>
          <DialogDescription className="mb-2 text-neutral-300">
            {isEditing
              ? "Update the details of this investment package."
              : "Create a new investment package to offer to users."}
          </DialogDescription>
        </DialogHeader>
        <PackageFormFields
          formState={formState}
          setFormState={setFormState}
          selectedBonuses={selectedBonuses}
          setSelectedBonuses={setSelectedBonuses}
        />
        <PackageFormError errorMessage={errorMessage} />
        <DialogFooter>
          <Button variant="outline" onClick={onClose} className="border-gold text-gold">
            Cancel
          </Button>
          <Button onClick={doSave} className="bg-gold-gradient text-black">
            <Save className="h-4 w-4 mr-2" />
            {isEditing ? "Update" : "Create"} Package
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

export default PackageFormDialog;
