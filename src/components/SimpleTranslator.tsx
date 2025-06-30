import React, { createContext, useContext, useState } from 'react';

// Simple fallback translations
const fallbackTranslations = {
  en: {
    // Navigation
    'nav.rewards': 'Rewards',
    'nav.affiliate': 'Affiliate',
    'nav.benefits': 'Benefits',
    'nav.about': 'About',
    'nav.sign_in': 'Sign In',
    
    // Homepage Hero
    'homepage.hero.title_part1': 'Become an',
    'homepage.hero.title_part2': 'Angel Funder',
    'homepage.hero.title_part3': 'in the Future of Digital',
    'homepage.hero.title_part4': 'Gold',
    'homepage.hero.subtitle': 'Exclusive pre-seed opportunity to fund Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.',
    'homepage.hero.cta_participate_en': 'Fund Now',
    'homepage.hero.cta_learn_en': 'Learn More',
    
    // Stats
    'stats.reward_funding': 'Reward on Funding',
    'stats.annual_share': 'Annual per Share',
    'stats.affiliate_commission': 'Affiliate Commission',
    
    // About
    'about.title_part1': 'About',
    'about.title_part2': 'Aureus Alliance Holdings',
    
    // CTA
    'cta.only': 'Only',
    'cta.preseed_funding': 'of pre-seed funding',
    'cta.available': 'available',
    'cta.secure_position': 'Secure your position',
    'cta.before_opportunity_closes': 'before the opportunity closes',
    'cta.fund_now_button': 'Fund Now',
    
    // Common
    'loading': 'Loading...',
    'error': 'Error',
    'success': 'Success'
  },
  es: {
    // Navigation
    'nav.rewards': 'Recompensas',
    'nav.affiliate': 'Afiliado',
    'nav.benefits': 'Beneficios',
    'nav.about': 'Acerca de',
    'nav.sign_in': 'Iniciar Sesión',
    
    // Homepage Hero
    'homepage.hero.title_part1': 'Conviértete en un',
    'homepage.hero.title_part2': 'Inversor Ángel',
    'homepage.hero.title_part3': 'en el Futuro del Oro',
    'homepage.hero.title_part4': 'Digital',
    'homepage.hero.subtitle': 'Oportunidad exclusiva de pre-semilla para financiar Aureus Alliance Holdings – combinando minería de oro física con coleccionables NFT digitales.',
    'homepage.hero.cta_participate_en': 'Financiar Ahora',
    'homepage.hero.cta_learn_en': 'Saber Más',
    
    // Stats
    'stats.reward_funding': 'Recompensa por Financiación',
    'stats.annual_share': 'Anual por Acción',
    'stats.affiliate_commission': 'Comisión de Afiliado',
    
    // About
    'about.title_part1': 'Acerca de',
    'about.title_part2': 'Aureus Alliance Holdings',
    
    // CTA
    'cta.only': 'Solo',
    'cta.preseed_funding': 'de financiación pre-semilla',
    'cta.available': 'disponible',
    'cta.secure_position': 'Asegura tu posición',
    'cta.before_opportunity_closes': 'antes de que se cierre la oportunidad',
    'cta.fund_now_button': 'Financiar Ahora',
    
    // Common
    'loading': 'Cargando...',
    'error': 'Error',
    'success': 'Éxito'
  }
};

interface SimpleTranslationContextType {
  currentLanguage: string;
  translate: (key: string, fallback?: string) => string;
  setLanguage: (code: string) => void;
  isLoading: boolean;
}

const SimpleTranslationContext = createContext<SimpleTranslationContextType | null>(null);

export const SimpleTranslationProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [currentLanguage, setCurrentLanguage] = useState('en');
  const [isLoading] = useState(false);

  const translate = (key: string, fallback?: string): string => {
    const translations = fallbackTranslations[currentLanguage as keyof typeof fallbackTranslations] || fallbackTranslations.en;
    return translations[key as keyof typeof translations] || fallback || key;
  };

  const setLanguage = (code: string) => {
    setCurrentLanguage(code);
    localStorage.setItem('selectedLanguage', code);
  };

  return (
    <SimpleTranslationContext.Provider value={{
      currentLanguage,
      translate,
      setLanguage,
      isLoading
    }}>
      {children}
    </SimpleTranslationContext.Provider>
  );
};

export const useSimpleTranslation = () => {
  const context = useContext(SimpleTranslationContext);
  if (!context) {
    throw new Error('useSimpleTranslation must be used within a SimpleTranslationProvider');
  }
  return context;
};

// Simple translation component
export const ST: React.FC<{ k: string; fallback?: string; className?: string }> = ({ 
  k, 
  fallback, 
  className 
}) => {
  const { translate } = useSimpleTranslation();
  return <span className={className}>{translate(k, fallback)}</span>;
};
