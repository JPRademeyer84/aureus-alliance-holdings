-- Step 1: Create Languages Table
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    native_name VARCHAR(100) NOT NULL,
    flag_emoji VARCHAR(10) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default languages
INSERT INTO languages (code, name, native_name, flag_emoji, is_active, is_default, sort_order) VALUES
('en', 'English', 'English', '🇺🇸', TRUE, TRUE, 1),
('es', 'Spanish', 'Español', '🇪🇸', TRUE, FALSE, 2),
('fr', 'French', 'Français', '🇫🇷', TRUE, FALSE, 3),
('de', 'German', 'Deutsch', '🇩🇪', TRUE, FALSE, 4),
('pt', 'Portuguese', 'Português', '🇵🇹', TRUE, FALSE, 5),
('it', 'Italian', 'Italiano', '🇮🇹', TRUE, FALSE, 6),
('ru', 'Russian', 'Русский', '🇷🇺', TRUE, FALSE, 7),
('zh', 'Chinese', '中文', '🇨🇳', TRUE, FALSE, 8),
('ja', 'Japanese', '日本語', '🇯🇵', TRUE, FALSE, 9),
('ar', 'Arabic', 'العربية', '🇸🇦', TRUE, FALSE, 10),
('uk', 'Ukrainian', 'Українська', '🇺🇦', TRUE, FALSE, 11),
('hi', 'Hindi', 'हिन्दी', '🇮🇳', TRUE, FALSE, 12),
('ur', 'Urdu', 'اردو', '🇵🇰', TRUE, FALSE, 13),
('bn', 'Bengali', 'বাংলা', '🇧🇩', TRUE, FALSE, 14),
('ko', 'Korean', '한국어', '🇰🇷', TRUE, FALSE, 15),
('ms', 'Malay', 'Bahasa Malaysia', '🇲🇾', TRUE, FALSE, 16);
