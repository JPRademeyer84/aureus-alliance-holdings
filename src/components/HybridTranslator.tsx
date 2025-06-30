import React, { useState, useEffect } from 'react';
// Safe icons
const Globe = ({ className }: { className?: string }) => <span className={className}>🌐</span>;
const ChevronDown = ({ className }: { className?: string }) => <span className={className}>▼</span>;
import { Button } from '@/components/ui/button';

interface Language {
  id: number;
  code: string;
  name: string;
  native_name: string;
  flag: string;
  is_default: boolean;
  sort_order: number;
}

interface HybridTranslatorProps {
  className?: string;
}

const HybridTranslator: React.FC<HybridTranslatorProps> = ({ className = '' }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedLanguage, setSelectedLanguage] = useState<Language | null>(null);
  const [languages, setLanguages] = useState<Language[]>([]);
  const [translations, setTranslations] = useState<Record<string, string>>({});
  const [isTranslating, setIsTranslating] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  // Note: This translator requires database connection - no hardcoded fallbacks

  useEffect(() => {
    loadLanguages();
    
    // Check for saved language and restore translation
    const savedLang = localStorage.getItem('selectedLanguage') || 'en';
    setTimeout(() => {
      const lang = languages.find(l => l.code === savedLang);
      if (lang) {
        setSelectedLanguage(lang);
        if (savedLang !== 'en') {
          loadTranslations(savedLang);
        }
      }
    }, 500);
  }, []);

  useEffect(() => {
    if (languages.length > 0) {
      const savedLang = localStorage.getItem('selectedLanguage') || 'en';
      const lang = languages.find(l => l.code === savedLang) || languages.find(l => l.is_default) || languages[0];
      setSelectedLanguage(lang);
      setIsLoading(false);
      
      if (savedLang !== 'en') {
        loadTranslations(savedLang);
      }
    }
  }, [languages]);

  const loadLanguages = async () => {
    try {
      // Try to load from database first
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-languages.php');
      const data = await response.json();
      if (data.success) {
        setLanguages(data.languages);
        console.log('🌍 Loaded languages from database:', data.languages.length);
        return;
      }
    } catch (error) {
      console.error('Error loading languages from database:', error);
    }

    // Fallback to default languages if database fails
    console.log('🌍 Using fallback languages');
    setLanguages([
      { id: 1, code: 'en', name: 'English', native_name: 'English', flag: '🇺🇸', is_default: true, sort_order: 1 },
      { id: 2, code: 'es', name: 'Spanish', native_name: 'Español', flag: '🇪🇸', is_default: false, sort_order: 2 },
      { id: 3, code: 'fr', name: 'French', native_name: 'Français', flag: '🇫🇷', is_default: false, sort_order: 3 },
      { id: 4, code: 'de', name: 'German', native_name: 'Deutsch', flag: '🇩🇪', is_default: false, sort_order: 4 },
      { id: 5, code: 'pt', name: 'Portuguese', native_name: 'Português', flag: '🇵🇹', is_default: false, sort_order: 5 },
      { id: 6, code: 'it', name: 'Italian', native_name: 'Italiano', flag: '🇮🇹', is_default: false, sort_order: 6 },
      { id: 7, code: 'ru', name: 'Russian', native_name: 'Русский', flag: '🇷🇺', is_default: false, sort_order: 7 },
      { id: 8, code: 'zh', name: 'Chinese', native_name: '中文', flag: '🇨🇳', is_default: false, sort_order: 8 },
      { id: 9, code: 'ja', name: 'Japanese', native_name: '日本語', flag: '🇯🇵', is_default: false, sort_order: 9 },
      { id: 10, code: 'ar', name: 'Arabic', native_name: 'العربية', flag: '🇸🇦', is_default: false, sort_order: 10 }
    ]);
  };

  const loadTranslations = async (languageCode: string) => {
    try {
      // Try to load from database first
      const response = await fetch(`http://localhost/aureus-angel-alliance/api/translations/get-translations.php?language=${languageCode}`);
      const data = await response.json();

      if (data.success) {
        setTranslations(data.translations);
        console.log(`🌍 Loaded ${data.count} translations from database for ${languageCode}`);

        // Apply translations to the page
        setTimeout(() => {
          translatePageContent(languageCode, data.translations);
        }, 100);
        return;
      }
    } catch (error) {
      console.error('Error loading translations from database:', error);
    }

    // No fallback to hardcoded translations - show error instead
    console.error(`🌍 Failed to load translations for ${languageCode} - database required`);
    setTranslations({});

    // Show error notification
    const language = languages.find(l => l.code === languageCode);
    showNotification(`❌ Translation failed for ${language?.name || languageCode}. Database connection required.`, 'error');
  };

  const translateText = (text: string, translations: Record<string, string>): string => {
    const cleanText = text.trim();
    
    // Direct match
    if (translations[cleanText]) {
      return translations[cleanText];
    }

    // Try partial matches for compound phrases
    let translatedText = cleanText;
    let hasTranslation = false;
    
    // Sort keys by length (longest first) to avoid partial replacements
    const sortedKeys = Object.keys(translations).sort((a, b) => b.length - a.length);
    
    for (const key of sortedKeys) {
      if (translatedText.includes(key)) {
        translatedText = translatedText.replace(new RegExp(key, 'gi'), translations[key]);
        hasTranslation = true;
      }
    }
    
    // If we found any translations, return the result
    if (hasTranslation) {
      return translatedText;
    }

    // Return original if no translation found
    return text;
  };

  const translatePageContent = (languageCode: string, translationData: Record<string, string>) => {
    console.log('🌍 Translating page to:', languageCode);
    
    if (languageCode === 'en') {
      // Reset to English - reload page
      window.location.reload();
      return;
    }

    let translatedCount = 0;

    // Find all text nodes and translate them
    const walker = document.createTreeWalker(
      document.body,
      NodeFilter.SHOW_TEXT,
      {
        acceptNode: (node) => {
          const parent = node.parentElement;
          if (!parent) return NodeFilter.FILTER_REJECT;
          
          // Skip script, style, and other non-visible elements
          const tagName = parent.tagName.toLowerCase();
          if (['script', 'style', 'noscript', 'meta'].includes(tagName)) {
            return NodeFilter.FILTER_REJECT;
          }
          
          const text = node.textContent?.trim();
          if (!text || text.length < 2) {
            return NodeFilter.FILTER_REJECT;
          }
          
          return NodeFilter.FILTER_ACCEPT;
        }
      }
    );

    const textNodes: Text[] = [];
    let node;
    while (node = walker.nextNode()) {
      textNodes.push(node as Text);
    }

    console.log(`📝 Found ${textNodes.length} text nodes to translate`);

    // Translate each text node
    textNodes.forEach(textNode => {
      const originalText = textNode.textContent?.trim();
      if (originalText && originalText.length > 1) {
        const translatedText = translateText(originalText, translationData);
        if (translatedText !== originalText) {
          textNode.textContent = translatedText;
          translatedCount++;
          console.log(`✅ Translated: "${originalText}" → "${translatedText}"`);
        }
      }
    });

    // Also translate button texts, placeholders, etc.
    const buttons = document.querySelectorAll('button, a[role="button"], input[type="submit"]');
    buttons.forEach(button => {
      const text = button.textContent?.trim();
      if (text) {
        const translated = translateText(text, translationData);
        if (translated !== text) {
          button.textContent = translated;
          translatedCount++;
        }
      }
    });

    // Translate input placeholders
    const inputs = document.querySelectorAll('input[placeholder], textarea[placeholder]');
    inputs.forEach(input => {
      const placeholder = (input as HTMLInputElement).placeholder;
      if (placeholder) {
        const translated = translateText(placeholder, translationData);
        if (translated !== placeholder) {
          (input as HTMLInputElement).placeholder = translated;
          translatedCount++;
        }
      }
    });

    console.log(`🎉 Translation complete! Translated ${translatedCount} elements`);
    
    // Show success notification
    const language = languages.find(l => l.code === languageCode);
    if (language) {
      showNotification(`✅ Page translated to ${language.name}! (${translatedCount} elements)`, 'success');
    }
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
    }, 5000);
  };

  const handleLanguageSelect = (language: Language) => {
    setSelectedLanguage(language);
    setIsOpen(false);
    setIsTranslating(true);
    
    console.log(`🌍 Language selected: ${language.name} (${language.code})`);
    
    // Save language choice
    localStorage.setItem('selectedLanguage', language.code);
    
    // Show translating notification
    showNotification(`${language.flag} Translating to ${language.name}...`, 'info');
    
    // Start translation
    setTimeout(() => {
      if (language.code === 'en') {
        window.location.reload();
      } else {
        loadTranslations(language.code);
      }
      setIsTranslating(false);
    }, 500);
  };

  return (
    <div className={`relative ${className}`}>
      <Button
        variant="ghost"
        size="sm"
        onClick={() => setIsOpen(!isOpen)}
        disabled={isTranslating || isLoading}
        className="flex items-center gap-2 text-white/80 hover:text-white hover:bg-white/10 border border-gold/30 hover:border-gold/50 disabled:opacity-50"
      >
        <Globe className={`h-4 w-4 ${isTranslating || isLoading ? 'animate-spin' : ''}`} />
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
              disabled={isTranslating}
              className={`w-full px-3 py-2 text-left text-sm hover:bg-gray-700 flex items-center gap-3 transition-colors disabled:opacity-50 ${
                selectedLanguage?.code === language.code ? 'bg-gold/20 text-gold' : 'text-white'
              }`}
            >
              <span className="text-lg">{language.flag}</span>
              <div className="flex flex-col">
                <span>{language.name}</span>
                <span className="text-xs text-gray-400">{language.native_name}</span>
              </div>
              {selectedLanguage?.code === language.code && (
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
    </div>
  );
};

export default HybridTranslator;
