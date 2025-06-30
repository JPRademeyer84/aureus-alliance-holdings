-- Step 3: Create Translations Table
CREATE TABLE IF NOT EXISTS translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_id INT NOT NULL,
    language_id INT NOT NULL,
    translation_text TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (key_id) REFERENCES translation_keys(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_translation (key_id, language_id)
);

-- Create indexes for better performance
CREATE INDEX idx_translations_key_lang ON translations(key_id, language_id);
CREATE INDEX idx_translation_keys_category ON translation_keys(category);
CREATE INDEX idx_languages_active ON languages(is_active);
CREATE INDEX idx_languages_code ON languages(code);
