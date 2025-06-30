import React from "react";
import { Input } from "@/components/ui/input";
import PackageBonusSelector from "./PackageBonusSelector";
import IconPicker from "./IconPicker";

interface Props {
  formState: any;
  setFormState: React.Dispatch<React.SetStateAction<any>>;
  selectedBonuses: string[];
  setSelectedBonuses: (val: string[]) => void;
}

const colorToHex = (color: string) => {
  // Try to parse bg-... tailwind classes or #hex, fallback defaults
  if (!color) return "#22c55e";
  if (color.startsWith("#")) return color;
  // Handle tailwind bg colors (e.g. bg-green-500, bg-red-500)
  const twHex: Record<string, string> = {
    "bg-green-500": "#22c55e",
    "bg-red-500": "#ef4444",
    "bg-blue-500": "#3b82f6",
    "bg-yellow-500": "#eab308",
    "bg-pink-500": "#ec4899",
    "bg-purple-500": "#a21caf",
    "bg-indigo-500": "#6366f1",
    "bg-gray-500": "#6b7280",
    "bg-gold": "#FFD700",
  };
  return twHex[color] ?? "#22c55e";
};

const hexToTailwind = (hex: string) => {
  // Return the closest match from our twHex map
  const twHex: Record<string, string> = {
    "#22c55e": "bg-green-500",
    "#ef4444": "bg-red-500",
    "#3b82f6": "bg-blue-500",
    "#eab308": "bg-yellow-500",
    "#ec4899": "bg-pink-500",
    "#a21caf": "bg-purple-500",
    "#6366f1": "bg-indigo-500",
    "#6b7280": "bg-gray-500",
    "#FFD700": "bg-gold",
  };
  return twHex[hex] ?? hex;
};

const PackageFormFields: React.FC<Props> = ({
  formState,
  setFormState,
  selectedBonuses,
  setSelectedBonuses,
}) => {
  const handleInput = (key: string, val: string) => {
    setFormState((state: any) => ({ ...state, [key]: val }));
  };

  const handleNumberInput = (key: string, val: string) => {
    setFormState((state: any) => ({
      ...state,
      [key]: val === "" ? "" : Number(val),
    }));
  };

  return (
    <div className="grid gap-4 py-4">
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <label htmlFor="name" className="text-sm font-medium text-gold">Name</label>
          <Input
            id="name"
            placeholder="e.g., Gold"
            value={formState.name || ""}
            onChange={(e) => handleInput("name", e.target.value)}
            className="bg-black/40 border-gold text-white"
          />
        </div>
        <div className="space-y-2">
          <label htmlFor="price" className="text-sm font-medium text-gold">Price ($)</label>
          <Input
            id="price"
            type="number"
            placeholder="e.g., 500"
            value={formState.price || ""}
            onChange={(e) => handleNumberInput("price", e.target.value)}
            className="bg-black/40 border-gold text-white"
          />
        </div>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <label htmlFor="shares" className="text-sm font-medium text-gold">Shares</label>
          <Input
            id="shares"
            type="number"
            placeholder="e.g., 80"
            value={formState.shares || ""}
            onChange={(e) => handleNumberInput("shares", e.target.value)}
            className="bg-black/40 border-gold text-white"
          />
        </div>
        <div className="space-y-2">
          <label htmlFor="commission_percentage" className="text-sm font-medium text-gold">Commission Rate (%)</label>
          <Input
            id="commission_percentage"
            type="number"
            placeholder="20"
            value={formState.commission_percentage || ""}
            onChange={(e) => handleNumberInput("commission_percentage", e.target.value)}
            className="bg-black/40 border-gold text-white"
          />
        </div>
      </div>

      {/* Revenue Distribution Section */}
      <div className="space-y-4">
        <h3 className="text-lg font-medium text-gold border-b border-gold/30 pb-2">Revenue Distribution (%)</h3>
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <label htmlFor="competition_allocation" className="text-sm font-medium text-green-400">Competition Pool</label>
            <Input
              id="competition_allocation"
              type="number"
              placeholder="15"
              value={formState.competition_allocation || ""}
              onChange={(e) => handleNumberInput("competition_allocation", e.target.value)}
              className="bg-black/40 border-green-400 text-white"
            />
          </div>
          <div className="space-y-2">
            <label htmlFor="npo_allocation" className="text-sm font-medium text-purple-400">NPO Fund</label>
            <Input
              id="npo_allocation"
              type="number"
              placeholder="10"
              value={formState.npo_allocation || ""}
              onChange={(e) => handleNumberInput("npo_allocation", e.target.value)}
              className="bg-black/40 border-purple-400 text-white"
            />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <label htmlFor="platform_allocation" className="text-sm font-medium text-blue-400">Platform & Tech</label>
            <Input
              id="platform_allocation"
              type="number"
              placeholder="25"
              value={formState.platform_allocation || ""}
              onChange={(e) => handleNumberInput("platform_allocation", e.target.value)}
              className="bg-black/40 border-blue-400 text-white"
            />
          </div>
          <div className="space-y-2">
            <label htmlFor="mine_allocation" className="text-sm font-medium text-orange-400">Mine Setup</label>
            <Input
              id="mine_allocation"
              type="number"
              placeholder="35"
              value={formState.mine_allocation || ""}
              onChange={(e) => handleNumberInput("mine_allocation", e.target.value)}
              className="bg-black/40 border-orange-400 text-white"
            />
          </div>
        </div>
      </div>

      {/* Phase and Activation Section */}
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <label htmlFor="phase_id" className="text-sm font-medium text-gold">Phase</label>
          <select
            id="phase_id"
            value={formState.phase_id || 1}
            onChange={(e) => handleNumberInput("phase_id", e.target.value)}
            className="bg-black/40 border-gold text-white rounded px-3 py-2 w-full"
          >
            {Array.from({length: 20}, (_, i) => i + 1).map(phase => (
              <option key={phase} value={phase}>Phase {phase}</option>
            ))}
          </select>
        </div>
        <div className="space-y-2">
          <label htmlFor="is_active" className="text-sm font-medium text-gold">Status</label>
          <select
            id="is_active"
            value={formState.is_active ? "true" : "false"}
            onChange={(e) => setFormState((state: any) => ({ ...state, is_active: e.target.value === "true" }))}
            className="bg-black/40 border-gold text-white rounded px-3 py-2 w-full"
          >
            <option value="false">Inactive</option>
            <option value="true">Active</option>
          </select>
        </div>
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <label htmlFor="icon" className="text-sm font-medium text-gold">Icon</label>
          <IconPicker
            value={formState.icon || ""}
            onChange={(iconVal) => handleInput("icon", iconVal)}
          />
        </div>
        <div className="space-y-2">
          <label htmlFor="icon_color" className="text-sm font-medium text-gold">Icon Color</label>
          <div className="flex items-center gap-3">
            <input
              type="color"
              id="icon_color"
              value={colorToHex(formState.icon_color)}
              onChange={e => {
                handleInput("icon_color", hexToTailwind(e.target.value));
              }}
              style={{ width: 36, height: 36, borderRadius: 8, border: "2px solid #FFD700", background: "#111" }}
            />
            <span className="text-base font-mono text-white bg-black/30 px-2 py-0.5 rounded border border-gold/30">{colorToHex(formState.icon_color)}</span>
          </div>
        </div>
      </div>
      <PackageBonusSelector
        selectedBonuses={selectedBonuses}
        onChange={setSelectedBonuses}
      />
    </div>
  );
};

export default PackageFormFields;
