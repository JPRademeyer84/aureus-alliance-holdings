import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Globe, Plus, Edit, Save, X, Check, AlertCircle, Filter, CheckCircle, XCircle, Wand2, Loader2, Shield, Eye, ChevronDown, ChevronRight } from 'lucide-react';

interface Language {
  id: number;
  code: string;
  name: string;
  native_name: string;
  flag: string;
  is_default: boolean;
  sort_order: number;
}

interface TranslationKey {
  id: number;
  key_name: string;
  description: string;
  category: string;
}

interface Translation {
  id: number;
  key_id: number;
  language_id: number;
  translation_text: string;
  is_approved: boolean;
}

const TranslationManagement: React.FC = () => {
  const [languages, setLanguages] = useState<Language[]>([]);
  const [translationKeys, setTranslationKeys] = useState<TranslationKey[]>([]);
  const [translations, setTranslations] = useState<Translation[]>([]);
  const [englishTranslations, setEnglishTranslations] = useState<Translation[]>([]);
  const [selectedLanguage, setSelectedLanguage] = useState<string>('');
  const [translationFilter, setTranslationFilter] = useState<'all' | 'translated' | 'untranslated' | 'issues'>('all');
  const [translationIssues, setTranslationIssues] = useState<Map<number, {accuracy: number, suggestion: string}>>(new Map());
  const [aiTranslating, setAiTranslating] = useState<Set<number>>(new Set());
  const [bulkTranslating, setBulkTranslating] = useState<Set<string>>(new Set());
  const [translateAllInProgress, setTranslateAllInProgress] = useState(false);
  const [verifying, setVerifying] = useState<Set<number>>(new Set());
  const [confirmingIssue, setConfirmingIssue] = useState<Set<number>>(new Set());
  const [detectingIssues, setDetectingIssues] = useState<boolean>(false);
  const [confirmedTranslations, setConfirmedTranslations] = useState<Set<string>>(new Set());
  const [collapsedCategories, setCollapsedCategories] = useState<Set<string>>(new Set());
  const [verifyingCategory, setVerifyingCategory] = useState<Set<string>>(new Set());
  const [serverConnected, setServerConnected] = useState<boolean>(true);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  // Check server connectivity
  const checkServerConnection = async () => {
    try {
      console.log('Testing server connection...');

      const response = await fetch('http://localhost/aureus-angel-alliance/api/api-status.php', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-cache'
      });

      if (response.ok) {
        const data = await response.json();
        console.log('Server connection test result:', data);

        if (data.success) {
          setServerConnected(true);
          return true;
        } else {
          console.error('Server test failed:', data);
          setServerConnected(false);
          return false;
        }
      } else {
        console.error('Server response not OK:', response.status, response.statusText);
        setServerConnected(false);
        return false;
      }
    } catch (error) {
      console.error('Server connection check failed:', error);
      setServerConnected(false);
      return false;
    }
  };
  const [activeTab, setActiveTab] = useState('translations');

  // New language form
  const [newLanguage, setNewLanguage] = useState({
    code: '',
    name: '',
    native_name: '',
    flag: '',
    sort_order: 0
  });

  // New translation key form
  const [newKey, setNewKey] = useState({
    key_name: '',
    description: '',
    category: ''
  });

  useEffect(() => {
    const initializeApp = async () => {
      const isConnected = await checkServerConnection();
      if (isConnected) {
        // Load languages first, then other data
        await fetchLanguages();
        fetchTranslationKeys();
        fetchEnglishTranslations(); // Always load English as reference
      } else {
        setLoading(false);
        alert('⚠️ Server Connection Error\n\nCannot connect to the translation API server.\n\nPlease ensure:\n1. Your local server is running\n2. The URL http://localhost/aureus-angel-alliance/ is accessible\n3. Check your network connection\n\nRefresh the page after fixing the connection.');
      }
    };

    initializeApp();
  }, []);

  // Load confirmed translations from database
  const loadConfirmedTranslations = async () => {
    if (!selectedLanguage) return;

    try {
      const languageId = languages.find(l => l.code === selectedLanguage)?.id;
      if (!languageId) return;

      const response = await fetch(`http://localhost/aureus-angel-alliance/api/translations/get-confirmed-translations.php?language_id=${languageId}`);
      const data = await response.json();

      if (data.success && data.confirmations && data.confirmations.length > 0) {
        const confirmedSet = new Set<string>();
        data.confirmations.forEach((confirmation: any) => {
          confirmedSet.add(`${confirmation.key_id}_${confirmation.language_id}`);
        });
        setConfirmedTranslations(confirmedSet);
        console.log(`Loaded ${data.confirmations.length} confirmed translations for ${selectedLanguage}`);
      } else {
        setConfirmedTranslations(new Set());
      }
    } catch (error) {
      console.error('Error loading confirmed translations:', error);
      setConfirmedTranslations(new Set());
    }
  };

  // Load existing translation issues from database
  const loadExistingIssues = async () => {
    if (!selectedLanguage) return;

    try {
      const languageId = languages.find(l => l.code === selectedLanguage)?.id;
      if (!languageId) return;

      const response = await fetch(`http://localhost/aureus-angel-alliance/api/translations/get-translation-issues.php?language_id=${languageId}&resolved=false`);
      const data = await response.json();

      if (data.success && data.issues && data.issues.length > 0) {
        const issuesMap = new Map();

        // Group issues by key_id
        const issuesByKey = {};
        data.issues.forEach(issue => {
          if (!issuesByKey[issue.key_id]) {
            issuesByKey[issue.key_id] = [];
          }
          issuesByKey[issue.key_id].push(issue);
        });

        // Create issue map for display
        Object.keys(issuesByKey).forEach(keyId => {
          const keyIssues = issuesByKey[keyId];
          const highestSeverityIssue = keyIssues.reduce((prev, current) => {
            const severityOrder = { critical: 4, high: 3, medium: 2, low: 1 };
            return (severityOrder[current.severity] > severityOrder[prev.severity]) ? current : prev;
          });

          issuesMap.set(parseInt(keyId), {
            accuracy: Math.max(0, 100 - (keyIssues.length * 15)), // Reduce accuracy based on issue count
            suggestion: `${keyIssues.length} issue(s): ${highestSeverityIssue.issue_description}`,
            issueCount: keyIssues.length,
            severity: highestSeverityIssue.severity
          });
        });

        setTranslationIssues(issuesMap);
        console.log(`Loaded ${data.issues.length} existing issues for ${Object.keys(issuesByKey).length} translations`);
      } else {
        // Clear issues if none found
        setTranslationIssues(new Map());
      }
    } catch (error) {
      console.error('Error loading existing issues:', error);
      setTranslationIssues(new Map());
    }
  };

  useEffect(() => {
    if (selectedLanguage && serverConnected && languages.length > 0) {
      // Verify the selected language exists in the languages array
      const languageExists = languages.some(lang => lang.code === selectedLanguage);
      if (languageExists) {
        fetchTranslations(selectedLanguage);
        loadExistingIssues(); // Also load existing issues
        loadConfirmedTranslations(); // Load confirmed translations
      }
    }
  }, [selectedLanguage, serverConnected, languages]);

  const fetchLanguages = async () => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-languages.php');
      const data = await response.json();
      if (data.success) {
        setLanguages(data.languages);

        // Set default language after languages are loaded
        if (data.languages && data.languages.length > 0 && !selectedLanguage) {
          // Try to find Spanish first, otherwise use the first available language
          const spanishLang = data.languages.find(lang => lang.code === 'es');
          const defaultLang = spanishLang || data.languages[0];
          setSelectedLanguage(defaultLang.code);
        }
      }
    } catch (error) {
      console.error('Error fetching languages:', error);
    }
  };

  const fetchEnglishTranslations = async () => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-all-translations.php?language=en');
      const data = await response.json();
      if (data.success) {
        setEnglishTranslations(data.translations);
      }
    } catch (error) {
      console.error('Error fetching English translations:', error);
    }
  };

  const fetchTranslationKeys = async () => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-translation-keys.php');
      const data = await response.json();
      if (data.success) {
        setTranslationKeys(data.keys);
      }
    } catch (error) {
      console.error('Error fetching translation keys:', error);
    }
  };

  const fetchTranslations = async (languageCode: string) => {
    try {
      setLoading(true);
      const response = await fetch(`http://localhost/aureus-angel-alliance/api/translations/get-all-translations.php?language=${languageCode}`);
      const data = await response.json();
      if (data.success) {
        setTranslations(data.translations);
        // Auto-detect issues for existing translations only if languages are loaded
        if (languages.length > 0 && languages.some(lang => lang.code === languageCode)) {
          await autoDetectTranslationIssues(data.translations, languageCode);
        }
      }
    } catch (error) {
      console.error('Error fetching translations:', error);
    } finally {
      setLoading(false);
    }
  };

  const addLanguage = async () => {
    try {
      setSaving(true);
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/add-language.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newLanguage)
      });

      const data = await response.json();
      if (data.success) {
        setNewLanguage({ code: '', name: '', native_name: '', flag: '', sort_order: 0 });
        fetchLanguages();
        alert('Language added successfully!');
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error adding language:', error);
      alert('Error adding language');
    } finally {
      setSaving(false);
    }
  };

  const addTranslationKey = async () => {
    try {
      setSaving(true);
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/add-translation-key.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newKey)
      });

      const data = await response.json();
      if (data.success) {
        setNewKey({ key_name: '', description: '', category: '' });
        fetchTranslationKeys();
        fetchEnglishTranslations(); // Refresh English translations
        alert('Translation key added successfully!');
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      console.error('Error adding translation key:', error);
      alert('Error adding translation key');
    } finally {
      setSaving(false);
    }
  };

  const updateTranslation = async (keyId: number, languageId: number, text: string) => {
    try {
      console.log('🔄 Starting translation update:', { keyId, languageId, text });

      // Enhanced server connectivity test
      console.log('🔍 Testing server connectivity...');
      const testResponse = await fetch('http://localhost/aureus-angel-alliance/api/translations/update-translation.php', {
        method: 'OPTIONS',
        headers: { 'Content-Type': 'application/json' }
      }).catch((error) => {
        console.error('❌ Server connectivity test failed:', error);
        return null;
      });

      if (!testResponse) {
        console.error('❌ Server is not reachable');
        throw new Error('Cannot connect to server. Please ensure:\n\n1. XAMPP is running\n2. Apache service is started\n3. URL http://localhost/aureus-angel-alliance/ is accessible\n4. No firewall is blocking the connection');
      }

      console.log('✅ Server connectivity test passed');

      console.log('📤 Sending translation update request...');
      const requestBody = {
        key_id: keyId,
        language_id: languageId,
        translation_text: text
      };
      console.log('📋 Request payload:', requestBody);

      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/update-translation.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(requestBody)
      });

      console.log('📥 Response received:', {
        status: response.status,
        statusText: response.statusText,
        ok: response.ok,
        headers: Object.fromEntries(response.headers.entries())
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('❌ HTTP Error Response:', errorText);
        throw new Error(`HTTP error! status: ${response.status} (${response.statusText})\n\nServer response: ${errorText}`);
      }

      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const responseText = await response.text();
        console.error('❌ Non-JSON response received:', responseText);
        throw new Error('Server returned non-JSON response. Expected JSON but got: ' + responseText);
      }

      const data = await response.json();
      console.log('✅ Translation update response:', data);

      if (data.success) {
        console.log('🎉 Translation updated successfully!');

        // Clear any issues for this key since it was successfully updated
        setTranslationIssues(prev => {
          const newMap = new Map(prev);
          newMap.delete(keyId);
          return newMap;
        });

        // Refresh translations to show the update
        console.log('🔄 Refreshing translations...');
        await fetchTranslations(selectedLanguage);

        // Show success message
        console.log('✅ Translation update completed successfully');

        // Optional: Show brief success notification
        // alert(`✅ Translation saved successfully!`);
      } else {
        console.error('❌ Translation update failed:', data);
        throw new Error(data.message || 'Translation update failed');
      }
    } catch (error) {
      console.error('❌ Error updating translation:', error);

      let errorMessage = 'Error updating translation: ';

      if (error instanceof TypeError && error.message.includes('fetch')) {
        errorMessage = '🚨 Connection Error: Cannot reach the server.\n\nPlease check:\n• XAMPP is running\n• Apache service is started\n• URL http://localhost/aureus-angel-alliance/ is accessible\n• No firewall is blocking the connection\n\nTry using the "Test Database Connection" button for detailed diagnostics.';
      } else if (error instanceof Error) {
        errorMessage += error.message;
      } else {
        errorMessage += 'Unknown error occurred';
      }

      alert(errorMessage);
    }
  };

  // AI Translation for single key
  const translateWithAI = async (keyId: number, englishText: string) => {
    const targetLanguage = selectedLangObj?.name || selectedLanguage;

    setAiTranslating(prev => new Set(prev).add(keyId));

    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/ai-translate-improved.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          text: englishText,
          target_language: targetLanguage,
          language_code: selectedLanguage,
          key_id: keyId
        })
      });

      const data = await response.json();
      if (data.success) {
        // Update the translation in the database
        const langId = languages.find(l => l.code === selectedLanguage)?.id;
        if (langId) {
          await updateTranslation(keyId, langId, data.translation);
        }
        if (data.was_translated) {
          alert(`✅ AI Translation completed: "${data.original_text}" → "${data.translation}"`);
        } else {
          alert(`⚠️ No translation found for "${englishText}". Original text kept.`);
        }
      } else {
        alert('AI Translation failed: ' + data.message);
      }
    } catch (error) {
      console.error('AI Translation error:', error);
      alert('AI Translation failed. Please try again.');
    } finally {
      setAiTranslating(prev => {
        const newSet = new Set(prev);
        newSet.delete(keyId);
        return newSet;
      });
    }
  };

  // AI Translation for entire category
  const translateCategoryWithAI = async (category: string) => {
    const targetLanguage = selectedLangObj?.name || selectedLanguage;
    const categoryKeys = translationKeys.filter(key => key.category === category);

    // Get keys that need translation (untranslated OR have issues)
    const keysToTranslate = categoryKeys.filter(key => {
      const translation = translations.find(t => t.key_id === key.id);
      const hasTranslation = translation && translation.translation_text && translation.translation_text.trim() !== '';
      const hasIssue = translationIssues.has(key.id);

      // Include if untranslated OR has issues
      return !hasTranslation || hasIssue;
    });

    if (keysToTranslate.length === 0) {
      alert(`All keys in "${category}" category are already translated and have no issues!`);
      return;
    }

    const issueCount = keysToTranslate.filter(key => translationIssues.has(key.id)).length;
    const untranslatedCount = keysToTranslate.length - issueCount;

    let confirmMessage = `AI Translate "${category}" category:\n\n`;
    if (untranslatedCount > 0) {
      confirmMessage += `• ${untranslatedCount} untranslated keys\n`;
    }
    if (issueCount > 0) {
      confirmMessage += `• ${issueCount} keys with issues to fix\n`;
    }
    confirmMessage += `\nTotal: ${keysToTranslate.length} keys to process. Continue?`;

    const confirmTranslate = confirm(confirmMessage);
    if (!confirmTranslate) return;

    setBulkTranslating(prev => new Set(prev).add(category));

    try {
      // Prepare batch translation data
      const translationBatch = keysToTranslate.map(key => {
        const englishTranslation = englishTranslations.find(t => t.key_id === key.id);
        const issue = translationIssues.get(key.id);

        return {
          key_id: key.id,
          key_name: key.key_name,
          english_text: englishTranslation?.translation_text || key.description,
          has_issue: !!issue,
          issue_suggestion: issue?.suggestion || null
        };
      });

      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/ai-translate-batch-improved.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          translations: translationBatch,
          target_language: targetLanguage,
          language_code: selectedLanguage,
          category: category
        })
      });

      const data = await response.json();
      if (data.success) {
        // Clear issues for successfully translated keys
        setTranslationIssues(prev => {
          const newMap = new Map(prev);
          keysToTranslate.forEach(key => {
            newMap.delete(key.id);
          });
          return newMap;
        });

        // Refresh translations to show the new ones
        await fetchTranslations(selectedLanguage);

        let message = `✅ AI Translation completed for "${category}" category!\n\n📊 Results:\n`;
        message += `• Total processed: ${data.translated_count} keys\n`;
        message += `• Actually translated: ${data.actually_translated} keys\n`;
        message += `• Issues fixed: ${issueCount} keys\n`;
        message += `• Kept original: ${data.translated_count - data.actually_translated} keys`;

        alert(message);
      } else {
        alert('Batch AI Translation failed: ' + data.message);
      }
    } catch (error) {
      console.error('Batch AI Translation error:', error);
      alert('Batch AI Translation failed. Please try again.');
    } finally {
      setBulkTranslating(prev => {
        const newSet = new Set(prev);
        newSet.delete(category);
        return newSet;
      });
    }
  };

  // AI Translation for all untranslated keys
  const translateAllWithAI = async () => {
    const targetLanguage = selectedLangObj?.name || selectedLanguage;
    const untranslatedKeys = translationKeys.filter(key => {
      const translation = translations.find(t => t.key_id === key.id);
      return !translation || !translation.translation_text || translation.translation_text.trim() === '';
    });

    if (untranslatedKeys.length === 0) {
      alert('All keys are already translated!');
      return;
    }

    const confirmTranslate = confirm(
      `This will AI translate ${untranslatedKeys.length} untranslated keys to ${targetLanguage}. Continue?`
    );

    if (!confirmTranslate) return;

    setTranslateAllInProgress(true);

    try {
      // Prepare batch translation data
      const translationBatch = untranslatedKeys.map(key => {
        const englishTranslation = englishTranslations.find(t => t.key_id === key.id);
        return {
          key_id: key.id,
          key_name: key.key_name,
          english_text: englishTranslation?.translation_text || key.description
        };
      });

      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/ai-translate-batch-improved.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          translations: translationBatch,
          target_language: targetLanguage,
          language_code: selectedLanguage,
          category: 'all'
        })
      });

      const data = await response.json();
      if (data.success) {
        // Refresh translations to show the new ones
        await fetchTranslations(selectedLanguage);
        alert(`🎉 Complete AI Translation finished!\n\n📊 Final Results:\n• Total processed: ${data.translated_count} keys\n• Actually translated: ${data.actually_translated} keys\n• Kept original: ${data.translated_count - data.actually_translated} keys\n\nYour ${targetLanguage} translation is now complete!`);
      } else {
        alert('Bulk AI Translation failed: ' + data.message);
      }
    } catch (error) {
      console.error('Bulk AI Translation error:', error);
      alert('Bulk AI Translation failed. Please try again.');
    } finally {
      setTranslateAllInProgress(false);
    }
  };

  // Auto-detect translation issues for existing translations
  const autoDetectTranslationIssues = async (translations: Translation[], languageCode: string) => {
    console.log('🔍 Starting auto-detect translation issues...', { translations: translations.length, languageCode });
    setDetectingIssues(true);

    try {
      const selectedLangObj = languages.find(lang => lang.code === languageCode);
      if (!selectedLangObj) {
        console.warn('❌ No language object found for code:', languageCode, 'Available languages:', languages.map(l => l.code));
        // Don't show alert if languages haven't loaded yet
        if (languages.length > 0) {
          alert('❌ Error: Language not found. Please select a valid language.');
        }
        return;
      }

      if (translations.length === 0) {
        console.warn('⚠️ No translations to check for issues');
        alert('⚠️ No translations found to check for issues. Please add some translations first.');
        return;
      }

    const issuesMap = new Map<number, { accuracy: number; suggestion: string }>();

    // Check each translation for potential issues
    for (const translation of translations) {
      if (!translation.translation_text || translation.translation_text.trim() === '') continue;

      // Skip if this translation has been manually confirmed
      const confirmationKey = `${translation.key_id}_${languages.find(l => l.code === languageCode)?.id}`;
      if (confirmedTranslations.has(confirmationKey)) {
        console.log(`Skipping issue detection for confirmed translation: ${confirmationKey}`);
        continue;
      }

      const englishTranslation = englishTranslations.find(t => t.key_id === translation.key_id);
      const englishText = englishTranslation?.translation_text ||
                         translationKeys.find(k => k.id === translation.key_id)?.description || '';

      if (!englishText) continue;

      // Simple issue detection logic
      const translatedText = translation.translation_text.trim();
      const originalText = englishText.trim();

      // Check if translation is identical to original (potential issue for non-English languages)
      if (languageCode !== 'en' && translatedText === originalText) {
        // List of terms that are commonly proper nouns or shouldn't be translated
        const properNouns = [
          'MetaMask', 'SafePal', 'Trust Wallet', 'Coinbase', 'Binance',
          'Aureus', 'Angel Alliance', 'NFT', 'ROI', 'KYC', 'API', 'URL',
          'USD', 'USDT', 'BTC', 'ETH', 'DeFi', 'Web3', 'DAO', 'dApp',
          'GitHub', 'Twitter', 'Facebook', 'LinkedIn', 'Telegram', 'WhatsApp',
          'iOS', 'Android', 'Windows', 'macOS', 'Linux'
        ];

        // Check if the text contains any proper nouns
        const containsProperNoun = properNouns.some(noun =>
          originalText.includes(noun) ||
          originalText.toLowerCase().includes(noun.toLowerCase())
        );

        // Check if it's a single capitalized word (likely proper noun)
        const isSingleCapitalizedWord = /^[A-Z][a-zA-Z]*$/.test(originalText.trim());

        // Check if it's all uppercase (likely acronym)
        const isAcronym = /^[A-Z]{2,}$/.test(originalText.trim());

        // Check if it contains special characters or numbers (likely technical term)
        const isTechnicalTerm = /[0-9@#$%&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(originalText);

        if (containsProperNoun || isSingleCapitalizedWord || isAcronym || isTechnicalTerm) {
          issuesMap.set(translation.key_id, {
            accuracy: 10, // Low accuracy to flag as issue
            suggestion: 'Translation may need review - not found in verified dictionary'
          });
        }
      }

      // Check for very short translations that might be incomplete
      if (originalText.length > 10 && translatedText.length < 3) {
        issuesMap.set(translation.key_id, {
          accuracy: 20,
          suggestion: 'Translation appears incomplete or too short'
        });
      }

      // Check for translations that contain English words mixed with target language
      if (languageCode !== 'en') {
        const englishWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        const hasEnglishWords = englishWords.some(word =>
          translatedText.toLowerCase().includes(` ${word} `) ||
          translatedText.toLowerCase().startsWith(`${word} `) ||
          translatedText.toLowerCase().endsWith(` ${word}`)
        );

        if (hasEnglishWords) {
          issuesMap.set(translation.key_id, {
            accuracy: 30,
            suggestion: 'Translation contains English words - may need review'
          });
        }
      }
    }

    // Update the translation issues state
    setTranslationIssues(issuesMap);

      // Provide user feedback
      const skippedConfirmed = translations.length - (translations.filter(t => !t.translation_text || t.translation_text.trim() === '').length) - issuesMap.size;

      if (issuesMap.size > 0) {
        console.log(`🔍 Auto-detected ${issuesMap.size} translation issues for ${languageCode}:`, Array.from(issuesMap.entries()));
        console.log(`📊 Detection summary: ${translations.length} total, ${skippedConfirmed} confirmed/skipped, ${issuesMap.size} issues found`);
        alert(`🔍 Issue Detection Complete!\n\n✅ Scanned ${translations.length} translations\n🚨 Found ${issuesMap.size} potential issues\n✅ Skipped ${skippedConfirmed} confirmed translations\n\n💡 Use the "Issues Only" filter to view problematic translations.\n\nIssues found include:\n• Untranslated proper nouns\n• Incomplete translations\n• Mixed language content`);
      } else {
        console.log(`✅ No translation issues detected for ${languageCode}`);
        console.log(`📊 Detection summary: ${translations.length} total, ${skippedConfirmed} confirmed/skipped, 0 issues found`);
        alert(`✅ Issue Detection Complete!\n\n🎉 Great news! No issues detected in ${translations.length} translations.\n✅ ${skippedConfirmed} translations were previously confirmed as correct.\n\nYour ${selectedLangObj.name} translations appear to be in good shape!`);
      }
    } catch (error) {
      console.error('❌ Error during issue detection:', error);
      alert(`❌ Error during issue detection: ${error instanceof Error ? error.message : 'Unknown error'}`);
    } finally {
      setDetectingIssues(false);
    }
  };

  // Confirm/Override translation issue (for proper nouns, program names, etc.)
  const confirmTranslationIssue = async (keyId: number) => {
    setConfirmingIssue(prev => new Set(prev.add(keyId)));

    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/confirm-translation-issue.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          key_id: keyId,
          language_id: languages.find(l => l.code === selectedLanguage)?.id,
          override_reason: 'Proper noun/program name - translation not required'
        })
      });

      const data = await response.json();
      if (data.success) {
        // Remove the issue from local state
        setTranslationIssues(prev => {
          const newMap = new Map(prev);
          newMap.delete(keyId);
          return newMap;
        });

        // Add to confirmed translations to prevent re-detection
        const languageId = languages.find(l => l.code === selectedLanguage)?.id;
        if (languageId) {
          const confirmationKey = `${keyId}_${languageId}`;
          setConfirmedTranslations(prev => new Set(prev).add(confirmationKey));
          console.log(`Added confirmed translation: ${confirmationKey}`);
        }

        let successMessage = '✅ Translation issue confirmed and overridden. This translation is now approved.';
        if (data.resolved_issues_count > 0) {
          successMessage += `\n\n📊 Resolved ${data.resolved_issues_count} issue(s) for this translation.`;
        }
        if (data.translation_approved) {
          successMessage += '\n✅ Translation has been marked as approved in the database.';
        }
        alert(successMessage);

        // Refresh translations to show updated approval status
        fetchTranslations(selectedLanguage);
      } else {
        console.error('Translation confirmation failed:', data);
        let errorMessage = 'Error confirming translation issue: ' + (data.message || 'Unknown error');
        if (data.debug_info) {
          errorMessage += '\n\nDebug info:';
          errorMessage += '\n• Key ID: ' + data.debug_info.key_id;
          errorMessage += '\n• Language ID: ' + data.debug_info.language_id;
          errorMessage += '\n• Error: ' + data.error;
        }
        alert(errorMessage);
      }
    } catch (error) {
      console.error('Error confirming translation issue:', error);
      let errorMessage = 'Error confirming translation issue. Please try again.';
      if (error instanceof Error) {
        errorMessage += '\n\nTechnical details: ' + error.message;
      }
      alert(errorMessage);
    } finally {
      setConfirmingIssue(prev => {
        const newSet = new Set(prev);
        newSet.delete(keyId);
        return newSet;
      });
    }
  };

  // Verify translation accuracy with database issue tracking
  const verifyTranslation = async (keyId: number, translationText: string, englishText: string) => {
    const targetLanguage = selectedLangObj?.name || selectedLanguage;

    setVerifying(prev => new Set(prev).add(keyId));

    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/verify-database-translation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          key_id: keyId,
          language_id: languages.find(l => l.code === selectedLanguage)?.id,
          verification_run_id: `verify_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
        })
      });

      const data = await response.json();
      if (data.success) {
        // Track issues for filtering and visual indicators
        setTranslationIssues(prev => {
          const newMap = new Map(prev);
          if (!data.is_approved && data.issues_count > 0) {
            newMap.set(keyId, {
              accuracy: Math.max(0, 100 - (data.issues_count * 20)),
              suggestion: data.issues[0] || 'Review translation quality'
            });
          } else {
            newMap.delete(keyId);
          }
          return newMap;
        });

        // Show comprehensive verification results
        const severityEmojis = {
          critical: '🚨',
          high: '⚠️',
          medium: '⚡',
          low: '💡'
        };

        let message = `🔍 Database Translation Verification Results:\n\n`;
        message += `📊 Status: ${data.is_approved ? '✅ APPROVED' : '❌ NEEDS ATTENTION'}\n`;
        message += `🔢 Issues Found: ${data.issues_count}\n`;
        message += data.cached_result ? `⚡ Using cached results from recent check\n` : `🆕 Fresh verification completed\n`;
        message += `🔤 Original: "${englishText}"\n`;
        message += `🌍 Translation: "${translationText}"\n`;

        if (data.severity_counts) {
          message += `\n📈 Issue Breakdown:\n`;
          if (data.severity_counts.critical > 0) message += `  🚨 Critical: ${data.severity_counts.critical}\n`;
          if (data.severity_counts.high > 0) message += `  ⚠️ High: ${data.severity_counts.high}\n`;
          if (data.severity_counts.medium > 0) message += `  ⚡ Medium: ${data.severity_counts.medium}\n`;
          if (data.severity_counts.low > 0) message += `  💡 Low: ${data.severity_counts.low}\n`;
        }

        if (data.issues.length > 0) {
          message += `\n🔍 Issues Found:\n`;
          data.issues.forEach((issue: string, index: number) => {
            message += `${index + 1}. ${issue}\n`;
          });
        } else {
          message += `\n✅ No issues found - translation is approved!`;
        }

        message += `\n💾 Results saved to database - won't re-check same issues`;

        alert(message);

        // Refresh the translations to show updated approval status
        fetchTranslations(selectedLanguage);
      } else {
        alert('Translation verification failed: ' + data.message);
      }
    } catch (error) {
      console.error('Translation verification error:', error);
      alert('Translation verification failed. Please try again.');
    } finally {
      setVerifying(prev => {
        const newSet = new Set(prev);
        newSet.delete(keyId);
        return newSet;
      });
    }
  };

  // Verify entire category translations
  const verifyCategoryTranslations = async (category: string) => {
    const targetLanguage = selectedLangObj?.name || selectedLanguage;
    const categoryKeys = translationKeys.filter(key => key.category === category);
    const translatedKeys = categoryKeys.filter(key => {
      const translation = translations.find(t => t.key_id === key.id);
      return translation && translation.translation_text && translation.translation_text.trim() !== '';
    });

    if (translatedKeys.length === 0) {
      alert(`No translations found in "${category}" category to verify.`);
      return;
    }

    setVerifyingCategory(prev => new Set(prev).add(category));

    try {
      // Prepare batch verification data
      const verificationBatch = translatedKeys.map(key => {
        const translation = translations.find(t => t.key_id === key.id);
        const englishTranslation = englishTranslations.find(t => t.key_id === key.id);
        return {
          key_id: key.id,
          key_name: key.key_name,
          original_text: englishTranslation?.translation_text || key.description,
          translated_text: translation?.translation_text || ''
        };
      });

      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/verify-category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          translations: verificationBatch,
          target_language: targetLanguage,
          language_code: selectedLanguage,
          category: category
        })
      });

      const data = await response.json();
      if (data.success) {
        const avgAccuracy = data.average_accuracy;
        const totalVerified = data.verified_count;
        const issues = data.issues || [];
        const results = data.results || [];

        // Track all issues from category verification
        setTranslationIssues(prev => {
          const newMap = new Map(prev);

          // Clear existing issues for this category
          const categoryKeys = translationKeys.filter(key => key.category === category);
          categoryKeys.forEach(key => {
            newMap.delete(key.id);
          });

          // Add new issues
          results.forEach((result: any) => {
            if (result.accuracy < 80) {
              newMap.set(result.key_id, {
                accuracy: result.accuracy,
                suggestion: result.suggestions[0] || 'Review translation quality'
              });
            }
          });

          return newMap;
        });

        let message = `🔍 Category "${category}" Verification Results:\n\n`;
        message += `📊 Average Accuracy: ${avgAccuracy}%\n`;
        message += `✅ Translations Verified: ${totalVerified}\n`;

        if (avgAccuracy >= 90) {
          message += `\n🎉 Excellent category quality!`;
        } else if (avgAccuracy >= 70) {
          message += `\n⚠️ Good category quality, some improvements possible.`;
        } else {
          message += `\n❌ Category needs improvement.`;
        }

        if (issues.length > 0) {
          message += `\n\n🚨 Issues Found:\n`;
          issues.slice(0, 5).forEach((issue: any, index: number) => {
            message += `${index + 1}. ${issue.key_name}: ${issue.accuracy}% - ${issue.suggestion}\n`;
          });

          if (issues.length > 5) {
            message += `... and ${issues.length - 5} more issues.`;
          }
        }

        alert(message);
      } else {
        alert('Category verification failed: ' + data.message);
      }
    } catch (error) {
      console.error('Category verification error:', error);
      alert('Category verification failed. Please try again.');
    } finally {
      setVerifyingCategory(prev => {
        const newSet = new Set(prev);
        newSet.delete(category);
        return newSet;
      });
    }
  };

  // Toggle category collapse
  const toggleCategoryCollapse = (category: string) => {
    setCollapsedCategories(prev => {
      const newSet = new Set(prev);
      if (newSet.has(category)) {
        newSet.delete(category);
      } else {
        newSet.add(category);
      }
      return newSet;
    });
  };

  // Comprehensive diagnostic test
  const runDiagnostics = async () => {
    const results = [];

    try {
      // Test 1: Basic server connection
      results.push('🔍 Testing basic server connection...');
      const serverTest = await fetch('http://localhost/aureus-angel-alliance/api/api-status.php');
      if (serverTest.ok) {
        const serverData = await serverTest.json();
        results.push(`✅ Server: ${serverData.message}`);
      } else {
        results.push(`❌ Server: HTTP ${serverTest.status}`);
      }
    } catch (error) {
      results.push(`❌ Server: ${error instanceof Error ? error.message : 'Connection failed'}`);
    }

    try {
      // Test 2: Translation tables setup
      results.push('\n🔍 Testing translation tables...');
      const setupTest = await fetch('http://localhost/aureus-angel-alliance/api/translations/setup-translation-tables.php');
      if (setupTest.ok) {
        const setupData = await setupTest.json();
        results.push(`✅ Tables: ${setupData.message}`);
        if (setupData.tables_created.length > 0) {
          results.push(`📋 Created/Verified: ${setupData.tables_created.join(', ')}`);
        }
      } else {
        results.push(`❌ Tables: HTTP ${setupTest.status}`);
      }
    } catch (error) {
      results.push(`❌ Tables: ${error instanceof Error ? error.message : 'Setup failed'}`);
    }

    try {
      // Test 3: Languages endpoint
      results.push('\n🔍 Testing languages endpoint...');
      const langTest = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-languages.php');
      if (langTest.ok) {
        const langData = await langTest.json();
        results.push(`✅ Languages: Found ${langData.length || 0} languages`);
      } else {
        results.push(`❌ Languages: HTTP ${langTest.status}`);
      }
    } catch (error) {
      results.push(`❌ Languages: ${error instanceof Error ? error.message : 'Failed'}`);
    }

    try {
      // Test 4: Translation keys endpoint
      results.push('\n🔍 Testing translation keys endpoint...');
      const keysTest = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-translation-keys.php');
      if (keysTest.ok) {
        const keysData = await keysTest.json();
        results.push(`✅ Keys: Found ${keysData.length || 0} translation keys`);
      } else {
        results.push(`❌ Keys: HTTP ${keysTest.status}`);
      }
    } catch (error) {
      results.push(`❌ Keys: ${error instanceof Error ? error.message : 'Failed'}`);
    }

    // Show results
    alert(`🔧 Translation System Diagnostics\n\n${results.join('\n')}\n\n${results.filter(r => r.includes('✅')).length > 0 ? 'Some tests passed! Try refreshing the page.' : 'All tests failed. Check your server setup.'}`);
  };

  // Filter translation keys based on translation status
  const getFilteredKeys = (keys: TranslationKey[]) => {
    if (translationFilter === 'all') return keys;

    return keys.filter(key => {
      const translation = translations.find(t => t.key_id === key.id);
      const hasTranslation = translation && translation.translation_text && translation.translation_text.trim() !== '';
      const hasIssue = translationIssues.has(key.id);

      if (translationFilter === 'translated') {
        return hasTranslation;
      } else if (translationFilter === 'untranslated') {
        return !hasTranslation;
      } else if (translationFilter === 'issues') {
        return hasIssue;
      }
      return true;
    });
  };

  // Calculate translation statistics
  const getTranslationStats = () => {
    const totalKeys = translationKeys.length;
    const translatedKeys = translationKeys.filter(key => {
      const translation = translations.find(t => t.key_id === key.id);
      return translation && translation.translation_text && translation.translation_text.trim() !== '';
    }).length;
    const untranslatedKeys = totalKeys - translatedKeys;
    const completionPercentage = totalKeys > 0 ? Math.round((translatedKeys / totalKeys) * 100) : 0;

    return {
      total: totalKeys,
      translated: translatedKeys,
      untranslated: untranslatedKeys,
      percentage: completionPercentage
    };
  };

  const stats = getTranslationStats();
  const categories = [...new Set(translationKeys.map(key => key.category))];
  const selectedLangObj = languages.find(lang => lang.code === selectedLanguage);

  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Globe className="h-8 w-8 text-gold" />
          <h1 className="text-3xl font-bold text-white">Translation Management</h1>
        </div>

        {/* Server Status Indicator */}
        <div className="flex items-center gap-2">
          <div className={`w-3 h-3 rounded-full ${serverConnected ? 'bg-green-500' : 'bg-red-500'}`}></div>
          <span className={`text-sm ${serverConnected ? 'text-green-400' : 'text-red-400'}`}>
            {serverConnected ? 'Server Connected' : 'Server Disconnected'}
          </span>
          {!serverConnected && (
            <Button
              size="sm"
              variant="outline"
              onClick={checkServerConnection}
              className="border-red-500 text-red-400 hover:bg-red-500 hover:text-white"
            >
              Retry Connection
            </Button>
          )}
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="translations">Manage Translations</TabsTrigger>
          <TabsTrigger value="languages">Manage Languages</TabsTrigger>
          <TabsTrigger value="keys">Manage Translation Keys</TabsTrigger>
        </TabsList>

        <TabsContent value="translations" className="space-y-6">
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Edit className="h-5 w-5" />
                Translation Editor
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Language Selector and Filters */}
              <div className="bg-gray-700 border border-gray-600 rounded-lg p-4 space-y-4">
                <div className="flex flex-col lg:flex-row lg:items-center gap-4">
                  <div className="flex items-center gap-4">
                    <label className="text-white font-medium">Select Language to Translate:</label>
                    <select
                      value={selectedLanguage}
                      onChange={(e) => setSelectedLanguage(e.target.value)}
                      className="bg-gray-800 text-white border border-gray-600 rounded px-3 py-2"
                    >
                      {!selectedLanguage && (
                        <option value="" disabled>
                          {languages.length === 0 ? 'Loading languages...' : 'Select a language'}
                        </option>
                      )}
                      {languages.filter(lang => lang.code !== 'en').map(lang => (
                        <option key={lang.code} value={lang.code}>
                          {lang.flag} {lang.name} ({lang.native_name})
                        </option>
                      ))}
                    </select>
                    {selectedLangObj && (
                      <Badge variant="outline" className="text-gold border-gold">
                        Translating to {selectedLangObj.name}
                      </Badge>
                    )}
                  </div>

                  <div className="flex items-center gap-4">
                    <label className="text-white font-medium flex items-center gap-2">
                      <Filter className="h-4 w-4" />
                      Filter:
                    </label>
                    <Select value={translationFilter} onValueChange={(value: 'all' | 'translated' | 'untranslated' | 'issues') => setTranslationFilter(value)}>
                      <SelectTrigger className="w-48 bg-gray-800 border-gray-600 text-white">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-gray-800 border-gray-600">
                        <SelectItem value="all" className="text-white hover:bg-gray-700">
                          <div className="flex items-center gap-2">
                            <Globe className="h-4 w-4" />
                            All Keys ({stats.total})
                          </div>
                        </SelectItem>
                        <SelectItem value="translated" className="text-white hover:bg-gray-700">
                          <div className="flex items-center gap-2">
                            <CheckCircle className="h-4 w-4 text-green-400" />
                            Translated ({stats.translated})
                          </div>
                        </SelectItem>
                        <SelectItem value="untranslated" className="text-white hover:bg-gray-700">
                          <div className="flex items-center gap-2">
                            <XCircle className="h-4 w-4 text-red-400" />
                            Untranslated ({stats.untranslated})
                          </div>
                        </SelectItem>
                        <SelectItem value="issues" className="text-white hover:bg-gray-700">
                          <div className="flex items-center gap-2">
                            <AlertCircle className="h-4 w-4 text-orange-400" />
                            Issues ({translationIssues.size})
                          </div>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                {/* Translation Progress */}
                <div className="bg-gray-800 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-white font-medium">Translation Progress</span>
                    <span className="text-gold font-bold">{stats.percentage}%</span>
                  </div>
                  <div className="w-full bg-gray-600 rounded-full h-2 mb-3">
                    <div
                      className="bg-gradient-to-r from-gold to-yellow-500 h-2 rounded-full transition-all duration-300"
                      style={{ width: `${stats.percentage}%` }}
                    ></div>
                  </div>
                  <div className="grid grid-cols-3 gap-4 text-sm">
                    <div className="text-center">
                      <div className="text-white font-medium">{stats.total}</div>
                      <div className="text-gray-400">Total Keys</div>
                    </div>
                    <div className="text-center">
                      <div className="text-green-400 font-medium">{stats.translated}</div>
                      <div className="text-gray-400">Translated</div>
                    </div>
                    <div className="text-center">
                      <div className="text-red-400 font-medium">{stats.untranslated}</div>
                      <div className="text-gray-400">Remaining</div>
                    </div>
                  </div>
                </div>

                <div className="flex items-center justify-between">
                  <p className="text-gray-400 text-sm">
                    💡 English original text is shown above each translation field for reference
                  </p>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => {
                        if (collapsedCategories.size === categories.length) {
                          setCollapsedCategories(new Set());
                        } else {
                          setCollapsedCategories(new Set(categories));
                        }
                      }}
                      className="border-gray-600 text-gray-300 hover:bg-gray-700"
                    >
                      {collapsedCategories.size === categories.length ? (
                        <>
                          <ChevronDown className="h-3 w-3 mr-1" />
                          Expand All
                        </>
                      ) : (
                        <>
                          <ChevronRight className="h-3 w-3 mr-1" />
                          Collapse All
                        </>
                      )}
                    </Button>
                    <Button
                      onClick={translateAllWithAI}
                      disabled={translateAllInProgress || stats.untranslated === 0}
                      className="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white"
                    >
                      {translateAllInProgress ? (
                        <>
                          <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                          AI Translating All ({stats.untranslated} keys)...
                        </>
                      ) : (
                        <>
                          <Wand2 className="h-4 w-4 mr-2" />
                          AI Translate All ({stats.untranslated} remaining)
                        </>
                      )}
                    </Button>

                    <Button
                      onClick={() => autoDetectTranslationIssues(translations, selectedLanguage)}
                      disabled={loading || detectingIssues}
                      className="bg-orange-600 hover:bg-orange-700 text-white"
                      title="Automatically detect translation issues"
                    >
                      {detectingIssues ? (
                        <>
                          <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                          Detecting Issues...
                        </>
                      ) : (
                        <>
                          <AlertCircle className="h-4 w-4 mr-2" />
                          Detect Issues
                        </>
                      )}
                    </Button>
                  </div>
                </div>
              </div>

              {/* Translations by Category */}
              {!serverConnected ? (
                <Card className="bg-gray-700 border-gray-600">
                  <CardContent className="text-center py-12">
                    <div className="flex flex-col items-center gap-4">
                      <XCircle className="h-16 w-16 text-red-400" />
                      <div>
                        <h3 className="text-white font-medium mb-2 text-xl">Server Connection Lost</h3>
                        <p className="text-gray-400 mb-4">
                          Cannot connect to the translation API server.
                        </p>
                        <div className="text-left bg-gray-800 p-4 rounded-lg mb-4">
                          <p className="text-white font-medium mb-2">Please check:</p>
                          <ul className="text-gray-300 space-y-1 text-sm">
                            <li>• Your local server is running</li>
                            <li>• The URL http://localhost/aureus-angel-alliance/ is accessible</li>
                            <li>• Your network connection is working</li>
                            <li>• No firewall is blocking the connection</li>
                          </ul>
                        </div>
                        <div className="flex gap-2">
                          <Button
                            onClick={async () => {
                              const connected = await checkServerConnection();
                              if (connected) {
                                window.location.reload();
                              }
                            }}
                            className="bg-blue-600 hover:bg-blue-700 text-white"
                          >
                            Test Connection & Reload
                          </Button>
                          <Button
                            onClick={async () => {
                              try {
                                const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/setup-translation-tables.php');
                                const data = await response.json();

                                if (data.success) {
                                  alert(`✅ Translation tables setup completed!\n\nTables created/verified:\n${data.tables_created.join('\n')}\n\nReloading page...`);
                                  window.location.reload();
                                } else {
                                  alert(`❌ Setup failed:\n\n${data.errors.join('\n')}`);
                                }
                              } catch (error) {
                                alert(`❌ Setup failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
                              }
                            }}
                            className="bg-green-600 hover:bg-green-700 text-white"
                          >
                            Setup Translation Tables
                          </Button>
                          <Button
                            onClick={runDiagnostics}
                            className="bg-purple-600 hover:bg-purple-700 text-white"
                          >
                            Run Full Diagnostics
                          </Button>
                          <Button
                            onClick={async () => {
                              try {
                                const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/debug-translation-system.php');
                                const data = await response.json();

                                const resultText = [
                                  `🔧 COMPLETE TRANSLATION SYSTEM DIAGNOSTIC`,
                                  ``,
                                  `📊 Summary:`,
                                  `✅ Total Checks: ${data.summary?.total_checks || 0}`,
                                  `❌ Errors Found: ${data.summary?.errors_found || 0}`,
                                  `🗄️ Database Connected: ${data.summary?.database_connected ? 'Yes' : 'No'}`,
                                  `📋 Tables Found: ${data.summary?.tables_exist || 0}`,
                                  ``,
                                  `📋 Detailed Results:`,
                                  ...data.debug_results,
                                  ...(data.errors.length > 0 ? ['', '🚨 ERRORS FOUND:', ...data.errors] : [])
                                ].join('\n');

                                alert(resultText);

                                if (data.success) {
                                  console.log('✅ Translation system diagnostic passed - should work now');
                                  // Refresh the page to load any newly created tables
                                  if (data.debug_results.some(r => r.includes('Created'))) {
                                    alert('✅ Missing tables were created! Refreshing page...');
                                    window.location.reload();
                                  }
                                } else {
                                  console.error('❌ Translation system diagnostic failed:', data.errors);
                                }
                              } catch (error) {
                                alert(`❌ Diagnostic failed: ${error instanceof Error ? error.message : 'Unknown error'}\n\nThis usually means:\n1. XAMPP is not running\n2. Apache service is stopped\n3. Translation API files are missing`);
                              }
                            }}
                            className="bg-red-600 hover:bg-red-700 text-white"
                          >
                            🔧 Fix Translation System
                          </Button>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ) : !selectedLanguage ? (
                <div className="text-center py-8">
                  <div className="flex flex-col items-center gap-4">
                    <Globe className="h-16 w-16 text-gray-400" />
                    <div>
                      <h3 className="text-white font-medium mb-2 text-xl">Select a Language</h3>
                      <p className="text-gray-400">
                        Please select a language from the dropdown above to start translating.
                      </p>
                    </div>
                  </div>
                </div>
              ) : loading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold mx-auto"></div>
                  <p className="text-gray-400 mt-2">Loading translations...</p>
                </div>
              ) : (
                <div className="space-y-6">
                  {categories.map(category => {
                    const categoryKeys = translationKeys.filter(key => key.category === category);
                    const filteredCategoryKeys = getFilteredKeys(categoryKeys);

                    // Skip categories with no keys after filtering
                    if (filteredCategoryKeys.length === 0) return null;

                    return (
                      <Card key={category} className="bg-gray-700 border-gray-600">
                        <CardHeader className="cursor-pointer" onClick={() => toggleCategoryCollapse(category)}>
                          <CardTitle className="text-white capitalize text-lg">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center gap-2">
                                {collapsedCategories.has(category) ? (
                                  <ChevronRight className="h-4 w-4" />
                                ) : (
                                  <ChevronDown className="h-4 w-4" />
                                )}
                                <span>{category.replace('_', ' ')}</span>
                              </div>
                              <div className="flex items-center gap-2" onClick={(e) => e.stopPropagation()}>
                                <Badge variant="outline" className="text-gray-300 border-gray-500">
                                  {filteredCategoryKeys.length} of {categoryKeys.length} keys
                                </Badge>
                                {translationFilter !== 'all' && (
                                  <Badge
                                    variant="outline"
                                    className={
                                      translationFilter === 'translated'
                                        ? "text-green-400 border-green-400"
                                        : translationFilter === 'untranslated'
                                        ? "text-red-400 border-red-400"
                                        : "text-orange-400 border-orange-400"
                                    }
                                  >
                                    {translationFilter === 'translated'
                                      ? 'Completed'
                                      : translationFilter === 'untranslated'
                                      ? 'Needs Translation'
                                      : 'Has Issues'}
                                  </Badge>
                                )}

                                {/* Category Verify Button */}
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => verifyCategoryTranslations(category)}
                                  disabled={verifyingCategory.has(category)}
                                  className="border-blue-500 text-blue-400 hover:bg-blue-500 hover:text-white"
                                  title={`Verify all translations in ${category} category`}
                                >
                                  {verifyingCategory.has(category) ? (
                                    <>
                                      <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                                      Verifying...
                                    </>
                                  ) : (
                                    <>
                                      <Shield className="h-3 w-3 mr-1" />
                                      Verify Category
                                    </>
                                  )}
                                </Button>

                                {/* AI Translate Category Button */}
                                <Button
                                  size="sm"
                                  onClick={() => translateCategoryWithAI(category)}
                                  disabled={bulkTranslating.has(category)}
                                  className="bg-purple-600 hover:bg-purple-700 text-white"
                                  title={`AI Translate all untranslated keys in ${category} category`}
                                >
                                  {bulkTranslating.has(category) ? (
                                    <>
                                      <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                                      Translating...
                                    </>
                                  ) : (
                                    <>
                                      <Wand2 className="h-3 w-3 mr-1" />
                                      AI Translate
                                    </>
                                  )}
                                </Button>
                              </div>
                            </div>
                          </CardTitle>
                        </CardHeader>
                        {!collapsedCategories.has(category) && (
                          <CardContent className="space-y-3">
                            {filteredCategoryKeys.map(key => {
                            const translation = translations.find(t => t.key_id === key.id);
                            const englishTranslation = englishTranslations.find(t => t.key_id === key.id);

                            return (
                              <div key={key.id} className="space-y-3 border-b border-gray-600 pb-4 mb-4">
                                <div className="flex items-center justify-between">
                                  <div className="flex items-center gap-2">
                                    <code className="text-gold text-sm bg-gray-800 px-2 py-1 rounded">
                                      {key.key_name}
                                    </code>
                                    {translation?.is_approved && (
                                      <Check className="h-4 w-4 text-green-500" />
                                    )}
                                  </div>
                                  <div className="flex items-center gap-2">
                                    {translationIssues.has(key.id) && (
                                      <Badge className="bg-orange-600 text-white border-orange-500">
                                        <AlertCircle className="h-3 w-3 mr-1" />
                                        Issue ({translationIssues.get(key.id)?.accuracy}%)
                                      </Badge>
                                    )}
                                    {translation && translation.translation_text && translation.translation_text.trim() !== '' ? (
                                      <Badge className="bg-green-600 text-white border-green-500">
                                        <CheckCircle className="h-3 w-3 mr-1" />
                                        Translated
                                      </Badge>
                                    ) : (
                                      <Badge variant="outline" className="text-red-400 border-red-400">
                                        <XCircle className="h-3 w-3 mr-1" />
                                        Needs Translation
                                      </Badge>
                                    )}
                                  </div>
                                </div>
                                <p className="text-gray-400 text-sm">{key.description}</p>

                                {/* English Original Text */}
                                <div className="bg-gray-800 border border-gray-600 rounded p-3">
                                  <div className="flex items-center gap-2 mb-2">
                                    <span className="text-xs font-medium text-blue-400">🇺🇸 ENGLISH ORIGINAL:</span>
                                  </div>
                                  <p className="text-white text-sm font-medium">
                                    {englishTranslation?.translation_text || 'No English text found'}
                                  </p>
                                </div>

                                {/* Translation Input */}
                                <div>
                                  <div className="flex items-center justify-between mb-2">
                                    <span className="text-xs font-medium text-gold">
                                      {selectedLangObj?.flag} {selectedLangObj?.name.toUpperCase()} TRANSLATION:
                                    </span>
                                    <div className="flex items-center gap-2">
                                      <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => translateWithAI(key.id, englishTranslation?.translation_text || key.description)}
                                        disabled={aiTranslating.has(key.id) || !englishTranslation?.translation_text}
                                        className="border-purple-500 text-purple-400 hover:bg-purple-500 hover:text-white"
                                        title="AI Translate this key"
                                      >
                                        {aiTranslating.has(key.id) ? (
                                          <>
                                            <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                                            Translating...
                                          </>
                                        ) : (
                                          <>
                                            <Wand2 className="h-3 w-3 mr-1" />
                                            AI Translate
                                          </>
                                        )}
                                      </Button>

                                      {translation && translation.translation_text && translation.translation_text.trim() !== '' && (
                                        <Button
                                          size="sm"
                                          variant="outline"
                                          onClick={() => verifyTranslation(key.id, translation.translation_text, englishTranslation?.translation_text || key.description)}
                                          disabled={verifying.has(key.id)}
                                          className="border-blue-500 text-blue-400 hover:bg-blue-500 hover:text-white"
                                          title="Verify translation accuracy"
                                        >
                                          {verifying.has(key.id) ? (
                                            <>
                                              <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                                              Verifying...
                                            </>
                                          ) : (
                                            <>
                                              <Shield className="h-3 w-3 mr-1" />
                                              Verify
                                            </>
                                          )}
                                        </Button>
                                      )}
                                    </div>
                                  </div>
                                  <Textarea
                                    value={translation?.translation_text || ''}
                                    onChange={(e) => {
                                      const langId = languages.find(l => l.code === selectedLanguage)?.id;
                                      if (langId) {
                                        updateTranslation(key.id, langId, e.target.value);
                                      }
                                    }}
                                    placeholder={`Enter ${selectedLangObj?.name} translation...`}
                                    className={`bg-gray-700 border-gray-600 text-white placeholder-gray-400 ${
                                      translationIssues.has(key.id) ? 'border-orange-500 ring-1 ring-orange-500' : ''
                                    }`}
                                    rows={3}
                                  />

                                  {/* Issue Warning */}
                                  {translationIssues.has(key.id) && (
                                    <div className="mt-2 p-2 bg-orange-900/20 border border-orange-500 rounded text-orange-300 text-sm">
                                      <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                          <AlertCircle className="h-4 w-4" />
                                          <span className="font-medium">Translation Issue ({translationIssues.get(key.id)?.accuracy}% accuracy)</span>
                                        </div>
                                        <Button
                                          size="sm"
                                          variant="outline"
                                          onClick={() => confirmTranslationIssue(key.id)}
                                          disabled={confirmingIssue.has(key.id)}
                                          className="border-green-500 text-green-400 hover:bg-green-500 hover:text-white"
                                          title="Confirm this translation is correct (e.g., proper noun, program name)"
                                        >
                                          {confirmingIssue.has(key.id) ? (
                                            <>
                                              <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                                              Confirming...
                                            </>
                                          ) : (
                                            <>
                                              <CheckCircle className="h-3 w-3 mr-1" />
                                              Confirm
                                            </>
                                          )}
                                        </Button>
                                      </div>
                                      <p className="mt-1 text-orange-200">
                                        💡 {translationIssues.get(key.id)?.suggestion}
                                      </p>
                                      <p className="mt-1 text-orange-100 text-xs">
                                        ℹ️ Click "Confirm" if this is a proper noun (like "MetaMask") or program name that shouldn't be translated.
                                      </p>
                                    </div>
                                  )}
                                </div>
                              </div>
                            );
                            })}
                          </CardContent>
                        )}
                      </Card>
                    );
                  }).filter(Boolean)}

                  {/* No results message */}
                  {categories.every(category => {
                    const categoryKeys = translationKeys.filter(key => key.category === category);
                    const filteredCategoryKeys = getFilteredKeys(categoryKeys);
                    return filteredCategoryKeys.length === 0;
                  }) && (
                    <Card className="bg-gray-700 border-gray-600">
                      <CardContent className="text-center py-12">
                        <div className="flex flex-col items-center gap-4">
                          {translationFilter === 'translated' ? (
                            <>
                              <XCircle className="h-12 w-12 text-red-400" />
                              <div>
                                <h3 className="text-white font-medium mb-2">No Translated Keys Found</h3>
                                <p className="text-gray-400">
                                  No translations have been completed for {selectedLangObj?.name} yet.
                                  Switch to "All Keys" or "Untranslated" to start translating.
                                </p>
                              </div>
                            </>
                          ) : translationFilter === 'untranslated' ? (
                            <>
                              <CheckCircle className="h-12 w-12 text-green-400" />
                              <div>
                                <h3 className="text-white font-medium mb-2">All Keys Translated! 🎉</h3>
                                <p className="text-gray-400">
                                  Congratulations! All translation keys have been completed for {selectedLangObj?.name}.
                                  Your translation is 100% complete.
                                </p>
                              </div>
                            </>
                          ) : (
                            <>
                              <AlertCircle className="h-12 w-12 text-yellow-400" />
                              <div>
                                <h3 className="text-white font-medium mb-2">No Translation Keys Found</h3>
                                <p className="text-gray-400">
                                  No translation keys are available. Please add some translation keys first.
                                </p>
                              </div>
                            </>
                          )}
                        </div>
                      </CardContent>
                    </Card>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="languages" className="space-y-6">
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Plus className="h-5 w-5" />
                Add New Language
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <Input
                  placeholder="Language Code (e.g., fr)"
                  value={newLanguage.code}
                  onChange={(e) => setNewLanguage({...newLanguage, code: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                />
                <Input
                  placeholder="English Name (e.g., French)"
                  value={newLanguage.name}
                  onChange={(e) => setNewLanguage({...newLanguage, name: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                />
                <Input
                  placeholder="Native Name (e.g., Français)"
                  value={newLanguage.native_name}
                  onChange={(e) => setNewLanguage({...newLanguage, native_name: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                />
                <Input
                  placeholder="Flag Emoji (e.g., 🇫🇷)"
                  value={newLanguage.flag}
                  onChange={(e) => setNewLanguage({...newLanguage, flag: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                />
              </div>
              <Button 
                onClick={addLanguage} 
                disabled={saving || !newLanguage.code || !newLanguage.name}
                className="bg-gold hover:bg-gold/80 text-black"
              >
                {saving ? 'Adding...' : 'Add Language'}
              </Button>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white">Existing Languages</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {languages.map(lang => (
                  <div key={lang.id} className="bg-gray-700 p-4 rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <span className="text-2xl">{lang.flag}</span>
                      <div>
                        <h3 className="text-white font-medium">{lang.name}</h3>
                        <p className="text-gray-400 text-sm">{lang.native_name}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge variant="outline" className="text-gold border-gold">
                        {lang.code}
                      </Badge>
                      {lang.is_default && (
                        <Badge variant="default">Default</Badge>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="keys" className="space-y-6">
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Plus className="h-5 w-5" />
                Add New Translation Key
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 gap-4">
                <Input
                  placeholder="Key Name (e.g., hero.title)"
                  value={newKey.key_name}
                  onChange={(e) => setNewKey({...newKey, key_name: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                />
                <Input
                  placeholder="Category (e.g., hero, navigation)"
                  value={newKey.category}
                  onChange={(e) => setNewKey({...newKey, category: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                />
                <Textarea
                  placeholder="Description of this translation key"
                  value={newKey.description}
                  onChange={(e) => setNewKey({...newKey, description: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white"
                  rows={3}
                />
              </div>
              <Button 
                onClick={addTranslationKey} 
                disabled={saving || !newKey.key_name || !newKey.category}
                className="bg-gold hover:bg-gold/80 text-black"
              >
                {saving ? 'Adding...' : 'Add Translation Key'}
              </Button>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white">Translation Keys by Category</CardTitle>
            </CardHeader>
            <CardContent>
              {categories.map(category => (
                <div key={category} className="mb-6">
                  <h3 className="text-white font-medium mb-3 capitalize">
                    {category.replace('_', ' ')} 
                    <Badge variant="outline" className="ml-2">
                      {translationKeys.filter(k => k.category === category).length} keys
                    </Badge>
                  </h3>
                  <div className="space-y-2">
                    {translationKeys
                      .filter(key => key.category === category)
                      .map(key => (
                        <div key={key.id} className="bg-gray-700 p-3 rounded">
                          <code className="text-gold">{key.key_name}</code>
                          <p className="text-gray-400 text-sm mt-1">{key.description}</p>
                        </div>
                      ))}
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default TranslationManagement;
