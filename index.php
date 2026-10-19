<?php
/**
 * EstateCraft - Homepage
 * Buy, Sell, and Rent Real Estate with Interactive Map, Transparent Broker Fees & Verified Accounts
 */

$pageTitle = "Find, Buy, Sell & Rent Exceptional Homes";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/upload.php';

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

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-pattern"></div>
    <div class="hero-content">
        <div class="hero-tag">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            Transparent Real Estate &bull; Verified Brokers &bull; Direct Owners
        </div>

        <h1 class="hero-title">
            <?= __('hero_title', 'The Smarter Way to Buy, Rent & Sell Exceptional Homes Across Ethiopia') ?>
        </h1>
        <p class="hero-subtitle">
            <?= __('hero_subtitle', 'Browse verified listings, view upfront broker fees before contacting agents, and explore neighborhoods with satellite map search.') ?>
        </p>

        <!-- Search Bar Card -->
        <div class="search-box-card">
            <div class="search-tabs">
                <button type="button" class="tab-btn active" data-purpose=""><?= __('tab_all', 'All Properties') ?></button>
                <button type="button" class="tab-btn" data-purpose="sale"><?= __('tab_buy', 'Buy a Home') ?></button>
                <button type="button" class="tab-btn" data-purpose="rent"><?= __('tab_rent', 'Rent a House') ?></button>
            </div>

            <form method="GET" action="<?= BASE_URL ?>/properties/index.php">
                <input type="hidden" name="purpose" id="searchPurposeInput" value="">

                <div class="search-grid">
                    <div class="form-group">
                        <label class="form-label" for="searchLocation">Location / City in Ethiopia</label>
                        <input type="text" id="searchLocation" name="location" class="form-input" placeholder="e.g. Bole, Kazanchis, CMC, Hawassa">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="searchType">Property Type</label>
                        <select id="searchType" name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="house">Houses</option>
                            <option value="apartment">Apartments / Condos</option>
                            <option value="villa">Luxury Villas</option>
                            <option value="townhouse">Townhouses</option>
                            <option value="commercial">Commercial Space</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="searchPrice">Max Price (ETB)</label>
                        <select id="searchPrice" name="max_price" class="form-select">
                            <option value="">Any Price</option>
                            <option value="50000">ETB 50,000 / mo</option>
                            <option value="100000">ETB 100,000 / mo</option>
                            <option value="20000000">ETB 20,000,000</option>
                            <option value="40000000">ETB 40,000,000</option>
                            <option value="60000000">ETB 60,000,000+</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="btnMainSearch">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <?= __('search_btn', 'Search Homes') ?>
                    </button>
                </div>
            </form>
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
