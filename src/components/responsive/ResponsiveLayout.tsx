import React, { ReactNode, useEffect } from 'react';
import { cn } from '@/lib/utils';
import { useMobileOptimization } from '@/hooks/useMobileOptimization';

interface ResponsiveLayoutProps {
  children: ReactNode;
  className?: string;
  enableSafeArea?: boolean;
  enableKeyboardAdjustment?: boolean;
  mobileFirst?: boolean;
}

interface ResponsiveContainerProps {
  children: ReactNode;
  className?: string;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full';
  padding?: 'none' | 'sm' | 'md' | 'lg';
  center?: boolean;
}

interface ResponsiveGridProps {
  children: ReactNode;
  className?: string;
  cols?: {
    mobile?: number;
    tablet?: number;
    desktop?: number;
  };
  gap?: 'sm' | 'md' | 'lg';
}

interface ResponsiveStackProps {
  children: ReactNode;
  className?: string;
  direction?: {
    mobile?: 'row' | 'column';
    tablet?: 'row' | 'column';
    desktop?: 'row' | 'column';
  };
  spacing?: 'sm' | 'md' | 'lg';
  align?: 'start' | 'center' | 'end' | 'stretch';
  justify?: 'start' | 'center' | 'end' | 'between' | 'around' | 'evenly';
}

export const ResponsiveLayout: React.FC<ResponsiveLayoutProps> = ({
  children,
  className,
  enableSafeArea = true,
  enableKeyboardAdjustment = true,
  mobileFirst = true
}) => {
  const { 
    deviceInfo, 
    isKeyboardOpen, 
    safeAreaInsets,
    isMobile,
    isTablet,
    isDesktop
  } = useMobileOptimization();

  useEffect(() => {
    // Apply CSS custom properties for safe area and keyboard
    const root = document.documentElement;
    
    if (enableSafeArea) {
      root.style.setProperty('--safe-area-top', `${safeAreaInsets.top}px`);
      root.style.setProperty('--safe-area-right', `${safeAreaInsets.right}px`);
      root.style.setProperty('--safe-area-bottom', `${safeAreaInsets.bottom}px`);
      root.style.setProperty('--safe-area-left', `${safeAreaInsets.left}px`);
    }
    
    if (enableKeyboardAdjustment) {
      root.style.setProperty('--keyboard-height', isKeyboardOpen ? '300px' : '0px');
    }
  }, [safeAreaInsets, isKeyboardOpen, enableSafeArea, enableKeyboardAdjustment]);

  const layoutClasses = cn(
    'responsive-layout',
    {
      'mobile-first': mobileFirst,
      'safe-area-enabled': enableSafeArea,
      'keyboard-adjustment-enabled': enableKeyboardAdjustment,
      'is-mobile': isMobile,
      'is-tablet': isTablet,
      'is-desktop': isDesktop,
      'has-touch': deviceInfo.touchSupport,
      'keyboard-open': isKeyboardOpen
    },
    className
  );

  const layoutStyles: React.CSSProperties = {
    ...(enableSafeArea && {
      paddingTop: `max(env(safe-area-inset-top), ${safeAreaInsets.top}px)`,
      paddingRight: `max(env(safe-area-inset-right), ${safeAreaInsets.right}px)`,
      paddingBottom: `max(env(safe-area-inset-bottom), ${safeAreaInsets.bottom}px)`,
      paddingLeft: `max(env(safe-area-inset-left), ${safeAreaInsets.left}px)`
    }),
    ...(enableKeyboardAdjustment && isKeyboardOpen && {
      paddingBottom: 'var(--keyboard-height)'
    })
  };

  return (
    <div className={layoutClasses} style={layoutStyles}>
      {children}
    </div>
  );
};

export const ResponsiveContainer: React.FC<ResponsiveContainerProps> = ({
  children,
  className,
  maxWidth = 'xl',
  padding = 'md',
  center = true
}) => {
  const { getResponsiveValue } = useMobileOptimization();

  const maxWidthClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    full: 'max-w-full'
  };

  const paddingClasses = getResponsiveValue({
    mobile: {
      none: 'p-0',
      sm: 'p-2',
      md: 'p-4',
      lg: 'p-6'
    }[padding],
    tablet: {
      none: 'p-0',
      sm: 'p-3',
      md: 'p-6',
      lg: 'p-8'
    }[padding],
    desktop: {
      none: 'p-0',
      sm: 'p-4',
      md: 'p-8',
      lg: 'p-12'
    }[padding],
    default: 'p-4'
  });

  const containerClasses = cn(
    'responsive-container w-full',
    maxWidthClasses[maxWidth],
    paddingClasses,
    {
      'mx-auto': center
    },
    className
  );

  return (
    <div className={containerClasses}>
      {children}
    </div>
  );
};

export const ResponsiveGrid: React.FC<ResponsiveGridProps> = ({
  children,
  className,
  cols = { mobile: 1, tablet: 2, desktop: 3 },
  gap = 'md'
}) => {
  const { getResponsiveValue } = useMobileOptimization();

  const gridCols = getResponsiveValue({
    mobile: `grid-cols-${cols.mobile || 1}`,
    tablet: `md:grid-cols-${cols.tablet || 2}`,
    desktop: `lg:grid-cols-${cols.desktop || 3}`,
    default: 'grid-cols-1'
  });

  const gapClasses = {
    sm: 'gap-2',
    md: 'gap-4',
    lg: 'gap-6'
  };

  const gridClasses = cn(
    'responsive-grid grid',
    gridCols,
    gapClasses[gap],
    className
  );

  return (
    <div className={gridClasses}>
      {children}
    </div>
  );
};

export const ResponsiveStack: React.FC<ResponsiveStackProps> = ({
  children,
  className,
  direction = { mobile: 'column', tablet: 'row', desktop: 'row' },
  spacing = 'md',
  align = 'stretch',
  justify = 'start'
}) => {
  const { getResponsiveValue } = useMobileOptimization();

  const flexDirection = getResponsiveValue({
    mobile: direction.mobile === 'row' ? 'flex-row' : 'flex-col',
    tablet: direction.tablet === 'row' ? 'md:flex-row' : 'md:flex-col',
    desktop: direction.desktop === 'row' ? 'lg:flex-row' : 'lg:flex-col',
    default: 'flex-col'
  });

  const spacingClasses = {
    sm: 'gap-2',
    md: 'gap-4',
    lg: 'gap-6'
  };

  const alignClasses = {
    start: 'items-start',
    center: 'items-center',
    end: 'items-end',
    stretch: 'items-stretch'
  };

  const justifyClasses = {
    start: 'justify-start',
    center: 'justify-center',
    end: 'justify-end',
    between: 'justify-between',
    around: 'justify-around',
    evenly: 'justify-evenly'
  };

  const stackClasses = cn(
    'responsive-stack flex',
    flexDirection,
    spacingClasses[spacing],
    alignClasses[align],
    justifyClasses[justify],
    className
  );

  return (
    <div className={stackClasses}>
      {children}
    </div>
  );
};

// Touch-optimized button component
interface TouchButtonProps {
  children: ReactNode;
  onClick?: () => void;
  className?: string;
  variant?: 'primary' | 'secondary' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
}

export const TouchButton: React.FC<TouchButtonProps> = ({
  children,
  onClick,
  className,
  variant = 'primary',
  size = 'md',
  disabled = false
}) => {
  const { hasTouch, isMobile } = useMobileOptimization();

  const baseClasses = 'touch-button relative inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
  
  const variantClasses = {
    primary: 'bg-gold text-black hover:bg-gold/90 focus:ring-gold',
    secondary: 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    outline: 'border-2 border-gold text-gold hover:bg-gold hover:text-black focus:ring-gold'
  };

  const sizeClasses = {
    sm: hasTouch ? 'px-4 py-3 text-sm min-h-[44px]' : 'px-3 py-2 text-sm',
    md: hasTouch ? 'px-6 py-4 text-base min-h-[48px]' : 'px-4 py-2 text-base',
    lg: hasTouch ? 'px-8 py-5 text-lg min-h-[52px]' : 'px-6 py-3 text-lg'
  };

  const touchClasses = hasTouch ? 'active:scale-95 touch-manipulation' : 'hover:scale-105';
  const mobileClasses = isMobile ? 'w-full' : '';

  const buttonClasses = cn(
    baseClasses,
    variantClasses[variant],
    sizeClasses[size],
    touchClasses,
    mobileClasses,
    {
      'opacity-50 cursor-not-allowed': disabled,
      'cursor-pointer': !disabled
    },
    className
  );

  return (
    <button
      className={buttonClasses}
      onClick={onClick}
      disabled={disabled}
      type="button"
    >
      {children}
    </button>
  );
};

// Responsive text component
interface ResponsiveTextProps {
  children: ReactNode;
  className?: string;
  size?: {
    mobile?: 'xs' | 'sm' | 'base' | 'lg' | 'xl' | '2xl' | '3xl';
    tablet?: 'xs' | 'sm' | 'base' | 'lg' | 'xl' | '2xl' | '3xl';
    desktop?: 'xs' | 'sm' | 'base' | 'lg' | 'xl' | '2xl' | '3xl';
  };
  weight?: 'normal' | 'medium' | 'semibold' | 'bold';
  align?: 'left' | 'center' | 'right';
}

export const ResponsiveText: React.FC<ResponsiveTextProps> = ({
  children,
  className,
  size = { mobile: 'base', tablet: 'lg', desktop: 'xl' },
  weight = 'normal',
  align = 'left'
}) => {
  const { getResponsiveValue } = useMobileOptimization();

  const textSize = getResponsiveValue({
    mobile: `text-${size.mobile || 'base'}`,
    tablet: `md:text-${size.tablet || 'lg'}`,
    desktop: `lg:text-${size.desktop || 'xl'}`,
    default: 'text-base'
  });

  const weightClasses = {
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold'
  };

  const alignClasses = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right'
  };

  const textClasses = cn(
    'responsive-text',
    textSize,
    weightClasses[weight],
    alignClasses[align],
    className
  );

  return (
    <div className={textClasses}>
      {children}
    </div>
  );
};

export default ResponsiveLayout;
