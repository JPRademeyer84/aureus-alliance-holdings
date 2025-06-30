
import React, { useState } from "react";
import { BONUS_OPTIONS } from "./PackageManager";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Plus, X } from "lucide-react";

interface PackageBonusSelectorProps {
  selectedBonuses: string[];
  onChange: (bonuses: string[]) => void;
}

const PackageBonusSelector: React.FC<PackageBonusSelectorProps> = ({
  selectedBonuses,
  onChange,
}) => {
  const [customBonus, setCustomBonus] = useState("");

  const handleBonusSelect = (bonus: string) => {
    if (selectedBonuses.includes(bonus)) {
      onChange(selectedBonuses.filter((b) => b !== bonus));
    } else {
      onChange([...selectedBonuses, bonus]);
    }
  };

  const handleAddCustomBonus = () => {
    const trimmed = customBonus.trim();
    if (trimmed && !selectedBonuses.includes(trimmed)) {
      onChange([...selectedBonuses, trimmed]);
      setCustomBonus("");
    }
  };

  const handleRemoveBonus = (bonus: string) => {
    onChange(selectedBonuses.filter((b) => b !== bonus));
  };

  return (
    <div className="space-y-2">
      <label className="text-sm font-medium text-neutral-200">Bonuses (select one or more)</label>
      
      <div className="flex items-center gap-2">
        <Input
          className="flex-1 bg-black/20 border-gray-700 text-white placeholder:text-gray-500"
          placeholder="Add custom bonus"
          value={customBonus}
          onChange={(e) => setCustomBonus(e.target.value)}
          onKeyDown={e => {
            if (e.key === "Enter") {
              e.preventDefault(); 
              handleAddCustomBonus();
            }
          }}
        />
        <Button
          type="button"
          onClick={handleAddCustomBonus}
          className="bg-green-600 hover:bg-green-700 text-white"
          size="sm"
        >
          <Plus className="h-4 w-4 mr-1" />
          Add
        </Button>
      </div>
      
      <div className="space-y-2">
        <p className="text-xs text-neutral-400">Available Bonuses:</p>
        <div className="flex flex-wrap gap-2">
          {BONUS_OPTIONS.map((bonus) => (
            <button
              key={bonus}
              type="button"
              className={`px-3 py-1 rounded-full text-xs ${
                selectedBonuses.includes(bonus)
                  ? "bg-green-600 text-white"
                  : "bg-black/30 text-gray-300 hover:bg-black/50 border border-gray-700"
              } transition-all`}
              onClick={() => handleBonusSelect(bonus)}
            >
              {bonus}
              {selectedBonuses.includes(bonus) && <span className="ml-1">✓</span>}
            </button>
          ))}
        </div>
      </div>
      
      {selectedBonuses.length > 0 && (
        <div>
          <p className="text-xs text-neutral-400 mb-2">Selected Bonuses:</p>
          <div className="border rounded-md p-3 bg-black/20 min-h-[44px] flex flex-wrap gap-2">
            {selectedBonuses.map((bonus) => (
              <div 
                key={bonus} 
                className="flex items-center gap-1 px-2 py-1 rounded-full bg-green-600/20 border border-green-600/30 text-white"
              >
                <span className="text-xs">{bonus}</span>
                <button
                  type="button"
                  onClick={() => handleRemoveBonus(bonus)}
                  className="text-red-400 hover:text-red-300 ml-1"
                >
                  <X className="h-3 w-3" />
                </button>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default PackageBonusSelector;
