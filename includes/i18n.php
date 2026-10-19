<?php
/**
 * Internationalization (i18n) System
 * Supports: English (en), Amharic (am - አማርኛ), and Afaan Oromoo (om - Afaan Oromoo)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Available languages definition
 */
function getAvailableLanguages(): array {
    return [
        'en' => [
            'code'   => 'en',
            'name'   => 'English',
            'native' => 'English',
            'flag'   => '🇬🇧'
        ],
        'am' => [
            'code'   => 'am',
            'name'   => 'Amharic',
            'native' => 'አማርኛ',
            'flag'   => '🇪🇹'
        ],
        'om' => [
            'code'   => 'om',
            'name'   => 'Afaan Oromoo',
            'native' => 'Afaan Oromoo',
            'flag'   => '🌳'
        ]
    ];
}

/**
 * Resolves current language code
 */
function getCurrentLang(): string {
    $allowed = ['en', 'am', 'om'];

    // 1. URL parameter override (?lang=am)
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $lang;
        @setcookie('site_lang', $lang, time() + (86400 * 365), '/');
        return $lang;
    }

    // 2. Session
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed, true)) {
        return $_SESSION['lang'];
    }

    // 3. Cookie
    if (!empty($_COOKIE['site_lang']) && in_array($_COOKIE['site_lang'], $allowed, true)) {
        $_SESSION['lang'] = $_COOKIE['site_lang'];
        return $_COOKIE['site_lang'];
    }

    // 4. User profile preference if logged in
    if (!empty($_SESSION['user_data']['preferred_language']) && in_array($_SESSION['user_data']['preferred_language'], $allowed, true)) {
        return $_SESSION['user_data']['preferred_language'];
    }

    return 'en';
}

/**
 * Master Translation Dictionary
 */
function getTranslations(): array {
    return [
        // Navigation & General
        'nav_buy'              => ['en' => 'Buy', 'am' => 'ግዛ', 'om' => 'Biti'],
        'nav_rent'             => ['en' => 'Rent', 'am' => 'ተከራይ', 'om' => 'Kireessi'],
        'nav_sell'             => ['en' => 'Sell', 'am' => 'ሽጥ', 'om' => 'Gurguri'],
        'nav_explore'          => ['en' => 'Explore All', 'am' => 'ሁሉንም አስስ', 'om' => 'Hunda Daawwadhu'],
        'nav_post_property'    => ['en' => 'Post Property', 'am' => 'ቤት መዝግብ', 'om' => 'Mana Galmeessi'],
        'nav_dashboard'        => ['en' => 'Dashboard', 'am' => 'ዳሽቦርድ', 'om' => 'Daashboordii'],
        'nav_settings'         => ['en' => 'Settings', 'am' => 'ቅንብሮች', 'om' => 'Qindaa’inoota'],
        'nav_signin'           => ['en' => 'Sign In', 'am' => 'ግባ', 'om' => 'Seeni'],
        'nav_register'         => ['en' => 'Create Account', 'am' => 'ተመዝገብ', 'om' => 'Akkaawuntii Uumi'],
        'nav_signout'          => ['en' => 'Sign Out', 'am' => 'ውጣ', 'om' => 'Ba’i'],
        'nav_safety_reports'   => ['en' => 'Safety Reports', 'am' => 'የደህንነት ሪፖርቶች', 'om' => 'Gabaasa Nageenyaa'],

        // Hero Search
        'hero_title'           => [
            'en' => 'The Smarter Way to Buy, Rent & Sell Exceptional Homes Across Ethiopia',
            'am' => 'በኢትዮጵያ ውስጥ ምርጥ ቤቶችን ለመግዛት፣ ለመከራየት እና ለመሸጥ የተሻለው መንገድ',
            'om' => 'Itoophiyaa keessatti Manneen filatamoo Bituu, Kireeffachuu fi Gurguruuf Karaa Filatamaa'
        ],
        'hero_subtitle'        => [
            'en' => 'Browse verified listings, view upfront broker fees before contacting agents, and explore neighborhoods with satellite map search.',
            'am' => 'የተረጋገጡ የቤት ማስታወቂያዎችን ይመልከቱ፣ ወኪሎችን ከማነጋገርዎ በፊት የደላላ ክፍያን በግልጽ ይወቁ እና በሳተላይት ካርታ አካባቢዎችን ያስሱ።',
            'om' => 'Manneen mirkanaa’an ilaalaa, dilaalota qunnamuun dura kaffaltii ifatti beekaa, fi kaartaa saatalaayitiin naannoo daawwadhaa.'
        ],
        'hero_btn_register'    => [
            'en' => 'Create Free Account / Sign Up',
            'am' => 'ነፃ አካውንት ክፈት / ተመዝገብ',
            'om' => 'Akkaawuntii Bilisaa Uumi / Galmaa’i'
        ],
        'hero_btn_signin'      => [
            'en' => 'Sign In',
            'am' => 'ግባ',
            'om' => 'Seeni'
        ],
        'tab_all'              => ['en' => 'All Properties', 'am' => 'ሁሉም ቤቶች', 'om' => 'Manneen Hunda'],
        'tab_buy'              => ['en' => 'Buy a Home', 'am' => 'ቤት ይግዙ', 'om' => 'Mana Biti'],
        'tab_rent'             => ['en' => 'Rent a House', 'am' => 'ቤት ይከራዩ', 'om' => 'Mana Kireeffadhu'],
        'search_loc_placeholder'=> ['en' => 'e.g. Bole, Kazanchis, CMC, Hawassa', 'am' => 'ምሳሌ፦ ቦሌ፣ ካዛንቺስ፣ ሲኤምሲ፣ ሐዋሳ', 'om' => 'Fkn: Boolee, Kaazaanchis, CMC, Hawaasaa'],
        'search_btn'           => ['en' => 'Search Homes', 'am' => 'ፈልግ', 'om' => 'Barbaadi'],
        'property_type'        => ['en' => 'Property Type', 'am' => 'የቤት ዓይነት', 'om' => 'Gosa Manaa'],
        'max_price'            => ['en' => 'Max Price', 'am' => 'ከፍተኛ ዋጋ', 'om' => 'Gatii Ol’aanaa'],

        // Categories Cards
        'cat_buy_title'        => ['en' => 'Buy a Home', 'am' => 'ቤት መግዛት', 'om' => 'Mana Bituu'],
        'cat_buy_desc'         => ['en' => 'Discover luxury villas, modern apartments, and compounds for sale with transparent prices.', 'am' => 'ዘመናዊ ቪላዎችን፣ አፓርታማዎችን እና ግቢ ያላቸው ቤቶችን ግልጽ በሆነ ዋጋ ይግዙ።', 'om' => 'Viillaawwan ammayyaa, apaartaamaa fi qe’eewwan gatii ifa ta’een biti.'],
        'cat_rent_title'       => ['en' => 'Rent a House', 'am' => 'ቤት መከራየት', 'om' => 'Mana Kireeffachuu'],
        'cat_rent_desc'        => ['en' => 'Browse downtown lofts, furnished apartments, and family residences for rent.', 'am' => 'የተሟሉ አፓርታማዎችን እና የመኖሪያ ቤቶችን በወርሃዊ ኪራይ ያግኙ።', 'om' => 'Apaartaamaa guutuu fi manneen jireenyaa kireeffadhu.'],
        'cat_sell_title'       => ['en' => 'Sell Your Property', 'am' => 'ቤትዎን ይሽጡ', 'om' => 'Mana Kee Gurguri'],
        'cat_sell_desc'        => ['en' => 'Post your property with photos, set your price, and choose direct owner sale or certified brokers.', 'am' => 'ቤትዎን በፎቶዎች ይለጥፉ፣ ዋጋ ይወስኑ፣ በቀጥታ ወይም በተረጋገጡ ደላሎች ይሽጡ።', 'om' => 'Suuraan mana keetii maxxansi, gatii murteessi, ofiin ykn dilaalaan gurguri.'],

        // Badges & Labels
        'badge_for_sale'       => ['en' => 'For Sale', 'am' => 'ለሽያጭ', 'om' => 'Gurguraaf'],
        'badge_for_rent'       => ['en' => 'For Rent', 'am' => 'ለኪራይ', 'om' => 'Kiraadhaaf'],
        'badge_verified'       => ['en' => 'Phone Verified', 'am' => 'ስልክ የተረጋገጠ', 'om' => 'Bilbilli Mirkanaa’e'],
        'badge_direct_owner'   => ['en' => 'Direct Owner (Zero Fee)', 'am' => 'ቀጥታ ከባለቤቱ (ያለ ኮሚሽን)', 'om' => 'Abbaa Qabeenyaa Irraa (Kaffaltii Malee)'],
        'badge_broker'         => ['en' => 'Broker Represented', 'am' => 'በደላላ የሚመራ', 'om' => 'Dilaalaan Kan Dhiyaate'],
        'beds'                 => ['en' => 'Beds', 'am' => 'መኝታ ክፍሎች', 'om' => 'Kutaa Ciisichaa'],
        'baths'                => ['en' => 'Baths', 'am' => 'መታጠቢያ ቤቶች', 'om' => 'Manooqee'],
        'sqft'                 => ['en' => 'Sq Ft', 'am' => 'ካሬ ጫማ', 'om' => 'Sq Ft'],
        'per_month'            => ['en' => '/mo', 'am' => '/በወር', 'om' => '/ji’atti'],
        'view_details'         => ['en' => 'View Details', 'am' => 'ዝርዝር እይ', 'om' => 'Bal’inaan Ilaali'],

        // Settings Page
        'settings_title'       => ['en' => 'Account & App Settings', 'am' => 'የመለያ እና መተግበሪያ ቅንብሮች', 'om' => 'Qindaa’inoota Herregaa fi Appii'],
        'tab_lang'             => ['en' => 'Language & Regional', 'am' => 'ቋንቋ እና ክልል', 'om' => 'Afaan fi Naannoo'],
        'tab_security'         => ['en' => 'Password & Security', 'am' => 'የይለፍ ቃል እና ደህንነት', 'om' => 'Jechama fi Nageenya'],
        'tab_feedback'         => ['en' => 'User Feedback', 'am' => 'አስተያየት ይስጡ', 'om' => 'Yaada Fayyadamaa'],
        'tab_reports'          => ['en' => 'Safety & Reports', 'am' => 'ደህንነት እና ሪፖርቶች', 'om' => 'Nageenya fi Gabaasa'],
        'tab_ai_assistant'     => ['en' => 'AI Smart Assistant', 'am' => 'ዘመናዊ AI ረዳት', 'om' => 'Gargaaraa AI Ammayyaa'],

        // AI Assistant Feature
        'ai_feature_title'     => [
            'en' => 'Antigravity AI Real Estate Engine',
            'am' => 'አንቲግራቪቲ AI የሪል እስቴት ረዳት',
            'om' => 'Mootora Qabeenya Manaa Antigravity AI'
        ],
        'ai_feature_badge'     => ['en' => 'Featured Smart Setting', 'am' => 'ምርጥ የቅንብር ገፅታ', 'om' => 'Amala Qindaa’ina Filatamaa'],
        'ai_desc_generator'    => ['en' => 'AI Listing Enhancer', 'am' => 'የማስታወቂያ ፅሁፍ አሻሽይ', 'om' => 'Ibsa Maxxansaa Cimsuu'],
        'ai_matcher'           => ['en' => 'AI Smart Home Matcher', 'am' => 'ስማርት የቤት ፈላጊ', 'om' => 'Mana Barbaadaa AI'],

        // Feedback & Rating
        'feedback_title'       => ['en' => 'Share Your Feedback', 'am' => 'አስተያየትዎን ያካፍሉን', 'om' => 'Yaada Kee Nuuf Qoodaa'],
        'feedback_subtitle'    => ['en' => 'Help us improve EstateCraft Ethiopia for everyone.', 'am' => 'ኢስቴትክራፍት ኢትዮጵያን ይበልጥ ለማሻሻል ያግዙን።', 'om' => 'EstateCraft Itoophiyaa fooyyessuuf nu gargaaraa.'],
        'submit_feedback'      => ['en' => 'Submit Feedback', 'am' => 'አስተያየት ላክ', 'om' => 'Yaada Ergi'],
        'rating'               => ['en' => 'Your Overall Rating', 'am' => 'አጠቃላይ ደረጃዎ', 'om' => 'Sadarkaa Waliigalaa'],

        // Password Reset
        'forgot_pass_link'     => ['en' => 'Forgot Password?', 'am' => 'የይለፍ ቃል ረሱ?', 'om' => 'Jechama Daggattee?'],
        'reset_pass_title'     => ['en' => 'Reset Your Password', 'am' => 'የይለፍ ቃልዎን ይቀይሩ', 'om' => 'Jechama Kee Haaromsi'],
        'send_reset_code'      => ['en' => 'Send Reset Link / OTP', 'am' => 'የማስተካከያ ኮድ ላክ', 'om' => 'Koodii Haaromsaa Ergi'],
        'save_changes'         => ['en' => 'Save Changes', 'am' => 'ለውጦችን መዝግብ', 'om' => 'Jijjiirama Galmeessi'],

        // Theme Modes (Day, Night, Default)
        'theme_mode'           => ['en' => 'Theme Mode', 'am' => 'የማሳያ ሁነታ', 'om' => 'Haala Mul’isaa'],
        'mode_day'             => ['en' => 'Day Mode', 'am' => 'የቀን ሁነታ (ብርሃን)', 'om' => 'Haala Guyyaa'],
        'mode_night'           => ['en' => 'Night Mode', 'am' => 'የማታ ሁነታ (ጨለማ)', 'om' => 'Haala Halkan'],
        'mode_default'         => ['en' => 'System Default', 'am' => 'ነባሪ (የሲስተም)', 'om' => 'Durtii Sirnaa'],
        'mode_day_desc'        => ['en' => 'Clean, bright interface designed for high-clarity daytime browsing.', 'am' => 'ለቀን አጠቃቀም የተመቻቸ ንጹህ እና ብሩህ ገጽታ።', 'om' => 'Bifa ifaa fi qulqulluu guyyaa fayyadamuuf tolfame.'],
        'mode_night_desc'      => ['en' => 'Deep slate dark interface gentle on eyes in dim environments.', 'am' => 'ለማታ ንባብ ምቹ የሆነ የጨለማ ገጽታ።', 'om' => 'Bifa dukkanaa’aa ijaaf mijatu kan halkan fayyadaman.'],
        'mode_default_desc'    => ['en' => 'Automatically switches between Day and Night matching your device settings.', 'am' => 'ከመሳሪያዎ ስርዓት (OS) ጋር ራሱን በራሱ የሚያስተካክል።', 'om' => 'Sirna meeshaa keetii wajjin ofumaan kan wal simatu.']
    ];
}

/**
 * Translates key into current language
 * Fallbacks to English or default text if key is missing
 */
function __($key, ?string $default = null): string {
    $lang = getCurrentLang();
    $translations = getTranslations();

    if (isset($translations[$key][$lang])) {
        return $translations[$key][$lang];
    }

    if (isset($translations[$key]['en'])) {
        return $translations[$key]['en'];
    }

    return $default ?? $key;
}
