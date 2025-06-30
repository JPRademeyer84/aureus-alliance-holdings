
import React from "react";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select";
import { Star, Heart, Flag, Award, Trophy } from "@/components/SafeIcons";

// Safe icon components for shapes and other icons
const Diamond = ({ className }: { className?: string }) => <span className={className}>💎</span>;
const Gem = ({ className }: { className?: string }) => <span className={className}>💎</span>;
const Flower = ({ className }: { className?: string }) => <span className={className}>🌸</span>;
const Leaf = ({ className }: { className?: string }) => <span className={className}>🍃</span>;
const Sun = ({ className }: { className?: string }) => <span className={className}>☀️</span>;
const Moon = ({ className }: { className?: string }) => <span className={className}>🌙</span>;
const Cloud = ({ className }: { className?: string }) => <span className={className}>☁️</span>;
const Circle = ({ className }: { className?: string }) => <span className={className}>⭕</span>;
const Square = ({ className }: { className?: string }) => <span className={className}>⬜</span>;
const Triangle = ({ className }: { className?: string }) => <span className={className}>🔺</span>;
const Hexagon = ({ className }: { className?: string }) => <span className={className}>⬡</span>;
const Octagon = ({ className }: { className?: string }) => <span className={className}>⬢</span>;
const Palette = ({ className }: { className?: string }) => <span className={className}>🎨</span>;

const iconMap = {
  star: Star,
  diamond: Diamond,
  heart: Heart,
  flag: Flag,
  award: Award,
  trophy: Trophy,
  gem: Gem,
  flower: Flower,
  leaf: Leaf,
  sun: Sun,
  moon: Moon,
  cloud: Cloud,
  circle: Circle,
  square: Square,
  triangle: Triangle,
  hexagon: Hexagon,
  octagon: Octagon,
  palette: Palette,
};

const ICON_OPTIONS = Object.keys(iconMap);

interface IconPickerProps {
  value: string;
  onChange: (iconName: string) => void;
}

const IconPicker: React.FC<IconPickerProps> = ({ value, onChange }) => (
  <Select value={value} onValueChange={onChange}>
    <SelectTrigger className="bg-black/40 border-gold text-white">
      <SelectValue placeholder="Choose icon" />
    </SelectTrigger>
    <SelectContent className="bg-black/90 border-gold text-white max-h-64 overflow-auto z-[1001]">
      {ICON_OPTIONS.map((iconName) => {
        const Icon = iconMap[iconName as keyof typeof iconMap];
        return (
          <SelectItem value={iconName} key={iconName} className="flex items-center gap-2">
            <span className="inline-flex items-center gap-2">
              <Icon size={18} className="mr-2" />
              <span className="capitalize">{iconName.replace('-', ' ')}</span>
            </span>
          </SelectItem>
        );
      })}
    </SelectContent>
  </Select>
);

export default IconPicker;
