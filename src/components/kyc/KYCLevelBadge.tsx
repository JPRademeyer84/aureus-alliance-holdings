import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Shield, ShieldCheck, Star } from 'lucide-react';

interface KYCLevelBadgeProps {
  level: number;
  levelName?: string;
  size?: 'sm' | 'md' | 'lg';
  showText?: boolean;
  className?: string;
}

const KYCLevelBadge: React.FC<KYCLevelBadgeProps> = ({
  level,
  levelName,
  size = 'md',
  showText = true,
  className = ''
}) => {
  const getLevelConfig = (level: number) => {
    switch (level) {
      case 1:
        return {
          name: levelName || 'Basic',
          color: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
          icon: Shield,
          gradient: 'from-blue-500 to-blue-600'
        };
      case 2:
        return {
          name: levelName || 'Intermediate',
          color: 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
          icon: ShieldCheck,
          gradient: 'from-yellow-500 to-orange-500'
        };
      case 3:
        return {
          name: levelName || 'Advanced',
          color: 'bg-green-500/20 text-green-400 border-green-500/30',
          icon: Star,
          gradient: 'from-green-500 to-emerald-500'
        };
      default:
        return {
          name: 'Unknown',
          color: 'bg-gray-500/20 text-gray-400 border-gray-500/30',
          icon: Shield,
          gradient: 'from-gray-500 to-gray-600'
        };
    }
  };

  const getSizeClasses = (size: string) => {
    switch (size) {
      case 'sm':
        return {
          badge: 'text-xs px-2 py-1',
          icon: 'h-3 w-3',
          text: 'text-xs'
        };
      case 'lg':
        return {
          badge: 'text-base px-4 py-2',
          icon: 'h-5 w-5',
          text: 'text-base'
        };
      default: // md
        return {
          badge: 'text-sm px-3 py-1.5',
          icon: 'h-4 w-4',
          text: 'text-sm'
        };
    }
  };

  const config = getLevelConfig(level);
  const sizeClasses = getSizeClasses(size);
  const IconComponent = config.icon;

  return (
    <Badge 
      className={`
        ${config.color} 
        ${sizeClasses.badge} 
        border 
        flex items-center gap-1.5 
        font-medium 
        ${className}
      `}
    >
      <IconComponent className={sizeClasses.icon} />
      {showText && (
        <span className={sizeClasses.text}>
          Level {level} - {config.name}
        </span>
      )}
    </Badge>
  );
};

export default KYCLevelBadge;
