<?php
/**
 * Create New Property Listing
 */

$pageTitle = "Post a New Property";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload.php';

$user = requireAuth();
$pdo = getDB();

$error = '';
$activePage = 'sell';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $title       = trim($_POST['title'] ?? '');
    $purpose     = ($_POST['purpose'] ?? 'sale') === 'rent' ? 'rent' : 'sale';
    $propType    = $_POST['property_type'] ?? 'house';
    $price       = isset($_POST['price']) && is_numeric($_POST['price']) ? (float)$_POST['price'] : 0;
    $frequency   = $_POST['price_frequency'] ?? ($purpose === 'rent' ? 'monthly' : 'total');
    $bedrooms    = isset($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 1;
    $bathrooms   = isset($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : 1;
    $areaSqft    = isset($_POST['area_sqft']) ? (int)$_POST['area_sqft'] : 0;
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $state       = trim($_POST['state'] ?? '');
    $postalCode  = trim($_POST['postal_code'] ?? '');
    $latitude    = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : 9.010793;
    $longitude   = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : 38.761252;
    $description = trim($_POST['description'] ?? '');

    // Contact details
    $contactName  = trim($_POST['contact_name'] ?? $user['full_name']);
    $contactPhone = trim($_POST['contact_phone'] ?? $user['phone']);
    $contactEmail = trim($_POST['contact_email'] ?? $user['email']);

    // Broker representation & fee
    $isBrokerListing = ($user['role'] === 'broker') || (!empty($_POST['has_broker']));
    $brokerFeeDisplay = trim($_POST['broker_fee_display'] ?? '');
    if ($isBrokerListing && empty($brokerFeeDisplay)) {
        $brokerFeeDisplay = !empty($user['broker_fee_percentage']) 
            ? 'Broker Commission: ' . $user['broker_fee_percentage'] . '% upon closing' 
            : 'Standard Broker Fee Applies';
    }

    // Validation
    if (empty($title) || empty($address) || empty($city) || $price <= 0 || empty($description)) {
        $error = "Please fill in all mandatory fields: Title, Price, Address, City, and Description.";
    } else {
        // Slug generation
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

        $stmt = $pdo->prepare("
            INSERT INTO properties (
                user_id, title, slug, description, purpose, property_type, price, price_frequency,
                bedrooms, bathrooms, area_sqft, address, city, state, postal_code,
                latitude, longitude, status, contact_name, contact_phone, contact_email,
                has_broker, broker_fee_display
            ) VALUES (
                :uid, :title, :slug, :desc, :purpose, :type, :price, :freq,
                :beds, :baths, :sqft, :address, :city, :state, :zip,
                :lat, :lng, 'active', :cname, :cphone, :cemail,
                :has_broker, :broker_fee
            )
        ");

        $stmt->execute([
            'uid'         => $user['id'],
            'title'       => $title,
            'slug'        => $slug,
            'desc'        => $description,
            'purpose'     => $purpose,
            'type'        => $propType,
            'price'       => $price,
            'freq'        => $frequency,
            'beds'        => $bedrooms,
            'baths'       => $bathrooms,
            'sqft'        => $areaSqft,
            'address'     => $address,
            'city'        => $city,
            'state'       => $state,
            'zip'         => $postalCode,
            'lat'         => $latitude,
            'lng'         => $longitude,
            'cname'       => $contactName,
            'cphone'      => $contactPhone,
            'cemail'      => $contactEmail,
            'has_broker'  => $isBrokerListing ? 1 : 0,
            'broker_fee'  => $isBrokerListing ? $brokerFeeDisplay : null
        ]);

        $newPropertyId = (int)$pdo->lastInsertId();

        // Handle uploaded images
        if (!empty($_FILES['images'])) {
            $uploadRes = handlePropertyImagesUpload($_FILES['images'], $newPropertyId, $pdo);
            if (!$uploadRes['success'] && !empty($uploadRes['errors'])) {
                $_SESSION['flash_warning'] = "Listing published, but some images failed: " . implode(' ', $uploadRes['errors']);
            }
        }

        $_SESSION['flash_success'] = "🎉 Your property listing has been published successfully!";
        header("Location: " . BASE_URL . "/properties/details.php?id=" . $newPropertyId);
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 900px; margin: 30px auto 60px; padding: 0 24px;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 28px; font-weight: 800; color: var(--secondary); letter-spacing: -0.02em;">Post a Property Listing</h1>
        <p style="color: var(--text-muted); font-size: 15px;">Reach thousands of verified buyers and renters with complete transparency.</p>
    </div>

    <?php if (!$user['phone_verified']): ?>
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; color: #92400e; font-size: 14px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><strong>Phone not verified:</strong> Verify your phone number to earn the trusted "Verified Seller" badge on your listings.</span>
            </div>
            <a href="<?= BASE_URL ?>/auth/verify_phone.php" class="btn btn-sm btn-accent">Verify Phone Now</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-sm);">
        <?= getCsrfInput() ?>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            1. Basic Listing Information
        </h3>

        <div class="form-group" style="margin-bottom: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                <label class="form-label" for="propTitle" style="margin-bottom: 0;">Property Headline / Title *</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">✨ Choose an Option:</span>
                    <select id="titlePresetSelect" class="form-select" style="padding: 6px 12px; font-size: 13px; width: auto; max-width: 320px; height: auto; border-color: var(--primary);" onchange="applyTitlePreset(this.value)">
                        <option value="">-- Click to select popular option --</option>
                        <optgroup label="🏡 Luxury Villas">
                            <option value="Luxury 5-Bedroom Diplomatic Villa with Landscaped Garden">Luxury 5-Bedroom Diplomatic Villa with Garden</option>
                            <option value="Modern 4-Bedroom Gated Villa with 24/7 Security & Parking">Modern 4-Bedroom Gated Villa with Security</option>
                            <option value="Newly Built Executive Villa with European Kitchen & Staff Quarters">Newly Built Executive Villa with Staff Quarters</option>
                            <option value="Scenic Waterfront Villa with Panoramic Lake & Mountain Views">Scenic Waterfront Villa with Panoramic Views</option>
                        </optgroup>
                        <optgroup label="🏢 Apartments & Penthouses">
                            <option value="Modern 3-Bedroom Furnished Penthouse with Panoramic City Views">Modern 3-Bedroom Furnished Penthouse</option>
                            <option value="Spacious 2-Bedroom Luxury Apartment with Balcony & Elevator">Spacious 2-Bedroom Luxury Apartment</option>
                            <option value="Fully Furnished 1-Bedroom Executive Studio with High-Speed WiFi">Fully Furnished 1-Bedroom Studio</option>
                            <option value="Bright & Airy 3-Bedroom Condominium in Prime Neighborhood">Bright & Airy 3-Bedroom Condominium</option>
                        </optgroup>
                        <optgroup label="🏠 Family Compound Houses">
                            <option value="Contemporary 4-Bedroom Family Compound Residence">Contemporary 4-Bedroom Family Compound</option>
                            <option value="Charming 3-Bedroom Compound House with Ample Parking & Greenery">Charming 3-Bedroom House with Ample Parking</option>
                            <option value="Spacious Multi-Story Family Home with Standby Backup Generator">Spacious Multi-Story Family Home</option>
                        </optgroup>
                        <optgroup label="🏬 Commercial, Retail & Office">
                            <option value="Executive Grade-A Commercial Office Floor with Fiber Internet">Executive Grade-A Commercial Office Floor</option>
                            <option value="High-Visibility Main Road Retail Shop & Showroom Space">High-Visibility Main Road Retail Shop</option>
                            <option value="Modern Multi-Story Commercial Building for Corporate Lease">Commercial Building for Corporate Lease</option>
                        </optgroup>
                        <optgroup label="🌿 Land & Development Plots">
                            <option value="Prime Residential Land Plot with Title Deed Ready">Prime Residential Land Plot (Ready Title)</option>
                            <option value="Strategic Commercial Development Plot on Main Asphalt Road">Commercial Development Plot on Main Road</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <input type="text" id="propTitle" name="title" list="titleOptionsDatalist" class="form-input" placeholder="e.g. Sunny 3-Bedroom Villa with Swimming Pool and Mountain View" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required autocomplete="off">
            
            <!-- HTML5 Dropdown Autocomplete List -->
            <datalist id="titleOptionsDatalist">
                <option value="Luxury 5-Bedroom Diplomatic Villa with Landscaped Garden">
                <option value="Modern 4-Bedroom Gated Villa with 24/7 Security & Parking">
                <option value="Newly Built Executive Villa with European Kitchen & Staff Quarters">
                <option value="Scenic Waterfront Villa with Panoramic Lake & Mountain Views">
                <option value="Modern 3-Bedroom Furnished Penthouse with Panoramic City Views">
                <option value="Spacious 2-Bedroom Luxury Apartment with Balcony & Elevator">
                <option value="Fully Furnished 1-Bedroom Executive Studio with High-Speed WiFi">
                <option value="Bright & Airy 3-Bedroom Condominium in Prime Neighborhood">
                <option value="Contemporary 4-Bedroom Family Compound Residence">
                <option value="Charming 3-Bedroom Compound House with Ample Parking & Greenery">
                <option value="Spacious Multi-Story Family Home with Standby Backup Generator">
                <option value="Executive Grade-A Commercial Office Floor with Fiber Internet">
                <option value="High-Visibility Main Road Retail Shop & Showroom Space">
                <option value="Modern Multi-Story Commercial Building for Corporate Lease">
                <option value="Prime Residential Land Plot with Title Deed Ready">
                <option value="Strategic Commercial Development Plot on Main Asphalt Road">
            </datalist>

            <!-- Quick Clickable Fill Badges/Chips -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; align-items: center;">
                <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Quick Fill:</span>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; border-radius: 20px;" onclick="applyTitlePreset('Luxury 5-Bedroom Diplomatic Villa with Landscaped Garden')">🏡 Luxury Villa</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; border-radius: 20px;" onclick="applyTitlePreset('Modern 3-Bedroom Furnished Penthouse with Panoramic City Views')">🏢 Furnished Penthouse</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; border-radius: 20px;" onclick="applyTitlePreset('Contemporary 4-Bedroom Family Compound Residence')">🏠 Family Compound</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; border-radius: 20px;" onclick="applyTitlePreset('Spacious 2-Bedroom Luxury Apartment with Balcony & Elevator')">🛋️ 2-Bed Luxury Condo</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; border-radius: 20px;" onclick="applyTitlePreset('Executive Grade-A Commercial Office Floor with Fiber Internet')">🏬 Commercial Office</button>
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; border-radius: 20px;" onclick="applyTitlePreset('Prime Residential Land Plot with Title Deed Ready')">🌿 Land Plot</button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div class="form-group">
                <label class="form-label" for="propPurpose">Listing Purpose *</label>
                <select id="propPurpose" name="purpose" class="form-select" onchange="updatePriceLabel(this.value)">
                    <option value="sale" <?= ($_POST['purpose'] ?? '') === 'sale' ? 'selected' : '' ?>>For Sale</option>
                    <option value="rent" <?= ($_POST['purpose'] ?? '') === 'rent' ? 'selected' : '' ?>>For Rent</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="propType">Property Type *</label>
                <select id="propType" name="property_type" class="form-select">
                    <option value="house">Single Family House</option>
                    <option value="apartment">Apartment / Flat</option>
                    <option value="villa">Luxury Villa</option>
                    <option value="condo">Condominium</option>
                    <option value="townhouse">Townhouse</option>
                    <option value="commercial">Commercial / Office</option>
                    <option value="land">Land / Plot</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="propPrice" id="priceLabel">Price (<?= CURRENCY_SYMBOL ?>) *</label>
                <input type="number" step="1" id="propPrice" name="price" class="form-input" placeholder="e.g. 4500000" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="form-group">
                <label class="form-label" for="propBeds">Bedrooms</label>
                <input type="number" id="propBeds" name="bedrooms" min="0" max="20" class="form-input" value="<?= htmlspecialchars($_POST['bedrooms'] ?? '3') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="propBaths">Bathrooms</label>
                <input type="number" id="propBaths" name="bathrooms" min="1" max="20" class="form-input" value="<?= htmlspecialchars($_POST['bathrooms'] ?? '2') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="propSqft">Total Area (Sq Ft)</label>
                <input type="number" id="propSqft" name="area_sqft" min="0" class="form-input" placeholder="e.g. 2100" value="<?= htmlspecialchars($_POST['area_sqft'] ?? '') ?>">
            </div>
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            2. Location & Interactive Satellite Map Pin (Ethiopia)
        </h3>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
                <label class="form-label" for="propAddress">Street Address / Neighborhood *</label>
                <input type="text" id="propAddress" name="address" class="form-input" placeholder="e.g. Bole Atlas, near Edna Mall" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="propCity">City *</label>
                <input type="text" id="propCity" name="city" class="form-input" placeholder="e.g. Addis Ababa" value="<?= htmlspecialchars($_POST['city'] ?? 'Addis Ababa') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="propState">Subcity / Region</label>
                <input type="text" id="propState" name="state" class="form-input" placeholder="e.g. Bole Subcity" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
            </div>
        </div>

        <!-- Interactive Pin Map with Search Bar -->
        <div style="margin-bottom: 24px;">
            <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span>Pin Exact Location on Satellite Map (Click or search location)</span>
                <small style="color: var(--primary); font-weight: 600;">Drag pin or search below</small>
            </label>

            <!-- Map Location Search Bar -->
            <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <div style="display: flex; gap: 8px;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" id="mapSearchInput" list="mapLocationsList" class="form-input" placeholder="🔍 Search location (e.g. Bole, Kazanchis, Old Airport, CMC, Hawassa...)" onkeydown="if(event.key === 'Enter'){event.preventDefault(); searchMapLocation(this.value, 'picker');}" onchange="searchMapLocation(this.value, 'picker');" autocomplete="off">
                        <datalist id="mapLocationsList">
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
                            <option value="Mexico">
                            <option value="Ayat">
                            <option value="Hawassa">
                            <option value="Bahir Dar">
                            <option value="Adama">
                            <option value="Bishoftu">
                        </datalist>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="searchMapLocation(document.getElementById('mapSearchInput').value, 'picker')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Search Map
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="useDeviceGpsLocation('picker')" title="Use My Current GPS Position" style="display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                        🎯 My Location
                    </button>
                </div>
                
                <!-- Quick Location Shortcut Badges -->
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Quick Jump:</span>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Bole'; searchMapLocation('Bole', 'picker');">📍 Bole</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Kazanchis'; searchMapLocation('Kazanchis', 'picker');">📍 Kazanchis</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Old Airport'; searchMapLocation('Old Airport', 'picker');">📍 Old Airport</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='CMC'; searchMapLocation('CMC', 'picker');">📍 CMC</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Sarbet'; searchMapLocation('Sarbet', 'picker');">📍 Sarbet</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Piazza'; searchMapLocation('Piazza', 'picker');">📍 Piazza</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 3px 8px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Hawassa'; searchMapLocation('Hawassa', 'picker');">📍 Hawassa</button>
                </div>
                <div id="mapSearchStatus" style="font-size: 12px; color: var(--primary); margin-top: 6px; font-weight: 600;"></div>
            </div>

            <div id="pickerMap" style="height: 320px; border-radius: 8px; border: 1px solid var(--border-color);"></div>

            <!-- Accepted Map Location Confirmation Card -->
            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 10px 14px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">📍</span>
                    <div>
                        <strong style="color: #065f46; font-size: 13px;">Location Accepted From Map:</strong>
                        <span id="acceptedCoordsLabel" style="font-size: 12px; font-weight: 700; color: #047857;">9.010793, 38.761252</span>
                        <div style="font-size: 11.5px; color: #047857;">Clicking on the map or dragging the pin automatically accepts coordinates and updates the address.</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="useDeviceGpsLocation('picker')" style="font-size: 11.5px; padding: 4px 10px;">
                    🎯 GPS Pin
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px;">
                <input type="text" id="propLat" name="latitude" class="form-input" value="9.010793" readonly title="Latitude (Accepted from Map)">
                <input type="text" id="propLng" name="longitude" class="form-input" value="38.761252" readonly title="Longitude (Accepted from Map)">
            </div>
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            3. Property Photos (Multiple Uploads)
        </h3>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="propImages">Select Photos from your Computer (JPEG, PNG, WebP &bull; Max 6MB each)</label>
            <input type="file" id="propImages" name="images[]" class="form-input" multiple accept="image/jpeg,image/png,image/webp">
            <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Hold Ctrl / Shift to select multiple images at once. The first image will be set as primary.</small>
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            4. Description & Detailed Features
        </h3>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="propDesc">Full Property Description *</label>
            <textarea id="propDesc" name="description" rows="5" class="form-textarea" placeholder="Detail the layout, renovations, neighborhood amenities, parking, appliances, and unique features..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            5. Broker Representation & Fee Disclosure
        </h3>

        <?php if ($user['role'] === 'broker'): ?>
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 18px; margin-bottom: 24px;">
                <input type="hidden" name="has_broker" value="1">
                <div style="font-weight: 700; color: #92400e; font-size: 14px; margin-bottom: 8px;">
                    ⭐ Broker Listing Disclosure (Mandatory for Brokers)
                </div>
                <div class="form-group">
                    <label class="form-label" for="brokerFeeDisplay">Commission / Fee Disclosure Text</label>
                    <input type="text" id="brokerFeeDisplay" name="broker_fee_display" class="form-input" value="Broker Fee: <?= htmlspecialchars($user['broker_fee_percentage'] ?? '2.5') ?>% commission upon closing" required>
                    <small style="color: #78350f; font-size: 12px; margin-top: 4px;">This statement is prominently showcased to prospective clients before contact.</small>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="has_broker" value="1" id="hasBrokerCheck" onchange="toggleOwnerBroker(this.checked)">
                    <span style="font-weight: 600; font-size: 14px; color: var(--secondary);">I am hiring or working with a broker for this listing</span>
                </label>
                <div id="ownerBrokerBox" style="display: none; margin-top: 14px;">
                    <label class="form-label" for="brokerFeeDisplay">Disclosed Broker Fee</label>
                    <input type="text" id="brokerFeeDisplay" name="broker_fee_display" class="form-input" placeholder="e.g. 2% Broker Commission payable upon closing">
                </div>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
            6. Public Contact Details
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 30px;">
            <div class="form-group">
                <label class="form-label">Contact Name</label>
                <input type="text" name="contact_name" class="form-input" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Phone</label>
                <input type="tel" name="contact_phone" class="form-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Email</label>
                <input type="email" name="contact_email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitPropertyBtn">
            Publish Property Listing Now &rarr;
        </button>
    </form>
</div>

<script>
function applyTitlePreset(val) {
    if (val) {
        const input = document.getElementById('propTitle');
        input.value = val;
        input.focus();
        // Highlight briefly for immediate visual feedback
        input.style.borderColor = 'var(--primary)';
        input.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.25)';
        setTimeout(() => {
            input.style.boxShadow = '';
        }, 1200);
    }
}

function updatePriceLabel(purpose) {
    const label = document.getElementById('priceLabel');
    label.innerText = purpose === 'rent' ? 'Monthly Rental Price (ETB) *' : 'Total Sale Price (ETB) *';
}

function toggleOwnerBroker(checked) {
    document.getElementById('ownerBrokerBox').style.display = checked ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    initPropertyPickerMap('pickerMap', 'propLat', 'propLng', 9.010793, 38.761252);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
