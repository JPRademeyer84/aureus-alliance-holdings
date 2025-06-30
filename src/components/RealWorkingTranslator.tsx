import React, { useState, useEffect } from 'react';
import { Globe, ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface Language {
  code: string;
  name: string;
  flag: string;
}

const languages: Language[] = [
  { code: 'en', name: 'English', flag: '🇺🇸' },
  { code: 'es', name: 'Español', flag: '🇪🇸' },
  { code: 'fr', name: 'Français', flag: '🇫🇷' },
  { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
  { code: 'pt', name: 'Português', flag: '🇵🇹' },
  { code: 'it', name: 'Italiano', flag: '🇮🇹' },
  { code: 'ru', name: 'Русский', flag: '🇷🇺' },
  { code: 'zh', name: '中文', flag: '🇨🇳' },
  { code: 'ja', name: '日本語', flag: '🇯🇵' },
  { code: 'ar', name: 'العربية', flag: '🇸🇦' },
  { code: 'uk', name: 'Українська', flag: '🇺🇦' },
  { code: 'hi', name: 'हिन्दी', flag: '🇮🇳' },
  { code: 'ur', name: 'اردو', flag: '🇵🇰' },
  { code: 'bn', name: 'বাংলা', flag: '🇧🇩' },
  { code: 'ko', name: '한국어', flag: '🇰🇷' },
  { code: 'ms', name: 'Bahasa Malaysia', flag: '🇲🇾' }
];

// Translation dictionary with key phrases
const translations: Record<string, Record<string, string>> = {
  // Hero section
  "Become an Angel Funder": {
    es: "Conviértete en un Financiador Ángel",
    fr: "Devenez un Financeur Providentiel",
    de: "Werden Sie ein Angel-Finanzierer",
    pt: "Torne-se um Financiador Anjo",
    it: "Diventa un Finanziatore Angelo",
    ru: "Станьте Ангелом-Финансистом",
    zh: "成为天使资助者",
    ja: "エンジェル資金提供者になる",
    ar: "كن ممول ملاك",
    uk: "Станьте Ангелом-Фінансистом",
    hi: "एक एंजेल फंडर बनें",
    ur: "ایک فرشتہ فنڈر بنیں",
    bn: "একজন অ্যাঞ্জেল ফান্ডার হন",
    ko: "엔젤 펀더가 되세요",
    ms: "Menjadi Pembiaya Malaikat"
  },
  "Become an Angel Investor": {
    es: "Conviértete en un Financiador Ángel",
    fr: "Devenez un Financeur Providentiel",
    de: "Werden Sie ein Angel-Finanzierer",
    pt: "Torne-se um Financiador Anjo",
    it: "Diventa un Finanziatore Angelo",
    ru: "Станьте Ангелом-Финансистом",
    zh: "成为天使资助者",
    ja: "エンジェル資金提供者になる",
    ar: "كن ممول ملاك",
    uk: "Станьте Ангелом-Фінансистом",
    hi: "एक एंजेल फंडर बनें",
    ur: "ایک فرشتہ فنڈر بنیں",
    bn: "একজন অ্যাঞ্জেল ফান্ডার হন",
    ko: "엔젤 펀더가 되세요",
    ms: "Menjadi Pembiaya Malaikat"
  },
  "Exclusive pre-seed opportunity to fund Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles.": {
    es: "Oportunidad exclusiva de pre-semilla para financiar Aureus Alliance Holdings – combinando minería de oro físico con coleccionables NFT digitales.",
    fr: "Opportunité exclusive de pré-amorçage pour financer Aureus Alliance Holdings – combinant l'extraction d'or physique avec des objets de collection NFT numériques.",
    de: "Exklusive Pre-Seed-Gelegenheit, Aureus Alliance Holdings zu finanzieren – Kombination aus physischem Goldbergbau und digitalen NFT-Sammlerstücken.",
    pt: "Oportunidade exclusiva de pré-semente para financiar Aureus Alliance Holdings – combinando mineração de ouro físico com colecionáveis NFT digitais.",
    it: "Opportunità esclusiva di pre-seed per finanziare Aureus Alliance Holdings – combinando l'estrazione di oro fisico con oggetti da collezione NFT digitali.",
    ru: "Эксклюзивная возможность предпосевного финансирования Aureus Alliance Holdings – сочетание физической добычи золота с цифровыми коллекционными NFT.",
    zh: "投资Aureus Alliance Holdings的独家预种子机会——将物理黄金开采与数字NFT收藏品相结合。",
    ja: "Aureus Alliance Holdingsへの独占的なプレシード投資機会 – 物理的な金採掘とデジタルNFTコレクティブルを組み合わせ。",
    ar: "فرصة حصرية للاستثمار في مرحلة ما قبل البذور في Aureus Alliance Holdings – الجمع بين تعدين الذهب الفعلي والمقتنيات الرقمية NFT.",
    uk: "Ексклюзивна можливість передпосівних інвестицій в Aureus Alliance Holdings – поєднання фізичного видобутку золота з цифровими колекційними NFT.",
    hi: "Aureus Alliance Holdings में निवेश करने का विशेष प्री-सीड अवसर – भौतिक सोने की खनन को डिजिटल NFT संग्रहणीय वस्तुओं के साथ मिलाना।",
    ur: "Aureus Alliance Holdings میں سرمایہ کاری کے لیے خصوصی پری سیڈ موقع – جسمانی سونے کی کان کنی کو ڈیجیٹل NFT جمع کرنے والی اشیاء کے ساتھ ملانا۔",
    bn: "Aureus Alliance Holdings-এ বিনিয়োগের জন্য একচেটিয়া প্রি-সিড সুযোগ – ভৌত সোনার খনন এবং ডিজিটাল NFT সংগ্রহযোগ্য বস্তুর সমন্বয়।",
    ko: "Aureus Alliance Holdings에 투자할 수 있는 독점적인 프리시드 기회 – 물리적 금 채굴과 디지털 NFT 수집품을 결합합니다.",
    ms: "Peluang pra-benih eksklusif untuk melabur dalam Aureus Alliance Holdings – menggabungkan perlombongan emas fizikal dengan koleksi NFT digital."
  },
  "Yield on Participation": {
    es: "Rendimiento del Financiamiento",
    fr: "Rendement sur Financement",
    de: "Rendite auf Finanzierung",
    pt: "Rendimento do Financiamento",
    it: "Rendimento sul Finanziamento",
    ru: "Доходность Финансирования",
    zh: "投资收益率",
    ja: "投資利回り",
    ar: "عائد الاستثمار",
    uk: "Дохідність Інвестицій",
    hi: "निवेश पर प्रतिफल",
    ur: "سرمایہ کاری پر منافع",
    bn: "বিনিয়োগের উপর ফলন",
    ko: "투자 수익률",
    ms: "Hasil atas Pelaburan"
  },
  "Annual per Share": {
    es: "Anual por Acción",
    fr: "Annuel par Action",
    de: "Jährlich pro Aktie",
    pt: "Anual por Ação",
    it: "Annuale per Azione",
    ru: "Годовой на Акцию",
    zh: "每股年收益",
    ja: "年間1株当たり",
    ar: "سنوي لكل سهم",
    uk: "Річний на Акцію",
    hi: "प्रति शेयर वार्षिक",
    ur: "فی شیئر سالانہ",
    bn: "প্রতি শেয়ার বার্ষিক",
    ko: "주당 연간",
    ms: "Tahunan setiap Saham"
  },
  "Affiliate Commission": {
    es: "Comisión de Afiliado",
    fr: "Commission d'Affiliation",
    de: "Affiliate-Provision",
    pt: "Comissão de Afiliado",
    it: "Commissione di Affiliazione",
    ru: "Партнерская Комиссия",
    zh: "联盟佣金",
    ja: "アフィリエイト手数料",
    ar: "عمولة الشراكة",
    uk: "Партнерська Комісія",
    hi: "सहयोगी कमीशन",
    ur: "ملحق کمیشن",
    bn: "অধিভুক্ত কমিশন",
    ko: "제휴 수수료",
    ms: "Komisen Gabungan"
  },
  "NFT Presale Launch": {
    es: "Lanzamiento de Preventa NFT",
    fr: "Lancement de Prévente NFT",
    de: "NFT-Vorverkaufsstart",
    pt: "Lançamento de Pré-venda NFT",
    it: "Lancio Prevendita NFT",
    ru: "Запуск Предпродажи NFT",
    zh: "NFT预售启动",
    ja: "NFTプレセール開始",
    ar: "إطلاق البيع المسبق NFT",
    uk: "Запуск Передпродажу NFT",
    hi: "NFT प्रीसेल लॉन्च",
    ur: "NFT پری سیل لانچ",
    bn: "NFT প্রিসেল লঞ্চ",
    ko: "NFT 프리세일 출시",
    ms: "Pelancaran Prajual NFT"
  },
  "in the Future of Digital Gold": {
    es: "en el Futuro del Oro Digital",
    fr: "dans l'Avenir de l'Or Numérique",
    de: "in der Zukunft des Digitalen Goldes",
    pt: "no Futuro do Ouro Digital",
    it: "nel Futuro dell'Oro Digitale",
    ru: "в Будущем Цифрового Золота",
    zh: "数字黄金的未来",
    ja: "デジタルゴールドの未来",
    ar: "في مستقبل الذهب الرقمي",
    uk: "в Майбутньому Цифрового Золота",
    hi: "डिजिटल गोल्ड के भविष्य में",
    ur: "ڈیجیٹل سونے کے مستقبل میں",
    bn: "ডিজিটাল সোনার ভবিষ্যতে",
    ko: "디지털 골드의 미래",
    ms: "dalam Masa Depan Emas Digital"
  },
  "Participate Now": {
    es: "Financiar Ahora",
    fr: "Financer Maintenant",
    de: "Jetzt Finanzieren",
    pt: "Financiar Agora",
    it: "Finanzia Ora",
    ru: "Финансировать Сейчас",
    zh: "立即资助",
    ja: "今すぐ資金提供",
    ar: "مول الآن",
    uk: "Фінансувати Зараз",
    hi: "अभी फंड करें",
    ur: "ابھی فنڈ کریں",
    bn: "এখনই ফান্ড করুন",
    ko: "지금 펀딩하세요",
    ms: "Biayai Sekarang"
  },
  "Fund Now": {
    es: "Financiar Ahora",
    fr: "Financer Maintenant",
    de: "Jetzt Finanzieren",
    pt: "Financiar Agora",
    it: "Finanzia Ora",
    ru: "Финансировать Сейчас",
    zh: "立即资助",
    ja: "今すぐ資金提供",
    ar: "مول الآن",
    uk: "Фінансувати Зараз",
    hi: "अभी फंड करें",
    ur: "ابھی فنڈ کریں",
    bn: "এখনই ফান্ড করুন",
    ko: "지금 펀딩하세요",
    ms: "Biayai Sekarang"
  },
  "Learn More": {
    es: "Aprende Más",
    fr: "En Savoir Plus",
    de: "Mehr Erfahren",
    pt: "Saiba Mais",
    it: "Scopri di Più",
    ru: "Узнать Больше",
    zh: "了解更多",
    ja: "詳細を見る",
    ar: "اعرف المزيد",
    uk: "Дізнатися Більше",
    hi: "और जानें",
    ur: "مزید جانیں",
    bn: "আরও জানুন",
    ko: "더 알아보기",
    ms: "Ketahui Lebih Lanjut"
  },
  "Participation": {
    es: "Financiamiento",
    fr: "Financement",
    de: "Finanzierung",
    pt: "Financiamento",
    it: "Finanziamento",
    ru: "Финансирование",
    zh: "资助",
    ja: "資金調達",
    ar: "تمويل",
    uk: "Фінансування",
    hi: "फंडिंग",
    ur: "فنڈنگ",
    bn: "ফান্ডিং",
    ko: "펀딩",
    ms: "Pembiayaan"
  },
  "Affiliate": {
    es: "Afiliado",
    fr: "Affilié",
    de: "Partner",
    pt: "Afiliado",
    it: "Affiliato",
    ru: "Партнер",
    zh: "联盟",
    ja: "アフィリエイト",
    ar: "شريك",
    uk: "Партнер",
    hi: "सहयोगी",
    ur: "ملحق",
    bn: "অধিভুক্ত",
    ko: "제휴",
    ms: "Gabungan"
  },
  "Benefits": {
    es: "Beneficios",
    fr: "Avantages",
    de: "Vorteile",
    pt: "Benefícios",
    it: "Vantaggi",
    ru: "Преимущества",
    zh: "好处",
    ja: "メリット",
    ar: "فوائد",
    uk: "Переваги",
    hi: "लाभ",
    ur: "فوائد",
    bn: "সুবিধা",
    ko: "혜택",
    ms: "Faedah"
  },
  "About": {
    es: "Acerca de",
    fr: "À Propos",
    de: "Über Uns",
    pt: "Sobre",
    it: "Chi Siamo",
    ru: "О Нас",
    zh: "关于",
    ja: "について",
    ar: "حول",
    uk: "Про Нас",
    hi: "के बारे में",
    ur: "کے بارے میں",
    bn: "সম্পর্কে",
    ko: "소개",
    ms: "Tentang"
  },
  "Contact": {
    es: "Contacto",
    fr: "Contact",
    de: "Kontakt",
    pt: "Contato",
    it: "Contatto",
    ru: "Контакт",
    zh: "联系",
    ja: "お問い合わせ",
    ar: "اتصال",
    uk: "Контакт",
    hi: "संपर्क",
    ur: "رابطہ",
    bn: "যোগাযোগ",
    ko: "연락처",
    ms: "Hubungi"
  },
  "Sign In": {
    es: "Iniciar Sesión",
    fr: "Se Connecter",
    de: "Anmelden",
    pt: "Entrar",
    it: "Accedi",
    ru: "Войти",
    zh: "登录",
    ja: "ログイン",
    ar: "تسجيل الدخول",
    uk: "Увійти",
    hi: "साइन इन करें",
    ur: "سائن ان کریں",
    bn: "সাইন ইন করুন",
    ko: "로그인",
    ms: "Log Masuk"
  },
  // How It Works section
  "How Angel Funding Works": {
    es: "Cómo Funciona el Financiamiento Ángel",
    fr: "Comment Fonctionne le Financement Providentiel",
    de: "Wie Angel-Finanzierung Funktioniert",
    pt: "Como Funciona o Financiamento Anjo",
    it: "Come Funziona il Finanziamento Angelo",
    ru: "Как Работает Ангельское Финансирование",
    zh: "天使资助如何运作",
    ja: "エンジェル資金調達の仕組み",
    ar: "كيف يعمل التمويل الملائكي",
    uk: "Як Працює Ангельське Фінансування",
    hi: "एंजेल फंडिंग कैसे काम करती है",
    ur: "فرشتہ فنڈنگ کیسے کام کرتی ہے",
    bn: "অ্যাঞ্জেল ফান্ডিং কীভাবে কাজ করে",
    ko: "엔젤 펀딩이 어떻게 작동하는지",
    ms: "Bagaimana Pembiayaan Malaikat Berfungsi"
  },
  "Join the Aureus Angel Alliance in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership.": {
    es: "Únete a la Alianza Ángel Aureus en 6 simples pasos. Sin procesos complicados, sin tarifas ocultas, solo un camino directo hacia la propiedad de oro digital.",
    fr: "Rejoignez l'Alliance Providentielle Aureus en 6 étapes simples. Pas de processus compliqués, pas de frais cachés - juste un chemin direct vers la propriété d'or numérique.",
    de: "Treten Sie der Aureus Angel Alliance in 6 einfachen Schritten bei. Keine komplizierten Prozesse, keine versteckten Gebühren - nur ein direkter Weg zum digitalen Goldbesitz.",
    pt: "Junte-se à Aliança Anjo Aureus em 6 passos simples. Sem processos complicados, sem taxas ocultas - apenas um caminho direto para a propriedade de ouro digital.",
    it: "Unisciti all'Alleanza Angelo Aureus in 6 semplici passaggi. Nessun processo complicato, nessuna commissione nascosta - solo un percorso diretto verso la proprietà dell'oro digitale.",
    ru: "Присоединяйтесь к Альянсу Ангелов Aureus за 6 простых шагов. Никаких сложных процессов, никаких скрытых комиссий - только прямой путь к владению цифровым золотом.",
    zh: "通过6个简单步骤加入Aureus天使联盟。没有复杂的流程，没有隐藏费用——只是通往数字黄金所有权的直接路径。",
    ja: "6つの簡単なステップでAureus Alliance Holdingsに参加しましょう。複雑なプロセスも隠れた手数料もありません - デジタルゴールド所有への直接的な道のりです。",
    ar: "انضم إلى Aureus Alliance Holdings في 6 خطوات بسيطة. لا توجد عمليات معقدة، لا توجد رسوم مخفية - فقط طريق مباشر لملكية الذهب الرقمي.",
    uk: "Приєднуйтесь до Aureus Alliance Holdings за 6 простих кроків. Ніяких складних процесів, ніяких прихованих комісій - лише прямий шлях до володіння цифровим золотом.",
    hi: "6 सरल चरणों में Aureus Alliance Holdings में शामिल हों। कोई जटिल प्रक्रिया नहीं, कोई छुपी हुई फीस नहीं - बस डिजिटल सोने के स्वामित्व का सीधा रास्ता।",
    ur: "6 آسان قدموں میں Aureus Alliance Holdings میں شامل ہوں۔ کوئی پیچیدہ عمل نہیں، کوئی چھپی ہوئی فیس نہیں - صرف ڈیجیٹل سونے کی ملکیت کا سیدھا راستہ۔",
    bn: "6টি সহজ ধাপে Aureus Alliance Holdings-এ যোগ দিন। কোনো জটিল প্রক্রিয়া নেই, কোনো লুকানো ফি নেই - শুধু ডিজিটাল সোনার মালিকানার সরাসরি পথ।",
    ko: "6가지 간단한 단계로 Aureus Angel Alliance에 가입하세요. 복잡한 과정도, 숨겨진 수수료도 없습니다 - 디지털 금 소유권으로 가는 직접적인 길입니다.",
    ms: "Sertai Aureus Angel Alliance dalam 6 langkah mudah. Tiada proses rumit, tiada yuran tersembunyi - hanya laluan terus kepada pemilikan emas digital."
  },
  "Create Your Account": {
    es: "Crea Tu Cuenta",
    fr: "Créez Votre Compte",
    de: "Erstellen Sie Ihr Konto",
    pt: "Crie Sua Conta",
    it: "Crea il Tuo Account",
    ru: "Создайте Свой Аккаунт",
    zh: "创建您的账户",
    ja: "アカウントを作成",
    ar: "أنشئ حسابك",
    uk: "Створіть Свій Обліковий Запис",
    hi: "अपना खाता बनाएं",
    ur: "اپنا اکاؤنٹ بنائیں",
    bn: "আপনার অ্যাকাউন্ট তৈরি করুন",
    ko: "계정 만들기",
    ms: "Cipta Akaun Anda"
  },
  "Choose Your NFT Package": {
    es: "Elige Tu Paquete NFT",
    fr: "Choisissez Votre Package NFT",
    de: "Wählen Sie Ihr NFT-Paket",
    pt: "Escolha Seu Pacote NFT",
    it: "Scegli il Tuo Pacchetto NFT",
    ru: "Выберите Свой NFT Пакет",
    zh: "选择您的NFT套餐",
    ja: "NFTパッケージを選択",
    ar: "اختر حزمة NFT الخاصة بك",
    uk: "Оберіть Свій NFT Пакет",
    hi: "अपना NFT पैकेज चुनें",
    ur: "اپنا NFT پیکج منتخب کریں",
    bn: "আপনার NFT প্যাকেজ বেছে নিন",
    ko: "NFT 패키지 선택",
    ms: "Pilih Pakej NFT Anda"
  },
  "Secure USDT Payment": {
    es: "Pago Seguro con USDT",
    fr: "Paiement USDT Sécurisé",
    de: "Sichere USDT-Zahlung",
    pt: "Pagamento Seguro com USDT",
    it: "Pagamento Sicuro USDT",
    ru: "Безопасный Платеж USDT",
    zh: "安全的USDT支付",
    ja: "安全なUSDT支払い",
    ar: "دفع USDT آمن",
    uk: "Безпечний Платіж USDT",
    hi: "सुरक्षित USDT भुगतान",
    ur: "محفوظ USDT ادائیگی",
    bn: "নিরাপদ USDT পেমেন্ট",
    ko: "안전한 USDT 결제",
    ms: "Pembayaran USDT Selamat"
  },
  "Earn Commissions": {
    es: "Gana Comisiones",
    fr: "Gagnez des Commissions",
    de: "Verdienen Sie Provisionen",
    pt: "Ganhe Comissões",
    it: "Guadagna Commissioni",
    ru: "Зарабатывайте Комиссии",
    zh: "赚取佣金",
    ja: "手数料を稼ぐ",
    ar: "اكسب العمولات",
    uk: "Заробляйте Комісії",
    hi: "कमीशन कमाएं",
    ur: "کمیشن کمائیں",
    bn: "কমিশন অর্জন করুন",
    ko: "수수료 획득",
    ms: "Peroleh Komisen"
  },
  "180-Day ROI Period": {
    es: "Período de ROI de 180 Días",
    fr: "Période de ROI de 180 Jours",
    de: "180-Tage ROI-Zeitraum",
    pt: "Período de ROI de 180 Dias",
    it: "Periodo ROI di 180 Giorni",
    ru: "180-дневный Период ROI",
    zh: "180天投资回报期",
    ja: "180日間のROI期間",
    ar: "فترة عائد الاستثمار 180 يوم",
    uk: "180-денний Період ROI",
    hi: "180-दिन की ROI अवधि",
    ur: "180 دن کا ROI دورانیہ",
    bn: "180-দিনের ROI সময়কাল",
    ko: "180일 ROI 기간",
    ms: "Tempoh ROI 180 Hari"
  },
  "Receive Your Returns": {
    es: "Recibe Tus Retornos",
    fr: "Recevez Vos Retours",
    de: "Erhalten Sie Ihre Renditen",
    pt: "Receba Seus Retornos",
    it: "Ricevi i Tuoi Ritorni",
    ru: "Получите Свои Доходы",
    zh: "获得您的回报",
    ja: "リターンを受け取る",
    ar: "احصل على عوائدك",
    uk: "Отримайте Свої Доходи",
    hi: "अपना रिटर्न प्राप्त करें",
    ur: "اپنا منافع حاصل کریں",
    bn: "আপনার রিটার্ন পান",
    ko: "수익 받기",
    ms: "Terima Pulangan Anda"
  },
  // Additional common phrases
  "Start Participating Now": {
    es: "Comienza a Financiar Ahora",
    fr: "Commencez à Financer Maintenant",
    de: "Jetzt Finanzieren Beginnen",
    pt: "Comece a Financiar Agora",
    it: "Inizia a Finanziare Ora",
    ru: "Начните Финансировать Сейчас",
    zh: "立即开始资助",
    ja: "今すぐ資金提供を始める",
    ar: "ابدأ التمويل الآن",
    uk: "Почніть Фінансувати Зараз",
    hi: "अभी फंडिंग शुरू करें",
    ur: "ابھی فنڈنگ شروع کریں",
    bn: "এখনই ফান্ডিং শুরু করুন",
    ko: "지금 펀딩 시작",
    ms: "Mula Membiayai Sekarang"
  },
  "Start Funding Now": {
    es: "Comienza a Financiar Ahora",
    fr: "Commencez à Financer Maintenant",
    de: "Jetzt Finanzieren Beginnen",
    pt: "Comece a Financiar Agora",
    it: "Inizia a Finanziare Ora",
    ru: "Начните Финансировать Сейчас",
    zh: "立即开始资助",
    ja: "今すぐ資金提供を始める",
    ar: "ابدأ التمويل الآن",
    uk: "Почніть Фінансувати Зараз",
    hi: "अभी फंडिंग शुरू करें",
    ur: "ابھی فنڈنگ شروع کریں",
    bn: "এখনই ফান্ডিং শুরু করুন",
    ko: "지금 펀딩 시작",
    ms: "Mula Membiayai Sekarang"
  },
  "View Participation Packages": {
    es: "Ver Paquetes de Financiamiento",
    fr: "Voir les Packages de Financement",
    de: "Finanzierungspakete Anzeigen",
    pt: "Ver Pacotes de Financiamento",
    it: "Visualizza Pacchetti di Finanziamento",
    ru: "Просмотреть Пакеты Финансирования",
    zh: "查看资助套餐",
    ja: "資金調達パッケージを見る",
    ar: "عرض حزم التمويل",
    uk: "Переглянути Пакети Фінансування",
    hi: "फंडिंग पैकेज देखें",
    ur: "فنڈنگ کے پیکج دیکھیں",
    bn: "ফান্ডিং প্যাকেজ দেখুন",
    ko: "펀딩 패키지 보기",
    ms: "Lihat Pakej Pembiayaan"
  },
  "Ready to Become an Angel Funder?": {
    es: "¿Listo para Convertirte en un Financiador Ángel?",
    fr: "Prêt à Devenir un Financeur Providentiel?",
    de: "Bereit, ein Angel-Finanzierer zu werden?",
    pt: "Pronto para se Tornar um Financiador Anjo?",
    it: "Pronto a Diventare un Finanziatore Angelo?",
    ru: "Готовы Стать Ангелом-Финансистом?",
    zh: "准备成为天使资助者了吗？",
    ja: "エンジェル資金提供者になる準備はできましたか？",
    ar: "هل أنت مستعد لتصبح ممول ملاك؟",
    uk: "Готові Стати Ангелом-Фінансистом?",
    hi: "एक एंजेल फंडर बनने के लिए तैयार हैं?",
    ur: "کیا آپ فرشتہ فنڈر بننے کے لیے تیار ہیں؟",
    bn: "একজন অ্যাঞ্জেল ফান্ডার হতে প্রস্তুত?",
    ko: "엔젤 펀더가 될 준비가 되셨나요?",
    ms: "Bersedia untuk Menjadi Pembiaya Malaikat?"
  },
  // Benefits section
  "Exclusive Angel Funder Benefits": {
    es: "Beneficios Exclusivos del Financiador Ángel",
    fr: "Avantages Exclusifs du Financeur Providentiel",
    de: "Exklusive Angel-Finanzierer Vorteile",
    pt: "Benefícios Exclusivos do Financiador Anjo",
    it: "Vantaggi Esclusivi del Finanziatore Angelo",
    ru: "Эксклюзивные Преимущества Ангела-Финансиста",
    zh: "专属天使资助者福利",
    ja: "独占的エンジェル資金提供者特典",
    ar: "مزايا حصرية للممول الملاك",
    uk: "Ексклюзивні Переваги Ангела-Фінансиста",
    hi: "विशेष एंजेल फंडर लाभ",
    ur: "خصوصی فرشتہ فنڈر فوائد",
    bn: "একচেটিয়া অ্যাঞ্জেল ফান্ডার সুবিধা",
    ko: "독점적인 엔젤 펀더 혜택",
    ms: "Faedah Eksklusif Pembiaya Malaikat"
  },
  "Limited Offer": {
    es: "Oferta Limitada",
    fr: "Offre Limitée",
    de: "Begrenztes Angebot",
    pt: "Oferta Limitada",
    it: "Offerta Limitata",
    ru: "Ограниченное Предложение",
    zh: "限时优惠",
    ja: "限定オファー",
    ar: "عرض محدود",
    uk: "Обмежена Пропозиція",
    hi: "सीमित प्रस्ताव",
    ur: "محدود پیشکش",
    bn: "সীমিত অফার",
    ko: "한정 제안",
    ms: "Tawaran Terhad"
  },
  "NFT Early Access": {
    es: "Acceso Temprano a NFT",
    fr: "Accès Anticipé aux NFT",
    de: "NFT Früher Zugang",
    pt: "Acesso Antecipado a NFT",
    it: "Accesso Anticipato agli NFT",
    ru: "Ранний Доступ к NFT",
    zh: "NFT早期访问",
    ja: "NFT早期アクセス",
    ar: "الوصول المبكر إلى NFT",
    uk: "Ранній Доступ до NFT",
    hi: "NFT प्रारंभिक पहुंच",
    ur: "NFT ابتدائی رسائی",
    bn: "NFT প্রাথমিক অ্যাক্সেস",
    ko: "NFT 조기 액세스",
    ms: "Akses Awal NFT"
  },
  "Gold Mine Dividends": {
    es: "Dividendos de Mina de Oro",
    fr: "Dividendes de Mine d'Or",
    de: "Goldminen-Dividenden",
    pt: "Dividendos da Mina de Ouro",
    it: "Dividendi della Miniera d'Oro",
    ru: "Дивиденды Золотой Шахты",
    zh: "金矿股息",
    ja: "金鉱配当",
    ar: "أرباح منجم الذهب",
    uk: "Дивіденди Золотої Шахти",
    hi: "सोने की खान लाभांश",
    ur: "سونے کی کان منافع",
    bn: "সোনার খনি লভ্যাংশ",
    ko: "금광 배당금",
    ms: "Dividen Lombong Emas"
  },
  "Affiliate Program": {
    es: "Programa de Afiliados",
    fr: "Programme d'Affiliation",
    de: "Affiliate-Programm",
    pt: "Programa de Afiliados",
    it: "Programma di Affiliazione",
    ru: "Партнерская Программа",
    zh: "联盟计划",
    ja: "アフィリエイトプログラム",
    ar: "برنامج الشراكة",
    uk: "Партнерська Програма",
    hi: "सहयोगी कार्यक्रम",
    ur: "ملحق پروگرام",
    bn: "অধিভুক্ত প্রোগ্রাম",
    ko: "제휴 프로그램",
    ms: "Program Gabungan"
  },
  "Gaming Integration": {
    es: "Integración de Juegos",
    fr: "Intégration de Jeux",
    de: "Gaming-Integration",
    pt: "Integração de Jogos",
    it: "Integrazione Gaming",
    ru: "Игровая Интеграция",
    zh: "游戏集成",
    ja: "ゲーミング統合",
    ar: "تكامل الألعاب",
    uk: "Ігрова Інтеграція",
    hi: "गेमिंग एकीकरण",
    ur: "گیمنگ انضمام",
    bn: "গেমিং ইন্টিগ্রেশন",
    ko: "게임 통합",
    ms: "Integrasi Permainan"
  },
  // Footer content
  "The future of gold mining meets blockchain innovation, NFT collectibles, and immersive gaming.": {
    es: "El futuro de la minería de oro se encuentra con la innovación blockchain, los coleccionables NFT y los juegos inmersivos.",
    fr: "L'avenir de l'extraction d'or rencontre l'innovation blockchain, les objets de collection NFT et les jeux immersifs.",
    de: "Die Zukunft des Goldbergbaus trifft auf Blockchain-Innovation, NFT-Sammlerstücke und immersive Spiele.",
    pt: "O futuro da mineração de ouro encontra a inovação blockchain, colecionáveis NFT e jogos imersivos.",
    it: "Il futuro dell'estrazione dell'oro incontra l'innovazione blockchain, i collezionabili NFT e i giochi immersivi.",
    ru: "Будущее добычи золота встречается с блокчейн-инновациями, коллекционными NFT и захватывающими играми.",
    zh: "黄金开采的未来与区块链创新、NFT收藏品和沉浸式游戏相遇。",
    ja: "金採掘の未来がブロックチェーンイノベーション、NFTコレクティブル、没入型ゲームと出会います。",
    ar: "مستقبل تعدين الذهب يلتقي مع ابتكار البلوك تشين ومقتنيات NFT والألعاب الغامرة.",
    uk: "Майбутнє видобутку золота зустрічається з блокчейн-інноваціями, колекційними NFT та захоплюючими іграми.",
    hi: "सोने की खनन का भविष्य ब्लॉकचेन नवाचार, NFT संग्रहणीय वस्तुओं और इमर्सिव गेमिंग से मिलता है।",
    ur: "سونے کی کان کنی کا مستقبل بلاک چین اختراع، NFT جمع کرنے والی اشیاء، اور غامر گیمنگ سے ملتا ہے۔",
    bn: "সোনার খনির ভবিষ্যত ব্লকচেইন উদ্ভাবন, NFT সংগ্রহযোগ্য বস্তু এবং নিমজ্জনকারী গেমিংয়ের সাথে মিলিত হয়।",
    ko: "금 채굴의 미래가 블록체인 혁신, NFT 수집품, 몰입형 게임과 만납니다.",
    ms: "Masa depan perlombongan emas bertemu dengan inovasi blockchain, koleksi NFT, dan permainan yang mengasyikkan."
  },
  "Quick Links": {
    es: "Enlaces Rápidos",
    fr: "Liens Rapides",
    de: "Schnelle Links",
    pt: "Links Rápidos",
    it: "Link Rapidi",
    ru: "Быстрые Ссылки",
    zh: "快速链接",
    ja: "クイックリンク",
    ar: "روابط سريعة",
    uk: "Швидкі Посилання",
    hi: "त्वरित लिंक",
    ur: "فوری لنکس",
    bn: "দ্রুত লিঙ্ক",
    ko: "빠른 링크",
    ms: "Pautan Pantas"
  },
  "Contact Us": {
    es: "Contáctanos",
    fr: "Contactez-nous",
    de: "Kontaktieren Sie uns",
    pt: "Entre em Contato",
    it: "Contattaci",
    ru: "Свяжитесь с Нами",
    zh: "联系我们",
    ja: "お問い合わせ",
    ar: "اتصل بنا",
    uk: "Зв'яжіться з Нами",
    hi: "हमसे संपर्क करें",
    ur: "ہم سے رابطہ کریں",
    bn: "আমাদের সাথে যোগাযোগ করুন",
    ko: "문의하기",
    ms: "Hubungi Kami"
  },
  "For funding inquiries:": {
    es: "Para consultas de financiamiento:",
    fr: "Pour les demandes de financement:",
    de: "Für Finanzierungsanfragen:",
    pt: "Para consultas de financiamento:",
    it: "Per richieste di finanziamento:",
    ru: "По вопросам финансирования:",
    zh: "资助咨询：",
    ja: "資金調達に関するお問い合わせ：",
    ar: "لاستفسارات التمويل:",
    uk: "З питань фінансування:",
    hi: "फंडिंग पूछताछ के लिए:",
    ur: "فنڈنگ کی انکوائری کے لیے:",
    bn: "ফান্ডিং অনুসন্ধানের জন্য:",
    ko: "펀딩 문의:",
    ms: "Untuk pertanyaan pembiayaan:"
  },
  "All rights reserved.": {
    es: "Todos los derechos reservados.",
    fr: "Tous droits réservés.",
    de: "Alle Rechte vorbehalten.",
    pt: "Todos os direitos reservados.",
    it: "Tutti i diritti riservati.",
    ru: "Все права защищены.",
    zh: "版权所有。",
    ja: "全著作権所有。",
    ar: "جميع الحقوق محفوظة.",
    uk: "Всі права захищені.",
    hi: "सभी अधिकार सुरक्षित।",
    ur: "تمام حقوق محفوظ ہیں۔",
    bn: "সমস্ত অধিকার সংরক্ষিত।",
    ko: "모든 권리 보유.",
    ms: "Hak cipta terpelihara."
  },
  // Common words that appear frequently
  "and": {
    es: "y", fr: "et", de: "und", pt: "e", it: "e", ru: "и", zh: "和", ja: "と", ar: "و", uk: "і", hi: "और", ur: "اور", bn: "এবং", ko: "그리고", ms: "dan"
  },
  "the": {
    es: "el", fr: "le", de: "der", pt: "o", it: "il", ru: "в", zh: "这", ja: "その", ar: "ال", uk: "в", hi: "यह", ur: "یہ", bn: "এই", ko: "그", ms: "yang"
  },
  "with": {
    es: "con", fr: "avec", de: "mit", pt: "com", it: "con", ru: "с", zh: "与", ja: "と", ar: "مع", uk: "з", hi: "के साथ", ur: "کے ساتھ", bn: "সাথে", ko: "와", ms: "dengan"
  },
  "for": {
    es: "para", fr: "pour", de: "für", pt: "para", it: "per", ru: "для", zh: "为", ja: "のため", ar: "لـ", uk: "для", hi: "के लिए", ur: "کے لیے", bn: "জন্য", ko: "위한", ms: "untuk"
  },
  "your": {
    es: "tu", fr: "votre", de: "Ihr", pt: "seu", it: "tuo", ru: "ваш", zh: "你的", ja: "あなたの", ar: "الخاص بك", uk: "ваш", hi: "आपका", ur: "آپ کا", bn: "আপনার", ko: "당신의", ms: "anda"
  },
  "get": {
    es: "obtener", fr: "obtenir", de: "erhalten", pt: "obter", it: "ottenere", ru: "получить", zh: "获得", ja: "取得", ar: "احصل", uk: "отримати", hi: "प्राप्त करें", ur: "حاصل کریں", bn: "পান", ko: "얻다", ms: "dapatkan"
  },
  "start": {
    es: "comenzar", fr: "commencer", de: "beginnen", pt: "começar", it: "iniziare", ru: "начать", zh: "开始", ja: "開始", ar: "ابدأ", uk: "почати", hi: "शुरू करें", ur: "شروع کریں", bn: "শুরু করুন", ko: "시작", ms: "mula"
  },
  "earn": {
    es: "ganar", fr: "gagner", de: "verdienen", pt: "ganhar", it: "guadagnare", ru: "зарабатывать", zh: "赚取", ja: "稼ぐ", ar: "اكسب", uk: "заробляти", hi: "कमाएं", ur: "کمائیں", bn: "অর্জন করুন", ko: "벌다", ms: "peroleh"
  },
  "share": {
    es: "compartir", fr: "partager", de: "teilen", pt: "compartilhar", it: "condividere", ru: "делиться", zh: "分享", ja: "共有", ar: "شارك", uk: "ділитися", hi: "साझा करें", ur: "شیئر کریں", bn: "শেয়ার করুন", ko: "공유", ms: "kongsi"
  },
  "receive": {
    es: "recibir", fr: "recevoir", de: "erhalten", pt: "receber", it: "ricevere", ru: "получать", zh: "接收", ja: "受け取る", ar: "استقبل", uk: "отримувати", hi: "प्राप्त करें", ur: "وصول کریں", bn: "গ্রহণ করুন", ko: "받다", ms: "terima"
  },
  "exclusive": {
    es: "exclusivo", fr: "exclusif", de: "exklusiv", pt: "exclusivo", it: "esclusivo", ru: "эксклюзивный", zh: "独家", ja: "独占的", ar: "حصري", uk: "ексклюзивний", hi: "विशेष", ur: "خصوصی", bn: "একচেটিয়া", ko: "독점적", ms: "eksklusif"
  },
  "opportunity": {
    es: "oportunidad", fr: "opportunité", de: "Gelegenheit", pt: "oportunidade", it: "opportunità", ru: "возможность", zh: "机会", ja: "機会", ar: "فرصة", uk: "можливість", hi: "अवसर", ur: "موقع", bn: "সুযোগ", ko: "기회", ms: "peluang"
  },
  "digital": {
    es: "digital", fr: "numérique", de: "digital", pt: "digital", it: "digitale", ru: "цифровой", zh: "数字", ja: "デジタル", ar: "رقمي", uk: "цифровий", hi: "डिजिटल", ur: "ڈیجیٹل", bn: "ডিজিটাল", ko: "디지털", ms: "digital"
  },
  "gold": {
    es: "oro", fr: "or", de: "Gold", pt: "ouro", it: "oro", ru: "золото", zh: "黄金", ja: "金", ar: "ذهب", uk: "золото", hi: "सोना", ur: "سونا", bn: "সোনা", ko: "금", ms: "emas"
  },
  "mining": {
    es: "minería", fr: "extraction", de: "Bergbau", pt: "mineração", it: "estrazione", ru: "добыча", zh: "采矿", ja: "採掘", ar: "تعدين", uk: "видобуток", hi: "खनन", ur: "کان کنی", bn: "খনন", ko: "채굴", ms: "perlombongan"
  },
  // Benefit descriptions
  "As an early supporter of Aureus Alliance Holdings, you'll receive unparalleled advantages that won't be available after our public launch.": {
    es: "Como partidario temprano de Aureus Alliance Holdings, recibirás ventajas incomparables que no estarán disponibles después de nuestro lanzamiento público.",
    fr: "En tant que partisan précoce de l'Alliance Providentielle Aureus, vous recevrez des avantages inégalés qui ne seront pas disponibles après notre lancement public.",
    de: "Als früher Unterstützer von Aureus Alliance Holdings erhalten Sie unvergleichliche Vorteile, die nach unserem öffentlichen Start nicht mehr verfügbar sein werden.",
    pt: "Como apoiador inicial da Aliança Anjo Aureus, você receberá vantagens incomparáveis que não estarão disponíveis após nosso lançamento público.",
    it: "Come sostenitore iniziale dell'Alleanza Angelo Aureus, riceverai vantaggi impareggiabili che non saranno disponibili dopo il nostro lancio pubblico.",
    ru: "Как ранний сторонник Альянса Ангелов Aureus, вы получите непревзойденные преимущества, которые не будут доступны после нашего публичного запуска.",
    zh: "作为Aureus天使联盟的早期支持者，您将获得在我们公开发布后无法获得的无与伦比的优势。",
    ja: "Aureus Alliance Holdingsの初期サポーターとして、公開ローンチ後には利用できない比類のない利点を受けることができます。",
    ar: "كداعم مبكر لتحالف الملائكة Aureus، ستحصل على مزايا لا مثيل لها لن تكون متاحة بعد إطلاقنا العام.",
    uk: "Як ранній прихильник Альянсу Ангелів Aureus, ви отримаєте неперевершені переваги, які не будуть доступні після нашого публічного запуску.",
    hi: "Aureus एंजेल एलायंस के प्रारंभिक समर्थक के रूप में, आपको अतुलनीय लाभ मिलेंगे जो हमारे सार्वजनिक लॉन्च के बाद उपलब्ध नहीं होंगे।",
    ur: "Aureus Angel Alliance کے ابتدائی حامی کے طور پر، آپ کو بے مثال فوائد حاصل ہوں گے جو ہماری عوامی لانچ کے بعد دستیاب نہیں ہوں گے۔",
    bn: "Aureus Angel Alliance-এর প্রাথমিক সমর্থক হিসেবে, আপনি অতুলনীয় সুবিধা পাবেন যা আমাদের পাবলিক লঞ্চের পর পাওয়া যাবে না।",
    ko: "Aureus Alliance Holdings의 초기 지지자로서, 공개 출시 후에는 이용할 수 없는 비할 데 없는 이점을 받게 됩니다.",
    ms: "Sebagai penyokong awal Aureus Alliance Holdings, anda akan menerima kelebihan yang tiada tandingan yang tidak akan tersedia selepas pelancaran awam kami."
  },
  "Why Choose Aureus Alliance?": {
    es: "¿Por Qué Elegir Aureus Alliance?",
    fr: "Pourquoi Choisir Aureus Alliance?",
    de: "Warum Aureus Alliance Wählen?",
    pt: "Por Que Escolher Aureus Alliance?",
    it: "Perché Scegliere Aureus Alliance?",
    ru: "Почему Выбрать Aureus Alliance?",
    zh: "为什么选择Aureus Alliance？",
    ja: "なぜAureus Allianceを選ぶのか？",
    ar: "لماذا تختار Aureus Alliance؟",
    uk: "Чому Обрати Aureus Alliance?",
    hi: "Aureus Alliance क्यों चुनें?",
    ur: "Aureus Alliance کیوں منتخب کریں؟",
    bn: "কেন Aureus Alliance বেছে নেবেন?",
    ko: "왜 Aureus Alliance를 선택해야 할까요?",
    ms: "Mengapa Memilih Aureus Alliance?"
  }
};

interface RealWorkingTranslatorProps {
  className?: string;
}

const RealWorkingTranslator: React.FC<RealWorkingTranslatorProps> = ({ className = '' }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedLanguage, setSelectedLanguage] = useState(languages[0]);
  const [isTranslating, setIsTranslating] = useState(false);

  useEffect(() => {
    // Check for saved language and restore translation
    const savedLang = localStorage.getItem('selectedLanguage');
    if (savedLang && savedLang !== 'en') {
      const lang = languages.find(l => l.code === savedLang);
      if (lang) {
        setSelectedLanguage(lang);
        // Auto-translate on page load
        setTimeout(() => {
          translatePageContent(savedLang);
        }, 1000);
      }
    }
  }, []);

  const translateText = (text: string, targetLang: string): string => {
    if (targetLang === 'en') return text;

    const cleanText = text.trim();

    // Direct match
    if (translations[cleanText] && translations[cleanText][targetLang]) {
      return translations[cleanText][targetLang];
    }

    // Try partial matches for compound phrases
    let translatedText = cleanText;
    let hasTranslation = false;

    // Sort keys by length (longest first) to avoid partial replacements
    const sortedKeys = Object.keys(translations).sort((a, b) => b.length - a.length);

    for (const key of sortedKeys) {
      if (translatedText.includes(key) && translations[key][targetLang]) {
        translatedText = translatedText.replace(new RegExp(key, 'gi'), translations[key][targetLang]);
        hasTranslation = true;
      }
    }

    // If we found any translations, return the result
    if (hasTranslation) {
      return translatedText;
    }

    // Try word-by-word translation for common words
    const words = cleanText.split(' ');
    if (words.length > 1) {
      const translatedWords = words.map(word => {
        const cleanWord = word.replace(/[^\w]/g, ''); // Remove punctuation
        if (translations[cleanWord] && translations[cleanWord][targetLang]) {
          return word.replace(cleanWord, translations[cleanWord][targetLang]);
        }
        return word;
      });

      const wordTranslatedText = translatedWords.join(' ');
      if (wordTranslatedText !== cleanText) {
        return wordTranslatedText;
      }
    }

    // If no translation found, return original
    return text;
  };

  const translatePageContent = (languageCode: string) => {
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
        const translatedText = translateText(originalText, languageCode);
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
        const translated = translateText(text, languageCode);
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
        const translated = translateText(placeholder, languageCode);
        if (translated !== placeholder) {
          (input as HTMLInputElement).placeholder = translated;
          translatedCount++;
        }
      }
    });

    console.log(`🎉 Translation complete! Translated ${translatedCount} elements`);
    
    // Show success notification
    showNotification(`✅ Page translated to ${languages.find(l => l.code === languageCode)?.name}! (${translatedCount} elements)`, 'success');
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
      translatePageContent(language.code);
      setIsTranslating(false);
    }, 500);
  };

  return (
    <div className={`relative ${className}`}>
      <Button
        variant="ghost"
        size="sm"
        onClick={() => setIsOpen(!isOpen)}
        disabled={isTranslating}
        className="flex items-center gap-2 text-white/80 hover:text-white hover:bg-white/10 border border-gold/30 hover:border-gold/50 disabled:opacity-50"
      >
        <Globe className={`h-4 w-4 ${isTranslating ? 'animate-spin' : ''}`} />
        <span className="hidden md:inline">{selectedLanguage.flag}</span>
        <span className="hidden lg:inline text-xs">{selectedLanguage.name}</span>
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
                selectedLanguage.code === language.code ? 'bg-gold/20 text-gold' : 'text-white'
              }`}
            >
              <span className="text-lg">{language.flag}</span>
              <span>{language.name}</span>
              {selectedLanguage.code === language.code && (
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

export default RealWorkingTranslator;
