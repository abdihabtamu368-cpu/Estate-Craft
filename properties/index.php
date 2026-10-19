<?php
/**
 * Property Catalog & Search Page
 * Supports Buy, Rent, Search Filters, Grid View and Map View
 */

$pageTitle = "Browse Properties";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/upload.php';

$pdo = getDB();

// Capture Query Filters
$purpose = $_GET['purpose'] ?? '';
$type    = $_GET['type'] ?? '';
$location= trim($_GET['location'] ?? '');
$minPrice= isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$maxPrice= isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$bedrooms= isset($_GET['bedrooms']) && is_numeric($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : '';
$brokerFilter = $_GET['broker'] ?? '';
$viewMode = $_GET['view'] ?? 'grid'; // 'grid' or 'map'

// Set active navigation tab
$activePage = 'browse';
if ($purpose === 'sale') $activePage = 'buy';
if ($purpose === 'rent') $activePage = 'rent';

// Build SQL
$where = ["p.status = 'active'"];
$params = [];

if (!empty($purpose) && in_array($purpose, ['sale', 'rent'])) {
    $where[] = "p.purpose = :purpose";
    $params['purpose'] = $purpose;
}

if (!empty($type)) {
    $where[] = "p.property_type = :type";
    $params['type'] = $type;
}

if (!empty($location)) {
    $locTerm = '%' . trim($location) . '%';
    $where[] = "(p.city LIKE :loc1 OR p.state LIKE :loc2 OR p.address LIKE :loc3 OR p.title LIKE :loc4)";
    $params['loc1'] = $locTerm;
    $params['loc2'] = $locTerm;
    $params['loc3'] = $locTerm;
    $params['loc4'] = $locTerm;
}

if ($minPrice !== '') {
    $where[] = "p.price >= :min_price";
    $params['min_price'] = $minPrice;
}

if ($maxPrice !== '') {
    $where[] = "p.price <= :max_price";
    $params['max_price'] = $maxPrice;
}

if ($bedrooms !== '') {
    $where[] = "p.bedrooms >= :beds";
    $params['beds'] = $bedrooms;
}

if ($brokerFilter === 'direct') {
    $where[] = "p.has_broker = 0";
} elseif ($brokerFilter === 'broker') {
    $where[] = "p.has_broker = 1";
}

$sql = "
    SELECT 
        p.*,
        u.full_name as seller_name,
        u.phone_verified as seller_phone_verified,
        u.role as seller_role,
        img.image_path as primary_image
    FROM properties p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
    WHERE " . implode(' AND ', $where) . "
    GROUP BY p.id
    ORDER BY p.featured DESC, p.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 24px 0;">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 26px; font-weight: 800; color: var(--secondary); letter-spacing: -0.02em;">
                    <?php
                    if ($purpose === 'sale') echo "Properties For Sale";
                    elseif ($purpose === 'rent') echo "Properties For Rent";
                    else echo "All Real Estate Listings";
                    ?>
                </h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
                    Showing <?= count($properties) ?> available properties matching your criteria
                </p>
            </div>

            <!-- View Switcher (Grid vs Interactive Map) -->
            <div style="display: flex; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; padding: 4px;">
                <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'grid'])) ?>" class="btn btn-sm <?= $viewMode === 'grid' ? 'btn-primary' : '' ?>" style="box-shadow:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Grid View
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'map'])) ?>" class="btn btn-sm <?= $viewMode === 'map' ? 'btn-primary' : '' ?>" style="box-shadow:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                    Map View
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="" style="background: var(--bg-main); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: flex-end;">
                <div class="form-group">
                    <label class="form-label">Purpose</label>
                    <select name="purpose" class="form-select">
                        <option value="">Buy or Rent (All)</option>
                        <option value="sale" <?= $purpose === 'sale' ? 'selected' : '' ?>>For Sale</option>
                        <option value="rent" <?= $purpose === 'rent' ? 'selected' : '' ?>>For Rent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Property Type</label>
                    <select name="type" class="form-select">
                        <option value="">Any Type</option>
                        <option value="house" <?= $type === 'house' ? 'selected' : '' ?>>House</option>
                        <option value="apartment" <?= $type === 'apartment' ? 'selected' : '' ?>>Apartment / Flat</option>
                        <option value="villa" <?= $type === 'villa' ? 'selected' : '' ?>>Luxury Villa</option>
                        <option value="condo" <?= $type === 'condo' ? 'selected' : '' ?>>Condominium</option>
                        <option value="townhouse" <?= $type === 'townhouse' ? 'selected' : '' ?>>Townhouse</option>
                        <option value="commercial" <?= $type === 'commercial' ? 'selected' : '' ?>>Commercial Space</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Location / City in Ethiopia</label>
                    <input type="text" name="location" class="form-input" placeholder="e.g. Bole, Kazanchis, CMC, Hawassa" value="<?= htmlspecialchars($location) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Min Price (ETB)</label>
                    <input type="number" name="min_price" class="form-input" placeholder="Min" value="<?= htmlspecialchars((string)$minPrice) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Max Price (ETB)</label>
                    <input type="number" name="max_price" class="form-input" placeholder="Max" value="<?= htmlspecialchars((string)$maxPrice) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Brokerage Fee</label>
                    <select name="broker" class="form-select">
                        <option value="">All Listings</option>
                        <option value="direct" <?= $brokerFilter === 'direct' ? 'selected' : '' ?>>Direct Owner (Zero Fee)</option>
                        <option value="broker" <?= $brokerFilter === 'broker' ? 'selected' : '' ?>>Broker Listed</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                    <a href="<?= BASE_URL ?>/properties/index.php" class="btn btn-secondary" title="Reset Filters">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="section-wrapper" style="margin-top: 30px;">
    <?php if ($viewMode === 'map'): ?>
        <!-- Interactive Map Mode with Search Bar -->
        <div style="margin-bottom: 24px;">
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 14px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 10px;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" id="catMapSearchInput" list="catMapLocationsList" class="form-input" placeholder="🔍 Search neighborhood on map (e.g. Bole, Kazanchis, Old Airport, CMC, Hawassa...)" onkeydown="if(event.key === 'Enter'){event.preventDefault(); searchMapLocation(this.value, 'catalog');}" onchange="searchMapLocation(this.value, 'catalog');" autocomplete="off">
                        <datalist id="catMapLocationsList">
                            <option value="Bole">
                            <option value="Bole Atlas">
                            <option value="Kazanchis">
                            <option value="Old Airport">
                            <option value="CMC">
                            <option value="Sarbet">
                            <option value="Piazza">
                            <option value="Megenagna">
                            <option value="Hawassa">
                        </datalist>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="searchMapLocation(document.getElementById('catMapSearchInput').value, 'catalog')">
                        Search on Map
                    </button>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Quick Explore:</span>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('catMapSearchInput').value='Bole'; searchMapLocation('Bole', 'catalog');">📍 Bole</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('catMapSearchInput').value='Kazanchis'; searchMapLocation('Kazanchis', 'catalog');">📍 Kazanchis</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('catMapSearchInput').value='Old Airport'; searchMapLocation('Old Airport', 'catalog');">📍 Old Airport</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('catMapSearchInput').value='CMC'; searchMapLocation('CMC', 'catalog');">📍 CMC</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('catMapSearchInput').value='Hawassa'; searchMapLocation('Hawassa', 'catalog');">📍 Hawassa</button>
                </div>
                <div id="catMapSearchStatus" style="font-size: 13px; color: var(--primary); margin-top: 8px; font-weight: 600;"></div>
            </div>
            <div id="catalogMap" style="height: 540px; border-radius: var(--radius-md); border: 1px solid var(--border-color);"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const apiParams = {
                    purpose: "<?= addslashes($purpose) ?>",
                    type: "<?= addslashes($type) ?>",
                    location: "<?= addslashes($location) ?>",
                    min_price: "<?= addslashes((string)$minPrice) ?>",
                    max_price: "<?= addslashes((string)$maxPrice) ?>"
                };
                initCatalogMap('catalogMap', "<?= BASE_URL ?>/api/get_properties.php", apiParams);
            });
        </script>
    <?php endif; ?>

    <!-- Property Card Grid -->
    <?php if (empty($properties)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 16px; border: 1px solid var(--border-color);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin: 0 auto 16px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <h3 style="font-size: 20px; font-weight: 700; color: var(--secondary); margin-bottom: 8px;">No matching properties found</h3>
            <p style="color: var(--text-muted); font-size: 15px; margin-bottom: 20px;">Try adjusting your search criteria, clearing filters, or browsing other locations.</p>
            <a href="<?= BASE_URL ?>/properties/index.php" class="btn btn-primary">Reset All Filters</a>
        </div>
    <?php else: ?>
        <div class="property-grid">
            <?php foreach ($properties as $prop): ?>
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

                        <!-- Broker Fee Disclosure (Prompt requirement: displayed clearly before contacting) -->
                        <div class="card-broker-disclosure">
                            <?php if ($prop['has_broker']): ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <span><strong>Broker Listed:</strong> <?= htmlspecialchars($prop['broker_fee_display'] ?: 'Standard commission applies') ?></span>
                            <?php else: ?>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                                <span><strong>Direct Owner:</strong> Zero Broker Fee</span>
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
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
