<?php
/**
 * EstateCraft - Homepage
 * Buy, Sell, and Rent Real Estate with Interactive Map, Transparent Broker Fees & Verified Accounts
 */

$pageTitle = "Find, Buy, Sell & Rent Exceptional Homes";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/upload.php';

$currentUser = getCurrentUser();
$pdo = getDB();

// Fetch Featured Properties
$stmtFeatured = $pdo->query("
    SELECT 
        p.*,
        u.full_name as seller_name,
        u.phone_verified as seller_phone_verified,
        u.role as seller_role,
        img.image_path as primary_image
    FROM properties p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
    WHERE p.status = 'active'
    ORDER BY p.featured DESC, p.created_at DESC
    LIMIT 6
");
$featuredProperties = $stmtFeatured->fetchAll();

// Top Cities aggregation for quick search
$stmtCities = $pdo->query("SELECT city, COUNT(*) as count FROM properties WHERE status = 'active' GROUP BY city ORDER BY count DESC LIMIT 4");
$topCities = $stmtCities->fetchAll();

// First Page Landing View: Dedicated Login & Create Account (Shown on initial visit before authentication)
if (!$currentUser && !isset($_GET['explore'])) {
    $hideHeaderNavbar = true;
    $pageTitle = "Log in or Sign up - EstateCraft";

    require_once __DIR__ . '/includes/header.php';
    ?>

    <section class="fb-landing-section" style="min-height: 100vh;">
        <div class="fb-landing-container">
            <!-- Left Column: Branding, Tagline & Photo Cards Collage -->
            <div class="fb-left-col">
                <div class="fb-brand-circle">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>

                <h1 class="fb-headline">
                    Explore<br>
                    the homes<br>
                    <span class="accent-blue">you love.</span>
                </h1>

                <!-- Visual Collage -->
                <div class="fb-collage">
                    <!-- Phone-style story card (Main top right) -->
                    <div class="fb-card-story">
                        <div class="fb-story-time">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            16:45
                        </div>
                        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80" alt="Modern Villa in Addis Ababa">
                        <div class="fb-story-bottom-bar">
                            <div class="fb-story-circle"></div>
                            <div class="fb-story-circle"></div>
                            <div class="fb-story-circle"></div>
                        </div>
                    </div>

                    <!-- Middle left square card with star badge -->
                    <div class="fb-card-square">
                        <div class="fb-card-badge">★</div>
                        <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=500&q=80" alt="Modern Interior">
                    </div>

                    <!-- Bottom card -->
                    <div class="fb-card-bottom">
                        <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=500&q=80" alt="Luxury Compound">
                    </div>

                    <!-- Avatar circle -->
                    <div class="fb-avatar-circle">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&q=80" alt="Verified Broker">
                    </div>

                    <!-- Floating Reactions -->
                    <div class="fb-reaction-laugh" title="Excited">😆</div>
                    <div class="fb-reaction-heart" title="Love this home">❤️</div>
                </div>
            </div>

            <!-- Center Divider Line -->
            <div class="fb-center-divider"></div>

            <!-- Right Column: Login & Create Account Box -->
            <div class="fb-right-col" style="max-width: 440px;">
                <div class="fb-auth-box">
                    <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert alert-error" style="margin-bottom: 16px; border-radius: 8px;">
                            <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success" style="margin-bottom: 16px; border-radius: 8px;">
                            <div><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
                        </div>
                        <?php unset($_SESSION['flash_success']); ?>
                    <?php endif; ?>

                    <!-- View 1: Log In Card -->
                    <div id="firstPageLoginCard">
                        <h2 class="fb-login-heading">Log into EstateCraft</h2>

                        <form method="POST" action="<?= BASE_URL ?>/auth/login.php">
                            <?= getCsrfInput() ?>

                            <div class="fb-input-wrapper">
                                <input type="text" name="email" class="fb-field-input" placeholder="Email or mobile number" required autocomplete="username">
                            </div>

                            <div class="fb-input-wrapper" style="position: relative;">
                                <input type="password" id="firstPageLoginPassword" name="password" class="fb-field-input" placeholder="Password" required autocomplete="current-password" style="padding-right: 44px;">
                                <button type="button" onclick="togglePasswordVisibility('firstPageLoginPassword', this)" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px;" title="Show/Hide Password">
                                    👁️
                                </button>
                            </div>

                            <button type="submit" class="fb-submit-btn" id="btnSubmitFirstPageLogin">
                                Log in
                            </button>

                            <div class="fb-forgot-link-wrap">
                                <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="fb-forgot-link">Forgot password?</a>
                            </div>

                            <div class="fb-line-sep"></div>

                            <button type="button" class="fb-create-acc-btn" id="btnShowCreateAccount" onclick="toggleAuthForm('register')">
                                Create new account
                            </button>
                        </form>
                    </div>

                    <!-- View 2: Create New Account Card (Toggled) -->
                    <div id="firstPageRegisterCard" style="display: none;">
                        <h2 class="fb-login-heading" style="margin-bottom: 4px;">Create a new account</h2>
                        <p style="color: #65676b; font-size: 14px; margin-bottom: 18px;">It's quick and easy.</p>

                        <form method="POST" action="<?= BASE_URL ?>/auth/register.php">
                            <?= getCsrfInput() ?>

                            <div class="fb-input-wrapper">
                                <input type="text" name="full_name" class="fb-field-input" placeholder="Full legal name" required autocomplete="name">
                            </div>

                            <div class="fb-input-wrapper">
                                <input type="email" name="email" class="fb-field-input" placeholder="Email address" required autocomplete="email">
                            </div>

                            <div class="fb-input-wrapper">
                                <input type="tel" name="phone" class="fb-field-input" placeholder="Mobile phone" required autocomplete="tel">
                            </div>

                            <div class="fb-input-wrapper" style="position: relative;">
                                <input type="password" id="firstPageRegPassword" name="password" class="fb-field-input" placeholder="Password (min. 6 characters)" required autocomplete="new-password" style="padding-right: 44px;">
                                <button type="button" onclick="togglePasswordVisibility('firstPageRegPassword', this)" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px;" title="Show/Hide Password">
                                    👁️
                                </button>
                            </div>

                            <div class="fb-input-wrapper" style="position: relative;">
                                <input type="password" id="firstPageRegConfirm" name="confirm_password" class="fb-field-input" placeholder="Confirm password" required autocomplete="new-password" style="padding-right: 44px;">
                                <button type="button" onclick="togglePasswordVisibility('firstPageRegConfirm', this)" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px;" title="Show/Hide Password">
                                    👁️
                                </button>
                            </div>

                            <button type="submit" class="fb-submit-btn" id="btnSubmitFirstPageRegister" style="background-color: #00a400;">
                                Sign Up
                            </button>

                            <div class="fb-line-sep"></div>

                            <button type="button" class="fb-create-acc-btn" id="btnBackToLogin" onclick="toggleAuthForm('login')">
                                Already have an account? Log in
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Facebook Minimal Language & Copyright Footer -->
        <div style="width: 100%; border-top: 1px solid #e5e7eb; padding: 22px 20px; text-align: center; font-size: 13px; color: #65676b; margin-top: 40px;">
            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 8px;">
                <a href="<?= buildLangUrl('en') ?>" style="color: #65676b; text-decoration: none;">English (US)</a>
                <a href="<?= buildLangUrl('am') ?>" style="color: #65676b; text-decoration: none;">አማርኛ</a>
                <a href="<?= buildLangUrl('om') ?>" style="color: #65676b; text-decoration: none;">Afaan Oromoo</a>
            </div>
            <div>EstateCraft &copy; 2026 &bull; Verified Real Estate Marketplace Ethiopia</div>
        </div>
    </section>

    <script>
    function toggleAuthForm(mode) {
        const loginCard = document.getElementById('firstPageLoginCard');
        const regCard = document.getElementById('firstPageRegisterCard');
        if (mode === 'register') {
            loginCard.style.display = 'none';
            regCard.style.display = 'block';
        } else {
            regCard.style.display = 'none';
            loginCard.style.display = 'block';
        }
    }

    function togglePasswordVisibility(fieldId, btn) {
        const pw = document.getElementById(fieldId);
        if (!pw) return;
        if (pw.type === 'password') {
            pw.type = 'text';
            btn.textContent = '🙈';
        } else {
            pw.type = 'password';
            btn.textContent = '👁️';
        }
    }
    </script>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section" id="propertiesCatalogSection">
    <div class="hero-ambient-orb orb-1"></div>
    <div class="hero-ambient-orb orb-2"></div>
    <div class="hero-pattern"></div>
    
    <div class="hero-content">
        <div class="hero-tag">
            <span class="pulse-dot"></span>
            <span><?= __('hero_tag', 'Verified Real Estate Marketplace Ethiopia') ?></span>
            <span style="opacity: 0.5;">&bull;</span>
            <span class="hero-tag-gold">🛡️ <?= __('hero_tag_fayda', '100% Fayda ID Verified') ?></span>
        </div>

        <h1 class="hero-title">
            <?= __('hero_title_1', 'Find, Buy, Sell & Rent') ?><br>
            <span class="hero-title-accent"><?= __('hero_title_accent', 'Exceptional Homes') ?></span><br>
            <?= __('hero_title_2', 'Across Ethiopia') ?>
        </h1>
        <p class="hero-subtitle">
            <?= __('hero_subtitle', 'Browse verified listings, view upfront broker fees before contacting agents, and explore neighborhoods with satellite map search.') ?>
        </p>

        <!-- Trust Stats Row -->
        <div class="hero-stats-row">
            <div class="hero-stat-item">
                <span class="stat-number">2,400+</span>
                <span class="stat-label">Verified Listings</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">National ID Verified</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat-item">
                <span class="stat-number">0% Fee</span>
                <span class="stat-label">Direct Owner Deals</span>
            </div>
            <div class="hero-stat-sep"></div>
            <div class="hero-stat-item">
                <span class="stat-number">4.9 ★</span>
                <span class="stat-label">Client Trust</span>
            </div>
        </div>

        <?php if (!$currentUser): ?>
        <!-- First View Call to Action: Sign In, Sign Up & Create Account -->
        <div style="display: flex; gap: 14px; justify-content: center; align-items: center; margin: 0 0 36px; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg" id="heroBtnCreateAccount" style="font-weight: 800; padding: 13px 28px; border-radius: var(--radius-full); box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4); text-decoration: none; display: inline-flex; align-items: center; gap: 10px; font-size: 15px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span><?= __('hero_btn_register', 'Create Free Account / Sign Up') ?></span>
            </a>
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary btn-lg" id="heroBtnSignIn" style="font-weight: 700; padding: 13px 26px; border-radius: var(--radius-full); background: rgba(255, 255, 255, 0.95); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 15px; border: 1px solid var(--border-color); color: var(--secondary);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                <span><?= __('hero_btn_signin', 'Sign In') ?></span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Search Bar Card -->
        <div class="search-box-card">
            <div class="search-tabs">
                <button type="button" class="tab-btn active" data-purpose="">✨ <?= __('tab_all', 'All Properties') ?></button>
                <button type="button" class="tab-btn" data-purpose="sale">🏡 <?= __('tab_buy', 'Buy a Home') ?></button>
                <button type="button" class="tab-btn" data-purpose="rent">🔑 <?= __('tab_rent', 'Rent a House') ?></button>
            </div>

            <form method="GET" action="<?= BASE_URL ?>/properties/index.php">
                <input type="hidden" name="purpose" id="searchPurposeInput" value="">

                <div class="search-grid">
                    <div class="form-group">
                        <label class="form-label" for="searchLocation">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span>Location / City in Ethiopia</span>
                        </label>
                        <input type="text" id="searchLocation" name="location" class="form-input" placeholder="e.g. Bole, Kazanchis, CMC, Hawassa">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="searchType">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                            <span>Property Type</span>
                        </label>
                        <select id="searchType" name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="house">Houses &amp; Compounds</option>
                            <option value="apartment">Apartments / Condos</option>
                            <option value="villa">Luxury Villas</option>
                            <option value="townhouse">Townhouses</option>
                            <option value="commercial">Commercial Space</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="searchPrice">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 18V6"></path></svg>
                            <span>Max Budget (ETB)</span>
                        </label>
                        <select id="searchPrice" name="max_price" class="form-select">
                            <option value="">Any Budget</option>
                            <option value="50000">Up to ETB 50,000 / mo</option>
                            <option value="100000">Up to ETB 100,000 / mo</option>
                            <option value="20000000">Up to ETB 20,000,000</option>
                            <option value="40000000">Up to ETB 40,000,000</option>
                            <option value="60000000">ETB 60,000,000+</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="btnMainSearch">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span><?= __('search_btn', 'Search Homes') ?></span>
                    </button>
                </div>
            </form>

            <!-- Quick Popular Searches -->
            <div class="search-quick-tags">
                <span class="quick-tags-label">Popular Searches:</span>
                <a href="<?= BASE_URL ?>/properties/index.php?location=Bole" class="quick-tag-chip" style="text-decoration: none;">📍 Bole Atlas</a>
                <a href="<?= BASE_URL ?>/properties/index.php?location=Kazanchis" class="quick-tag-chip" style="text-decoration: none;">📍 Kazanchis</a>
                <a href="<?= BASE_URL ?>/properties/index.php?location=Old+Airport" class="quick-tag-chip" style="text-decoration: none;">📍 Old Airport</a>
                <a href="<?= BASE_URL ?>/properties/index.php?location=CMC" class="quick-tag-chip" style="text-decoration: none;">📍 CMC Condos</a>
                <a href="<?= BASE_URL ?>/properties/index.php?location=Hawassa" class="quick-tag-chip" style="text-decoration: none;">📍 Hawassa Lake</a>
                <a href="<?= BASE_URL ?>/properties/index.php?location=Bishoftu" class="quick-tag-chip" style="text-decoration: none;">📍 Bishoftu Resort</a>
            </div>
        </div>
    </div>
</section>

<!-- Quick Action Categories: Buy, Rent, Sell -->
<div class="quick-categories">
    <div class="cat-card">
        <div class="cat-icon cat-icon-blue">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <h3 class="cat-title"><?= __('cat_buy_title', 'Buy a Home') ?></h3>
        <p class="cat-desc"><?= __('cat_buy_desc', 'Discover luxury villas, modern apartments, and compounds for sale with transparent prices.') ?></p>
        <a href="<?= BASE_URL ?>/properties/index.php?purpose=sale" class="btn btn-outline btn-sm"><?= __('cat_buy_title', 'Find Homes for Sale') ?> &rarr;</a>
    </div>

    <div class="cat-card">
        <div class="cat-icon cat-icon-green">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </div>
        <h3 class="cat-title"><?= __('cat_rent_title', 'Rent a House') ?></h3>
        <p class="cat-desc"><?= __('cat_rent_desc', 'Browse downtown lofts, furnished apartments, and family residences available for flexible monthly rental terms.') ?></p>
        <a href="<?= BASE_URL ?>/properties/index.php?purpose=rent" class="btn btn-outline btn-sm"><?= __('cat_rent_title', 'Browse Rental Properties') ?> &rarr;</a>
    </div>

    <div class="cat-card">
        <div class="cat-icon cat-icon-amber">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <h3 class="cat-title"><?= __('cat_sell_title', 'Sell Your Property') ?></h3>
        <p class="cat-desc"><?= __('cat_sell_desc', 'Post your house with multiple photos, set your price, and choose between direct owner listing or certified brokers.') ?></p>
        <a href="<?= BASE_URL ?>/properties/create.php" class="btn btn-outline btn-sm"><?= __('cat_sell_title', 'List Your Property Now') ?> &rarr;</a>
    </div>
</div>

<!-- Featured Properties Section -->
<section class="section-wrapper">
    <div class="section-header">
        <div>
            <h2 class="section-title">Featured Property Listings</h2>
            <p class="section-subtitle">Hand-picked residential and commercial properties available right now</p>
        </div>
        <a href="<?= BASE_URL ?>/properties/index.php" class="btn btn-secondary">View All Listings &rarr;</a>
    </div>

    <div class="property-grid">
        <?php foreach ($featuredProperties as $prop): ?>
            <div class="property-card">
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars(getPropertyImageUrl($prop['primary_image'])) ?>" alt="<?= htmlspecialchars($prop['title']) ?>" loading="lazy">
                    
                    <div class="card-badges">
                        <span class="badge badge-<?= $prop['purpose'] === 'sale' ? 'sale' : 'rent' ?>">
                            For <?= $prop['purpose'] === 'sale' ? 'Sale' : 'Rent' ?>
                        </span>
                        <span class="badge" style="background: rgba(15, 23, 42, 0.85); color: #fff;">
                            <?= ucfirst($prop['property_type']) ?>
                        </span>
                    </div>

                    <div class="card-price-tag">
                        <?= CURRENCY_SYMBOL ?><?= number_format($prop['price']) ?>
                        <?php if ($prop['price_frequency'] === 'monthly'): ?>
                            <span>/mo</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body">
                    <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $prop['id'] ?>" class="card-title">
                        <?= htmlspecialchars($prop['title']) ?>
                    </a>

                    <div class="card-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <?= htmlspecialchars($prop['city']) ?>, <?= htmlspecialchars($prop['state'] ?? '') ?>
                    </div>

                    <div class="card-features">
                        <div class="card-feat-item" title="Bedrooms">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/></svg>
                            <span><?= $prop['bedrooms'] ?> Beds</span>
                        </div>
                        <div class="card-feat-item" title="Bathrooms">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h6M9 10h6m-9 4h12a2 2 0 0 1 2 2v1a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-1a2 2 0 0 1 2-2z"/></svg>
                            <span><?= $prop['bathrooms'] ?> Baths</span>
                        </div>
                        <div class="card-feat-item" title="Area">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                            <span><?= number_format($prop['area_sqft']) ?> sqft</span>
                        </div>
                    </div>

                    <!-- Broker Fee Transparency Pill -->
                    <div class="card-broker-disclosure">
                        <?php if ($prop['has_broker']): ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span><strong>Broker Listed:</strong> <?= htmlspecialchars($prop['broker_fee_display'] ?: 'Transparent Fee Disclosed') ?></span>
                        <?php else: ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                            <span><strong>Direct Owner:</strong> Zero Broker Fees</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <?= strtoupper(substr($prop['seller_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="seller-name"><?= htmlspecialchars($prop['seller_name']) ?></div>
                                <?php if ($prop['seller_phone_verified']): ?>
                                    <span class="badge badge-verified" style="font-size: 10px; padding: 1px 6px;">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $prop['id'] ?>" class="btn btn-outline btn-sm">
                            View Details &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Interactive Map Explorer Section -->
<section class="section-wrapper">
    <div style="background: #ffffff; border-radius: var(--radius-xl); border: 1px solid var(--border-color); padding: 36px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="badge" style="background: rgba(37, 99, 235, 0.1); color: var(--primary); margin-bottom: 8px;">Interactive Geo-Search</span>
                <h2 style="font-size: 26px; font-weight: 800; color: var(--secondary);">Explore Listings on the Map</h2>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Search any city or neighborhood (e.g. Bole) or click any pin to preview properties.</p>
            </div>
            <a href="<?= BASE_URL ?>/properties/index.php?view=map" class="btn btn-primary btn-sm">Open Fullscreen Map View</a>
        </div>

        <!-- Map Search Bar -->
        <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; margin-bottom: 16px;">
            <div style="display: flex; gap: 10px;">
                <div style="position: relative; flex: 1;">
                    <input type="text" id="homeMapSearchInput" list="homeMapLocationsList" class="form-input" placeholder="🔍 Search neighborhood on map (e.g. Bole, Kazanchis, Old Airport, CMC, Hawassa...)" onkeydown="if(event.key === 'Enter'){event.preventDefault(); searchMapLocation(this.value, 'catalog');}" onchange="searchMapLocation(this.value, 'catalog');" autocomplete="off">
                    <datalist id="homeMapLocationsList">
                        <option value="Bole">
                        <option value="Bole Atlas">
                        <option value="Bole Medhanialem">
                        <option value="Kazanchis">
                        <option value="Old Airport">
                        <option value="CMC">
                        <option value="Summit">
                        <option value="Sarbet">
                        <option value="Piazza">
                        <option value="Megenagna">
                        <option value="Gerji">
                        <option value="Hawassa">
                        <option value="Bahir Dar">
                    </datalist>
                </div>
                <button type="button" class="btn btn-primary" onclick="searchMapLocation(document.getElementById('homeMapSearchInput').value, 'catalog')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Search on Map
                </button>
            </div>

            <!-- Quick Jump Chips -->
            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; align-items: center;">
                <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Quick Explore:</span>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('homeMapSearchInput').value='Bole'; searchMapLocation('Bole', 'catalog');">📍 Bole</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('homeMapSearchInput').value='Kazanchis'; searchMapLocation('Kazanchis', 'catalog');">📍 Kazanchis</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('homeMapSearchInput').value='Old Airport'; searchMapLocation('Old Airport', 'catalog');">📍 Old Airport</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('homeMapSearchInput').value='CMC'; searchMapLocation('CMC', 'catalog');">📍 CMC</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('homeMapSearchInput').value='Sarbet'; searchMapLocation('Sarbet', 'catalog');">📍 Sarbet</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('homeMapSearchInput').value='Hawassa'; searchMapLocation('Hawassa', 'catalog');">📍 Hawassa</button>
            </div>
            <div id="homeMapSearchStatus" style="font-size: 13px; color: var(--primary); margin-top: 8px; font-weight: 600;"></div>
        </div>

        <div id="homeCatalogMap" style="height: 440px; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
    </div>
</section>

<script>
function startHomeCatalogMap() {
    if (typeof initCatalogMap === 'function') {
        initCatalogMap('homeCatalogMap', "<?= BASE_URL ?>/api/get_properties.php");
    } else {
        setTimeout(startHomeCatalogMap, 150);
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startHomeCatalogMap);
} else {
    startHomeCatalogMap();
}
</script>

<!-- Trust & Platform Pillars -->
<section class="section-wrapper" id="safety">
    <div style="text-align: center; max-width: 650px; margin: 0 auto 40px;">
        <h2 style="font-size: 28px; font-weight: 800; color: var(--secondary); margin-bottom: 8px;">Built for Trust and Transparency</h2>
        <p style="color: var(--text-muted); font-size: 15px;">Every feature on EstateCraft is crafted to protect buyers, sellers, and certified real estate professionals.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 28px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: var(--success); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <h4 style="font-size: 17px; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">Verified Mobile Numbers</h4>
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">Listers confirm their phone identity via SMS OTP, significantly reducing fraudulent posts and spam.</p>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 28px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h4 style="font-size: 17px; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">Upfront Broker Fee Disclosure</h4>
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">No surprise broker commissions. Licensed brokers must disclose exact fee rates before you contact them.</p>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 28px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(37, 99, 235, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            </div>
            <h4 style="font-size: 17px; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">Interactive Geo-Location</h4>
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">Pinpoint homes on OpenStreetMap, check distances to amenities, and search directly on the map.</p>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 28px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239, 68, 68, 0.1); color: var(--danger); display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
            </div>
            <h4 style="font-size: 17px; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">Community Safety Reporting</h4>
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">Anyone can flag suspicious listings. Multi-reported posts are automatically quarantined for admin review.</p>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); color: #ffffff; padding: 60px 24px; text-align: center; margin-top: 40px;">
    <div style="max-width: 700px; margin: 0 auto;">
        <h2 style="font-size: 32px; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.02em;">Ready to List Your Property?</h2>
        <p style="color: #94a3b8; font-size: 16px; margin-bottom: 28px;">Connect directly with genuine buyers and tenants across Ethiopia with zero hassle.</p>
        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/properties/create.php" class="btn btn-primary btn-lg">Post Your Property Now</a>
            <a href="<?= BASE_URL ?>/properties/index.php" class="btn btn-secondary btn-lg" style="color: #0f172a;">Explore All Properties</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
