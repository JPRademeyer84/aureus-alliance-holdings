<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $language_code = $_GET['language'] ?? 'en';

    // Get language ID
    $lang_query = "SELECT id FROM languages WHERE code = ? AND is_active = TRUE";
    $lang_stmt = $db->prepare($lang_query);
    $lang_stmt->execute([$language_code]);
    $language = $lang_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$language) {
        throw new Exception('Language not found');
    }

    $language_id = $language['id'];

    // Get all translations for this language
    $query = "SELECT tk.key_name, t.translation_text, tk.category
              FROM translation_keys tk
              LEFT JOIN translations t ON tk.id = t.key_id AND t.language_id = ?
              WHERE t.translation_text IS NOT NULL AND t.is_approved = TRUE
              ORDER BY tk.category, tk.key_name";

    $stmt = $db->prepare($query);
    $stmt->execute([$language_id]);

    $translations = [];
    $categories = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['key_name'];
        $text = $row['translation_text'];
        $category = $row['category'];

        $translations[$key] = $text;

        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][$key] = $text;
    }

    // Also create a simple key-value mapping for easy lookup
    $simpleTranslations = [];
    foreach ($translations as $key => $text) {
        // Create both dot notation and original text mappings
        $simpleTranslations[$key] = $text;

        // For backward compatibility, also map common phrases
        if ($key === 'hero.title') $simpleTranslations['Become an Angel Investor'] = $text;
        if ($key === 'hero.invest_now') $simpleTranslations['Invest Now'] = $text;
        if ($key === 'hero.learn_more') $simpleTranslations['Learn More'] = $text;
        if ($key === 'nav.investment') $simpleTranslations['Investment'] = $text;
        if ($key === 'nav.affiliate') $simpleTranslations['Affiliate'] = $text;
        if ($key === 'nav.benefits') $simpleTranslations['Benefits'] = $text;
        if ($key === 'nav.about') $simpleTranslations['About'] = $text;
        if ($key === 'nav.contact') $simpleTranslations['Contact'] = $text;
        if ($key === 'nav.sign_in') $simpleTranslations['Sign In'] = $text;
    }

    echo json_encode([
        'success' => true,
        'language' => $language_code,
        'translations' => $simpleTranslations,
        'structured' => $translations,
        'categories' => $categories,
        'count' => count($translations)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch translations: ' . $e->getMessage()
    ]);
}
?>
