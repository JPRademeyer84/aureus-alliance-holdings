import React, { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Bug, Settings } from 'lucide-react';
import { useDebugPanel } from '@/hooks/useDebugPanel';
import EnhancedDebugPanel from './EnhancedDebugPanel';

interface DebugButtonProps {
  position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left';
  showLabel?: boolean;
  variant?: 'floating' | 'inline';
}

const DebugButton: React.FC<DebugButtonProps> = ({ 
  position = 'bottom-right', 
  showLabel = false,
  variant = 'floating'
}) => {
  const { 
    isOpen, 
    isEnabled, 
    loading, 
    features,
    toggleDebugPanel, 
    closeDebugPanel 
  } = useDebugPanel();

  // Add keyboard shortcut support
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.shiftKey && event.key === 'D') {
        event.preventDefault();
        if (isEnabled) {
          toggleDebugPanel();
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isEnabled, toggleDebugPanel]);

  // Don't render if debug is not enabled or still loading
  if (loading || !isEnabled) {
    return null;
  }

  const getPositionClasses = () => {
    if (variant === 'inline') return '';
    
    const baseClasses = 'fixed z-40';
    switch (position) {
      case 'bottom-right':
        return `${baseClasses} bottom-4 right-4`;
      case 'bottom-left':
        return `${baseClasses} bottom-4 left-4`;
      case 'top-right':
        return `${baseClasses} top-4 right-4`;
      case 'top-left':
        return `${baseClasses} top-4 left-4`;
      default:
        return `${baseClasses} bottom-4 right-4`;
    }
  };

  const buttonContent = (
    <>
      <Bug className="w-4 h-4" />
      {showLabel && <span className="ml-2">Debug</span>}
      {features.length > 0 && (
        <span className="ml-1 px-1.5 py-0.5 bg-green-500 text-black text-xs rounded-full font-semibold">
          {features.length}
        </span>
      )}
    </>
  );

  return (
    <>
      <div className={getPositionClasses()}>
        {variant === 'floating' ? (
          <Button
            onClick={toggleDebugPanel}
            className="bg-gray-800 hover:bg-gray-700 text-gold border border-gold/30 shadow-lg"
            size={showLabel ? "default" : "sm"}
            title={`Debug Panel (${features.length} features) - Ctrl+Shift+D`}
          >
            {buttonContent}
          </Button>
        ) : (
          <Button
            onClick={toggleDebugPanel}
            variant="outline"
            size="sm"
            className="text-gold border-gold/30 hover:bg-gold/10"
            title={`Debug Panel (${features.length} features) - Ctrl+Shift+D`}
          >
            {buttonContent}
          </Button>
        )}
      </div>

      <EnhancedDebugPanel 
        isOpen={isOpen} 
        onClose={closeDebugPanel} 
      />
    </>
  );
};

export default DebugButton;
