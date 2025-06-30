import React from 'react';
import * as LucideIcons from 'lucide-react';

interface SafeLucideIconProps {
  name: keyof typeof LucideIcons;
  className?: string | undefined | null;
  size?: number;
  [key: string]: any;
}

// Safe wrapper for Lucide React icons that handles className properly
const SafeLucideIcon: React.FC<SafeLucideIconProps> = ({ 
  name, 
  className, 
  size = 16, 
  ...props 
}) => {
  // Ensure className is always a string
  const safeClassName = typeof className === 'string' ? className : '';
  
  // Get the icon component
  const IconComponent = LucideIcons[name] as React.ComponentType<any>;
  
  if (!IconComponent) {
    // Fallback if icon doesn't exist
    return (
      <span 
        className={safeClassName}
        style={{ 
          fontSize: `${size}px`,
          lineHeight: 1,
          display: 'inline-block',
          verticalAlign: 'middle'
        }}
      >
        ❓
      </span>
    );
  }

  try {
    return (
      <IconComponent
        className={safeClassName}
        size={size}
        {...props}
      />
    );
  } catch (error) {
    console.warn(`SafeLucideIcon: Error rendering ${name}:`, error);
    // Fallback to emoji
    return (
      <span 
        className={safeClassName}
        style={{ 
          fontSize: `${size}px`,
          lineHeight: 1,
          display: 'inline-block',
          verticalAlign: 'middle'
        }}
      >
        ❓
      </span>
    );
  }
};

export default SafeLucideIcon;
