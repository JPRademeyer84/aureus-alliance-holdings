
import React from 'react';
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from "@/components/ui/select";

export const BLOCKCHAINS = [
  { label: "Ethereum", value: "Ethereum" },
  { label: "Bitcoin", value: "Bitcoin" },
  { label: "BNB Chain", value: "BNB Chain" },
  { label: "Polygon", value: "Polygon" }, // Added Polygon
  { label: "Tron", value: "Tron" },
];

interface Props {
  value: string;
  onChange: (chain: string) => void;
}

const ChainDropdown: React.FC<Props> = ({ value, onChange }) => (
  <Select value={value} onValueChange={onChange}>
    <SelectTrigger>
      <SelectValue placeholder="Select blockchain (required)" />
    </SelectTrigger>
    <SelectContent>
      {BLOCKCHAINS.map((chain) => (
        <SelectItem key={chain.value} value={chain.value}>
          {chain.label}
        </SelectItem>
      ))}
    </SelectContent>
  </Select>
);

export default ChainDropdown;
