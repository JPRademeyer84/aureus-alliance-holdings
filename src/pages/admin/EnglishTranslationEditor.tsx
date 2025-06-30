import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Loader2, Wand2, Save, RefreshCw, Search, Filter } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface TranslationKey {
  id: number;
  key_name: string;
  description: string;
  category: string;
}

interface EnglishTranslation {
  id: number;
  key_id: number;
  translation_text: string;
  is_approved: boolean;
}

interface Language {
  id: number;
  code: string;
  name: string;
  native_name: string;
  flag: string;
}

const EnglishTranslationEditor: React.FC = () => {
  const [translationKeys, setTranslationKeys] = useState<TranslationKey[]>([]);
  const [englishTranslations, setEnglishTranslations] = useState<EnglishTranslation[]>([]);
  const [languages, setLanguages] = useState<Language[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState<Set<number>>(new Set());
  const [regenerating, setRegenerating] = useState<Set<number>>(new Set());
  const [regeneratingAll, setRegeneratingAll] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [editedTexts, setEditedTexts] = useState<Map<number, string>>(new Map());

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    setLoading(true);
    try {
      await Promise.all([
        fetchTranslationKeys(),
        fetchEnglishTranslations(),
        fetchLanguages()
      ]);
    } catch (error) {
      console.error('Error fetching data:', error);
    } finally {
      setLoading(false);
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

  const fetchLanguages = async () => {
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/get-languages.php');
      const data = await response.json();
      if (data.success) {
        setLanguages(data.languages.filter((lang: Language) => lang.code !== 'en'));
      }
    } catch (error) {
      console.error('Error fetching languages:', error);
    }
  };

  const updateEnglishText = (keyId: number, newText: string) => {
    setEditedTexts(prev => new Map(prev.set(keyId, newText)));
  };

  const saveEnglishTranslation = async (keyId: number) => {
    const newText = editedTexts.get(keyId);
    if (!newText) return;

    setSaving(prev => new Set(prev.add(keyId)));

    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/update-english-translation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          key_id: keyId,
          translation_text: newText
        })
      });

      const data = await response.json();
      if (data.success) {
        // Update local state
        setEnglishTranslations(prev => 
          prev.map(t => t.key_id === keyId ? { ...t, translation_text: newText } : t)
        );
        setEditedTexts(prev => {
          const newMap = new Map(prev);
          newMap.delete(keyId);
          return newMap;
        });
        alert('English translation saved successfully!');
      } else {
        alert('Error saving translation: ' + data.message);
      }
    } catch (error) {
      console.error('Error saving translation:', error);
      alert('Error saving translation');
    } finally {
      setSaving(prev => {
        const newSet = new Set(prev);
        newSet.delete(keyId);
        return newSet;
      });
    }
  };

  const regenerateAllTranslations = async (keyId: number) => {
    const englishText = editedTexts.get(keyId) || 
      englishTranslations.find(t => t.key_id === keyId)?.translation_text;
    
    if (!englishText) {
      alert('No English text found to translate');
      return;
    }

    setRegenerating(prev => new Set(prev.add(keyId)));

    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/regenerate-all-translations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          key_id: keyId,
          english_text: englishText,
          target_languages: languages.map(lang => ({
            id: lang.id,
            code: lang.code,
            name: lang.name
          }))
        })
      });

      const data = await response.json();
      if (data.success) {
        alert(`✅ Successfully regenerated translations for ${data.translations_updated} languages!`);
      } else {
        alert('Error regenerating translations: ' + data.message);
      }
    } catch (error) {
      console.error('Error regenerating translations:', error);
      alert('Error regenerating translations');
    } finally {
      setRegenerating(prev => {
        const newSet = new Set(prev);
        newSet.delete(keyId);
        return newSet;
      });
    }
  };

  const regenerateAllForCategory = async (category: string) => {
    setRegeneratingAll(true);

    try {
      const keysToRegenerate = filteredKeys.filter(key => 
        category === 'all' || key.category === category
      );

      const response = await fetch('http://localhost/aureus-angel-alliance/api/translations/regenerate-category-translations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          category: category,
          key_ids: keysToRegenerate.map(key => key.id),
          target_languages: languages.map(lang => ({
            id: lang.id,
            code: lang.code,
            name: lang.name
          }))
        })
      });

      const data = await response.json();
      if (data.success) {
        alert(`✅ Successfully regenerated ${data.total_translations} translations for ${keysToRegenerate.length} keys!`);
      } else {
        alert('Error regenerating category translations: ' + data.message);
      }
    } catch (error) {
      console.error('Error regenerating category translations:', error);
      alert('Error regenerating category translations');
    } finally {
      setRegeneratingAll(false);
    }
  };

  // Filter and search logic
  const categories = [...new Set(translationKeys.map(key => key.category))];
  
  const filteredKeys = translationKeys.filter(key => {
    const matchesSearch = key.key_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         key.description.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = selectedCategory === 'all' || key.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader2 className="h-8 w-8 animate-spin text-gold" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-charcoal p-6">
      <div className="max-w-7xl mx-auto">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gold mb-2">English Translation Editor</h1>
          <p className="text-white/70">Edit English translations and regenerate all other language translations</p>
        </div>

        {/* Controls */}
        <Card className="mb-6 bg-dark-card border-gold/30">
          <CardContent className="p-6">
            <div className="flex flex-col md:flex-row gap-4 items-center">
              <div className="flex-1">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-white/50 h-4 w-4" />
                  <Input
                    placeholder="Search translation keys..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10 bg-black/50 border-gold/30 text-white"
                  />
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <Filter className="h-4 w-4 text-white/50" />
                <select
                  value={selectedCategory}
                  onChange={(e) => setSelectedCategory(e.target.value)}
                  className="bg-black/50 border border-gold/30 rounded px-3 py-2 text-white"
                >
                  <option value="all">All Categories</option>
                  {categories.map(category => (
                    <option key={category} value={category}>{category}</option>
                  ))}
                </select>
              </div>

              <Button
                onClick={() => regenerateAllForCategory(selectedCategory)}
                disabled={regeneratingAll}
                className="bg-purple-600 hover:bg-purple-700 text-white"
              >
                {regeneratingAll ? (
                  <>
                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    Regenerating All...
                  </>
                ) : (
                  <>
                    <RefreshCw className="h-4 w-4 mr-2" />
                    Regenerate All {selectedCategory !== 'all' ? selectedCategory : ''}
                  </>
                )}
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Translation Keys */}
        <div className="space-y-4">
          {filteredKeys.map(key => {
            const englishTranslation = englishTranslations.find(t => t.key_id === key.id);
            const currentText = editedTexts.get(key.id) || englishTranslation?.translation_text || key.description;
            const hasChanges = editedTexts.has(key.id);

            return (
              <Card key={key.id} className="bg-dark-card border-gold/30">
                <CardHeader className="pb-3">
                  <div className="flex items-center justify-between">
                    <div>
                      <CardTitle className="text-gold text-lg">{key.key_name}</CardTitle>
                      <Badge variant="outline" className="mt-1 border-gold/50 text-gold">
                        {key.category}
                      </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button
                        size="sm"
                        onClick={() => saveEnglishTranslation(key.id)}
                        disabled={!hasChanges || saving.has(key.id)}
                        className="bg-green-600 hover:bg-green-700 text-white"
                      >
                        {saving.has(key.id) ? (
                          <>
                            <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                            Saving...
                          </>
                        ) : (
                          <>
                            <Save className="h-3 w-3 mr-1" />
                            Save
                          </>
                        )}
                      </Button>
                      
                      <Button
                        size="sm"
                        onClick={() => regenerateAllTranslations(key.id)}
                        disabled={regenerating.has(key.id)}
                        className="bg-purple-600 hover:bg-purple-700 text-white"
                      >
                        {regenerating.has(key.id) ? (
                          <>
                            <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                            Regenerating...
                          </>
                        ) : (
                          <>
                            <Wand2 className="h-3 w-3 mr-1" />
                            Regenerate All
                          </>
                        )}
                      </Button>
                    </div>
                  </div>
                </CardHeader>
                
                <CardContent>
                  <div className="space-y-3">
                    <div>
                      <label className="text-sm font-medium text-white/70 block mb-2">
                        English Translation:
                      </label>
                      {currentText.length > 100 ? (
                        <Textarea
                          value={currentText}
                          onChange={(e) => updateEnglishText(key.id, e.target.value)}
                          className="bg-black/50 border-gold/30 text-white min-h-[100px]"
                          placeholder="Enter English translation..."
                        />
                      ) : (
                        <Input
                          value={currentText}
                          onChange={(e) => updateEnglishText(key.id, e.target.value)}
                          className="bg-black/50 border-gold/30 text-white"
                          placeholder="Enter English translation..."
                        />
                      )}
                    </div>
                    
                    {hasChanges && (
                      <Alert className="border-yellow-500 bg-yellow-500/10">
                        <AlertDescription className="text-yellow-400">
                          ⚠️ You have unsaved changes. Click "Save" to update the English translation, then "Regenerate All" to update other languages.
                        </AlertDescription>
                      </Alert>
                    )}
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {filteredKeys.length === 0 && (
          <Card className="bg-dark-card border-gold/30">
            <CardContent className="p-8 text-center">
              <p className="text-white/70">No translation keys found matching your search criteria.</p>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
};

export default EnglishTranslationEditor;
