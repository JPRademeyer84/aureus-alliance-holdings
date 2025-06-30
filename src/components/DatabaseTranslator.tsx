import React, { useState, useEffect, createContext, useContext } from 'react';
import { Button } from '@/components/ui/button';

// Safe icons
const Globe = ({ className }: { className?: string }) => <span className={className}>🌐</span>;
const ChevronDown = ({ className }: { className?: string }) => <span className={className}>▼</span>;

interface Language {
  id: number;
  code: string;
  name: string;
  native_name: string;
  flag: string;
  is_default: boolean;
  sort_order: number;
}

interface TranslationContextType {
  currentLanguage: string;
  translations: Record<string, string>;
  languages: Language[];
  translate: (key: string, fallback?: string) => string;
  setLanguage: (code: string) => void;
  isLoading: boolean;
}

const TranslationContext = createContext<TranslationContextType | null>(null);

// Translation Provider Component
export const TranslationProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [currentLanguage, setCurrentLanguage] = useState('en');
  const [translations, setTranslations] = useState<Record<string, string>>({});
  const [languages, setLanguages] = useState<Language[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Load saved language from localStorage
    const savedLanguage = localStorage.getItem('selectedLanguage') || 'en';
    setCurrentLanguage(savedLanguage);
    
    // Load languages and translations
    loadLanguages();
    loadTranslations(savedLanguage);
  }, []);

  const loadLanguages = async () => {
    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/translations/get-languages.php');
      const data = await response.json();
      if (data.success) {
        setLanguages(data.languages);
      } else {
        // Fallback to default languages if database is not available
        setLanguages([
          { id: 1, code: 'en', name: 'English', native_name: 'English', flag: '🇺🇸', is_default: true, sort_order: 1 },
          { id: 2, code: 'es', name: 'Spanish', native_name: 'Español', flag: '🇪🇸', is_default: false, sort_order: 2 }
        ]);
      }
    } catch (error) {
      console.error('Error loading languages:', error);
      // Fallback to default languages
      setLanguages([
        { id: 1, code: 'en', name: 'English', native_name: 'English', flag: '🇺🇸', is_default: true, sort_order: 1 },
        { id: 2, code: 'es', name: 'Spanish', native_name: 'Español', flag: '🇪🇸', is_default: false, sort_order: 2 }
      ]);
    }
  };

  const loadTranslations = async (languageCode: string) => {
    try {
      setIsLoading(true);
      const response = await fetch(`http://localhost/Aureus%201%20-%20Complex/api/translations/get-translations.php?language=${languageCode}`);
      const data = await response.json();
      
      if (data.success) {
        setTranslations(data.translations);
        console.log(`🌍 Loaded ${data.count} translations for ${languageCode}`);
      } else {
        console.warn('No translations found for language:', languageCode);
        setTranslations({});
      }
    } catch (error) {
      console.error('Error loading translations:', error);
      // Fallback to empty translations - will use fallback text
      setTranslations({});
    } finally {
      setIsLoading(false);
    }
  };

  const setLanguage = (code: string) => {
    setCurrentLanguage(code);
    localStorage.setItem('selectedLanguage', code);
    loadTranslations(code);
    
    // Show notification
    const language = languages.find(l => l.code === code);
    if (language) {
      showNotification(`${language.flag} Language changed to ${language.name}`, 'success');
    }
  };

  const translate = (key: string, fallback?: string): string => {
    // Return translation if exists
    if (translations[key]) {
      return translations[key];
    }
    
    // Return fallback if provided
    if (fallback) {
      return fallback;
    }
    
    // Return the key itself as last resort
    return key;
  };

  const showNotification = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#dc2626' : '#1f2937';
    
    notification.innerHTML = `
      <div style="
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 9999;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        max-width: 300px;
      ">
        ${message}
      </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 4000);
  };

  return (
    <TranslationContext.Provider value={{
      currentLanguage,
      translations,
      languages,
      translate,
      setLanguage,
      isLoading
    }}>
      {children}
    </TranslationContext.Provider>
  );
};

// Hook to use translation context
export const useTranslation = () => {
  const context = useContext(TranslationContext);
  if (!context) {
    throw new Error('useTranslation must be used within a TranslationProvider');
  }
  return context;
};

// Translation component for inline text
export const T: React.FC<{ k: string; fallback?: string; className?: string }> = ({ 
  k, 
  fallback, 
  className 
}) => {
  const { translate } = useTranslation();
  return <span className={className}>{translate(k, fallback)}</span>;
};

// Language Selector Component
interface DatabaseTranslatorProps {
  className?: string;
}

const DatabaseTranslator: React.FC<DatabaseTranslatorProps> = ({ className = '' }) => {
  const [isOpen, setIsOpen] = useState(false);
  const { currentLanguage, languages, setLanguage, isLoading } = useTranslation();
  
  const selectedLanguage = languages.find(lang => lang.code === currentLanguage) || languages[0];

  const handleLanguageSelect = (language: Language) => {
    setLanguage(language.code);
    setIsOpen(false);
  };

  return (
    <div className={`relative ${className}`}>
      <Button
        variant="ghost"
        size="sm"
        onClick={() => setIsOpen(!isOpen)}
        disabled={isLoading}
        className="flex items-center gap-2 text-white/80 hover:text-white hover:bg-white/10 border border-gold/30 hover:border-gold/50 disabled:opacity-50"
      >
        <Globe className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
        {selectedLanguage && (
          <>
            <span className="hidden md:inline">{selectedLanguage.flag}</span>
            <span className="hidden lg:inline text-xs">{selectedLanguage.name}</span>
          </>
        )}
        <ChevronDown className="h-3 w-3" />
      </Button>

      {isOpen && (
        <div className="absolute right-0 top-full mt-2 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto">
          {languages.map((language) => (
            <button
              key={language.code}
              onClick={() => handleLanguageSelect(language)}
              disabled={isLoading}
              className={`w-full px-3 py-2 text-left text-sm hover:bg-gray-700 flex items-center gap-3 transition-colors disabled:opacity-50 ${
                currentLanguage === language.code ? 'bg-gold/20 text-gold' : 'text-white'
              }`}
            >
              <span className="text-lg">{language.flag}</span>
              <div className="flex flex-col">
                <span>{language.name}</span>
                <span className="text-xs text-gray-400">{language.native_name}</span>
              </div>
              {currentLanguage === language.code && (
                <span className="ml-auto text-gold">✓</span>
              )}
            </button>
          ))}
        </div>
      )}

      {/* Click outside to close */}
      {isOpen && (
        <div
          className="fixed inset-0 z-40"
          onClick={() => setIsOpen(false)}
        />
      )}

      {/* Loading indicator */}
      {isLoading && (
        <div className="absolute -top-1 -right-1 w-2 h-2 bg-yellow-400 rounded-full animate-pulse" 
             title="Loading translations..." />
      )}
    </div>
  );
};

export default DatabaseTranslator;
