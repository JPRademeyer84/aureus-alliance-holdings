<?php
// Add Step 3 "Secure USDT Payment" translation keys
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// AI Translation function
function translateWithAI($text, $targetLanguage) {
    $translations = [
        'Spanish' => [
            'Secure USDT Payment' => 'Pago Seguro USDT',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Paga con USDT en la blockchain Polygon usando la billetera SafePal. Todas las transacciones son transparentes y registradas en la cadena.'
        ],
        'French' => [
            'Secure USDT Payment' => 'Paiement USDT Sécurisé',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Payez avec USDT sur la blockchain Polygon en utilisant le portefeuille SafePal. Toutes les transactions sont transparentes et enregistrées sur la chaîne.'
        ],
        'German' => [
            'Secure USDT Payment' => 'Sichere USDT-Zahlung',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Zahlen Sie mit USDT auf der Polygon-Blockchain mit der SafePal-Wallet. Alle Transaktionen sind transparent und werden on-chain aufgezeichnet.'
        ],
        'Portuguese' => [
            'Secure USDT Payment' => 'Pagamento Seguro USDT',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Pague com USDT na blockchain Polygon usando a carteira SafePal. Todas as transações são transparentes e registradas na cadeia.'
        ],
        'Italian' => [
            'Secure USDT Payment' => 'Pagamento USDT Sicuro',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Paga con USDT sulla blockchain Polygon utilizzando il wallet SafePal. Tutte le transazioni sono trasparenti e registrate on-chain.'
        ],
        'Russian' => [
            'Secure USDT Payment' => 'Безопасный Платеж USDT',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Платите USDT в блокчейне Polygon, используя кошелек SafePal. Все транзакции прозрачны и записываются в блокчейне.'
        ],
        'Chinese' => [
            'Secure USDT Payment' => '安全USDT支付',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => '使用SafePal钱包在Polygon区块链上用USDT支付。所有交易都是透明的并记录在链上。'
        ],
        'Japanese' => [
            'Secure USDT Payment' => '安全なUSDT決済',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'SafePalウォレットを使用してPolygonブロックチェーン上でUSDTで支払います。すべての取引は透明でオンチェーンに記録されます。'
        ],
        'Arabic' => [
            'Secure USDT Payment' => 'دفع USDT آمن',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'ادفع بـ USDT على بلوك تشين Polygon باستخدام محفظة SafePal. جميع المعاملات شفافة ومسجلة على السلسلة.'
        ],
        'Ukrainian' => [
            'Secure USDT Payment' => 'Безпечний Платіж USDT',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Платіть USDT в блокчейні Polygon, використовуючи гаманець SafePal. Всі транзакції прозорі та записуються в блокчейні.'
        ],
        'Hindi' => [
            'Secure USDT Payment' => 'सुरक्षित USDT भुगतान',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'SafePal वॉलेट का उपयोग करके Polygon ब्लॉकचेन पर USDT से भुगतान करें। सभी लेनदेन पारदर्शी हैं और ऑन-चेन रिकॉर्ड किए गए हैं।'
        ],
        'Urdu' => [
            'Secure USDT Payment' => 'محفوظ USDT ادائیگی',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'SafePal والیٹ استعمال کرتے ہوئے Polygon بلاک چین پر USDT سے ادائیگی کریں۔ تمام لین دین شفاف ہیں اور آن چین ریکارڈ کیے گئے ہیں۔'
        ],
        'Bengali' => [
            'Secure USDT Payment' => 'নিরাপদ USDT পেমেন্ট',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'SafePal ওয়ালেট ব্যবহার করে Polygon ব্লকচেইনে USDT দিয়ে পেমেন্ট করুন। সমস্ত লেনদেন স্বচ্ছ এবং অন-চেইনে রেকর্ড করা হয়।'
        ],
        'Korean' => [
            'Secure USDT Payment' => '안전한 USDT 결제',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'SafePal 지갑을 사용하여 Polygon 블록체인에서 USDT로 결제하세요. 모든 거래는 투명하고 온체인에 기록됩니다.'
        ],
        'Malay' => [
            'Secure USDT Payment' => 'Pembayaran USDT Selamat',
            'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.' => 'Bayar dengan USDT di blockchain Polygon menggunakan dompet SafePal. Semua transaksi adalah telus dan direkodkan di rantai.'
        ]
    ];
    
    if (isset($translations[$targetLanguage][$text])) {
        return $translations[$targetLanguage][$text];
    }
    
    return $text; // Return original if no translation found
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 3 translation keys to add
    $step3Keys = [
        [
            'key_name' => 'homepage.steps.step3.title',
            'category' => 'homepage',
            'description' => 'Secure USDT Payment'
        ],
        [
            'key_name' => 'homepage.steps.step3.description',
            'category' => 'homepage',
            'description' => 'Pay with USDT on Polygon blockchain using SafePal wallet. All transactions are transparent and recorded on-chain.'
        ]
    ];
    
    $addedKeys = [];
    $skippedKeys = [];
    
    // Add translation keys
    foreach ($step3Keys as $keyData) {
        // Check if key already exists
        $checkQuery = "SELECT id FROM translation_keys WHERE key_name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$keyData['key_name']]);
        
        if ($checkStmt->fetch()) {
            $skippedKeys[] = $keyData['key_name'] . ' (already exists)';
            continue;
        }
        
        // Insert new translation key
        $insertQuery = "INSERT INTO translation_keys (key_name, category, description, created_at) 
                       VALUES (?, ?, ?, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            $keyData['key_name'],
            $keyData['category'],
            $keyData['description']
        ]);
        
        $keyId = $db->lastInsertId();
        $addedKeys[] = [
            'id' => $keyId,
            'key_name' => $keyData['key_name'],
            'description' => $keyData['description']
        ];
        
        // Add English translation (default)
        $englishLangQuery = "SELECT id FROM languages WHERE code = 'en'";
        $englishLangStmt = $db->prepare($englishLangQuery);
        $englishLangStmt->execute();
        $englishLang = $englishLangStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($englishLang) {
            $translationQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                               VALUES (?, ?, ?, TRUE, NOW())";
            $translationStmt = $db->prepare($translationQuery);
            $translationStmt->execute([
                $keyId,
                $englishLang['id'],
                $keyData['description']
            ]);
        }
    }
    
    // Add translations for all other languages
    if (!empty($addedKeys)) {
        $langQuery = "SELECT id, name, code FROM languages WHERE code != 'en' AND is_active = TRUE";
        $langStmt = $db->prepare($langQuery);
        $langStmt->execute();
        $languages = $langStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $translationsAdded = [];
        
        foreach ($languages as $language) {
            foreach ($addedKeys as $key) {
                // Get AI translation
                $translation = translateWithAI($key['description'], $language['name']);
                
                // Insert translation
                $insertQuery = "INSERT INTO translations (key_id, language_id, translation_text, is_approved, created_at) 
                               VALUES (?, ?, ?, TRUE, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$key['id'], $language['id'], $translation]);
                
                $translationsAdded[] = [
                    'key_name' => $key['key_name'],
                    'language' => $language['name'],
                    'language_code' => $language['code'],
                    'original' => $key['description'],
                    'translation' => $translation
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Step 3 translation keys processed successfully',
        'results' => [
            'added_keys' => $addedKeys,
            'skipped_keys' => $skippedKeys,
            'added_count' => count($addedKeys),
            'skipped_count' => count($skippedKeys),
            'translations_added' => isset($translationsAdded) ? count($translationsAdded) : 0
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add Step 3 translation keys',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
