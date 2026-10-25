<?php
/**
 * Edit Existing Property Listing
 */

$pageTitle = "Edit Property Listing";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload.php';

$user = requireAuth();
$pdo = getDB();

$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propertyId <= 0) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

// Fetch property
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $propertyId]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['flash_error'] = "Property listing not found.";
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

// Ownership Authorization Check
if ($property['user_id'] != $user['id'] && $user['role'] !== 'admin') {
    $_SESSION['flash_error'] = "You are not authorized to edit this listing.";
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

$error = '';

// Handle Image Delete action
if (isset($_GET['delete_img'])) {
    $imgId = (int)$_GET['delete_img'];
    $stmtDelImg = $pdo->prepare("SELECT * FROM property_images WHERE id = :img_id AND property_id = :pid");
    $stmtDelImg->execute(['img_id' => $imgId, 'pid' => $propertyId]);
    $imgRecord = $stmtDelImg->fetch();

    if ($imgRecord) {
        // Delete physical file if local
        if (!preg_match('#^https?://#i', $imgRecord['image_path'])) {
            $filePath = ROOT_PATH . '/' . ltrim($imgRecord['image_path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        $pdo->prepare("DELETE FROM property_images WHERE id = :id")->execute(['id' => $imgId]);
        $_SESSION['flash_success'] = "Image removed.";
        header("Location: " . BASE_URL . "/properties/edit.php?id=" . $propertyId);
        exit;
    }
}

// Handle Update POST
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
    $latitude    = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : 40.7128;
    $longitude   = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : -74.0060;
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status'] ?? 'active';

    // Contact & Broker
    $contactName  = trim($_POST['contact_name'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $hasBroker    = !empty($_POST['has_broker']) ? 1 : 0;
    $brokerFeeDisplay = trim($_POST['broker_fee_display'] ?? '');

    if (empty($title) || empty($address) || empty($city) || $price <= 0 || empty($description)) {
        $error = "Please fill in all mandatory fields: Title, Price, Address, City, and Description.";
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE properties SET 
                title = :title,
                purpose = :purpose,
                property_type = :type,
                price = :price,
                price_frequency = :freq,
                bedrooms = :beds,
                bathrooms = :baths,
                area_sqft = :sqft,
                address = :address,
                city = :city,
                state = :state,
                postal_code = :zip,
                latitude = :lat,
                longitude = :lng,
                description = :desc,
                status = :status,
                contact_name = :cname,
                contact_phone = :cphone,
                contact_email = :cemail,
                has_broker = :has_broker,
                broker_fee_display = :broker_fee
            WHERE id = :id
        ");

        $stmtUpdate->execute([
            'title'       => $title,
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
            'desc'        => $description,
            'status'      => $status,
            'cname'       => $contactName,
            'cphone'      => $contactPhone,
            'cemail'      => $contactEmail,
            'has_broker'  => $hasBroker,
            'broker_fee'  => $hasBroker ? $brokerFeeDisplay : null,
            'id'          => $propertyId
        ]);

        // Upload additional images if provided
        if (!empty($_FILES['images'])) {
            handlePropertyImagesUpload($_FILES['images'], $propertyId, $pdo);
        }

        $_SESSION['flash_success'] = "Listing updated successfully!";
        header("Location: " . BASE_URL . "/properties/details.php?id=" . $propertyId);
        exit;
    }
}

// Fetch images
$stmtImgs = $pdo->prepare("SELECT * FROM property_images WHERE property_id = :id");
$stmtImgs->execute(['id' => $propertyId]);
$images = $stmtImgs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 900px; margin: 30px auto 60px; padding: 0 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--secondary);">Edit Property Listing</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Modify your property details, photos, price, or status.</p>
        </div>
        <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $propertyId ?>" class="btn btn-secondary btn-sm">View Public Listing</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-sm);">
        <?= getCsrfInput() ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                    <label class="form-label" for="propTitle" style="margin-bottom: 0;">Property Title *</label>
                    <select id="titlePresetSelect" class="form-select" style="padding: 4px 10px; font-size: 12px; width: auto; max-width: 260px; height: auto;" onchange="applyTitlePreset(this.value)">
                        <option value="">-- Or pick from options --</option>
                        <option value="Luxury 5-Bedroom Diplomatic Villa with Landscaped Garden">Luxury 5-Bedroom Diplomatic Villa</option>
                        <option value="Modern 4-Bedroom Gated Villa with 24/7 Security & Parking">Modern 4-Bedroom Gated Villa</option>
                        <option value="Modern 3-Bedroom Furnished Penthouse with Panoramic City Views">Modern 3-Bedroom Penthouse</option>
                        <option value="Spacious 2-Bedroom Luxury Apartment with Balcony & Elevator">Spacious 2-Bedroom Luxury Apartment</option>
                        <option value="Contemporary 4-Bedroom Family Compound Residence">Contemporary 4-Bedroom Compound</option>
                        <option value="Executive Grade-A Commercial Office Floor with Fiber Internet">Grade-A Commercial Office</option>
                        <option value="Prime Residential Land Plot with Title Deed Ready">Prime Residential Land Plot</option>
                    </select>
                </div>
                <input type="text" id="propTitle" name="title" list="titleOptionsDatalist" class="form-input" value="<?= htmlspecialchars($property['title']) ?>" required>
                <datalist id="titleOptionsDatalist">
                    <option value="Luxury 5-Bedroom Diplomatic Villa with Landscaped Garden">
                    <option value="Modern 4-Bedroom Gated Villa with 24/7 Security & Parking">
                    <option value="Modern 3-Bedroom Furnished Penthouse with Panoramic City Views">
                    <option value="Spacious 2-Bedroom Luxury Apartment with Balcony & Elevator">
                    <option value="Contemporary 4-Bedroom Family Compound Residence">
                    <option value="Executive Grade-A Commercial Office Floor with Fiber Internet">
                    <option value="Prime Residential Land Plot with Title Deed Ready">
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="propStatus">Listing Status</label>
                <select id="propStatus" name="status" class="form-select">
                    <option value="active" <?= $property['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                    <option value="pending_review" <?= $property['status'] === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="sold" <?= $property['status'] === 'sold' ? 'selected' : '' ?>>Mark as Sold</option>
                    <option value="rented" <?= $property['status'] === 'rented' ? 'selected' : '' ?>>Mark as Rented</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div class="form-group">
                <label class="form-label">Purpose</label>
                <select name="purpose" class="form-select">
                    <option value="sale" <?= $property['purpose'] === 'sale' ? 'selected' : '' ?>>For Sale</option>
                    <option value="rent" <?= $property['purpose'] === 'rent' ? 'selected' : '' ?>>For Rent</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Property Type</label>
                <select name="property_type" class="form-select">
                    <option value="house" <?= $property['property_type'] === 'house' ? 'selected' : '' ?>>House</option>
                    <option value="apartment" <?= $property['property_type'] === 'apartment' ? 'selected' : '' ?>>Apartment</option>
                    <option value="villa" <?= $property['property_type'] === 'villa' ? 'selected' : '' ?>>Villa</option>
                    <option value="condo" <?= $property['property_type'] === 'condo' ? 'selected' : '' ?>>Condo</option>
                    <option value="townhouse" <?= $property['property_type'] === 'townhouse' ? 'selected' : '' ?>>Townhouse</option>
                    <option value="commercial" <?= $property['property_type'] === 'commercial' ? 'selected' : '' ?>>Commercial</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Price (<?= CURRENCY_SYMBOL ?>) *</label>
                <input type="number" name="price" class="form-input" value="<?= htmlspecialchars((string)$property['price']) ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <div class="form-group">
                <label class="form-label">Bedrooms</label>
                <input type="number" name="bedrooms" class="form-input" value="<?= htmlspecialchars((string)$property['bedrooms']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Bathrooms</label>
                <input type="number" name="bathrooms" class="form-input" value="<?= htmlspecialchars((string)$property['bathrooms']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Sq Ft</label>
                <input type="number" name="area_sqft" class="form-input" value="<?= htmlspecialchars((string)$property['area_sqft']) ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-input" value="<?= htmlspecialchars($property['address']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-input" value="<?= htmlspecialchars($property['city']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-input" value="<?= htmlspecialchars($property['state'] ?? '') ?>">
            </div>
        </div>

        <!-- Interactive Map Picker with Search Bar -->
        <div style="margin-bottom: 24px;">
            <label class="form-label" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span>Interactive Map Pin</span>
                <small style="color: var(--primary); font-weight: 600;">Drag pin or search location</small>
            </label>

            <!-- Map Location Search Bar -->
            <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <div style="display: flex; gap: 8px;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" id="mapSearchInput" list="mapLocationsList" class="form-input" placeholder="🔍 Search location (e.g. Bole, Kazanchis, Old Airport, CMC, Hawassa...)" onkeydown="if(event.key === 'Enter'){event.preventDefault(); searchMapLocation(this.value, 'picker');}" onchange="searchMapLocation(this.value, 'picker');" autocomplete="off">
                        <datalist id="mapLocationsList">
                            <option value="Bole">
                            <option value="Bole Atlas">
                            <option value="Kazanchis">
                            <option value="Old Airport">
                            <option value="CMC">
                            <option value="Sarbet">
                            <option value="Piazza">
                            <option value="Hawassa">
                        </datalist>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="searchMapLocation(document.getElementById('mapSearchInput').value, 'picker')">
                        Search Map
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="useDeviceGpsLocation('picker')" title="Use My Current GPS Position" style="display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                        🎯 My Location
                    </button>
                </div>
                
                <!-- Quick Location Shortcut Badges -->
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; align-items: center;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Quick Jump:</span>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Bole'; searchMapLocation('Bole', 'picker');">📍 Bole</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Kazanchis'; searchMapLocation('Kazanchis', 'picker');">📍 Kazanchis</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Old Airport'; searchMapLocation('Old Airport', 'picker');">📍 Old Airport</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='CMC'; searchMapLocation('CMC', 'picker');">📍 CMC</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 2px 7px; font-size: 11px; border-radius: 16px;" onclick="document.getElementById('mapSearchInput').value='Hawassa'; searchMapLocation('Hawassa', 'picker');">📍 Hawassa</button>
                </div>
                <div id="mapSearchStatus" style="font-size: 12px; color: var(--primary); margin-top: 4px; font-weight: 600;"></div>
            </div>

            <div id="pickerMap" style="height: 280px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 8px;"></div>
            
            <!-- Accepted Map Location Confirmation Card -->
            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 8px 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">📍</span>
                    <div>
                        <strong style="color: #065f46; font-size: 12.5px;">Location Accepted From Map:</strong>
                        <span id="acceptedCoordsLabel" style="font-size: 12px; font-weight: 700; color: #047857;"><?= htmlspecialchars((string)$property['latitude']) ?>, <?= htmlspecialchars((string)$property['longitude']) ?></span>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="useDeviceGpsLocation('picker')" style="font-size: 11px; padding: 2px 8px;">
                    🎯 GPS Pin
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <input type="text" id="propLat" name="latitude" class="form-input" value="<?= htmlspecialchars((string)$property['latitude']) ?>" readonly title="Latitude (Accepted from Map)">
                <input type="text" id="propLng" name="longitude" class="form-input" value="<?= htmlspecialchars((string)$property['longitude']) ?>" readonly title="Longitude (Accepted from Map)">
            </div>
        </div>

        <!-- Current Photos Management -->
        <div style="margin-bottom: 24px;">
            <label class="form-label">Current Property Photos</label>
            <?php if (!empty($images)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; margin-top: 8px;">
                    <?php foreach ($images as $img): ?>
                        <div style="position: relative; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: #f8fafc;">
                            <img src="<?= htmlspecialchars(getPropertyImageUrl($img['image_path'])) ?>" style="width: 100%; height: 90px; object-fit: cover;" alt="Photo">
                            <a href="<?= BASE_URL ?>/properties/edit.php?id=<?= $propertyId ?>&delete_img=<?= $img['id'] ?>" onclick="return confirm('Remove this image?');" style="display: block; text-align: center; background: #fee2e2; color: #dc2626; font-size: 11px; font-weight: 700; padding: 4px; text-decoration: none;">
                                Delete Photo
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="font-size: 13px; color: var(--text-muted);">No images currently attached.</p>
            <?php endif; ?>

            <div style="margin-top: 14px;">
                <label class="form-label">Upload Additional Photos</label>
                <input type="file" name="images[]" class="form-input" multiple accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label">Description</label>
            <textarea name="description" rows="5" class="form-textarea" required><?= htmlspecialchars($property['description']) ?></textarea>
        </div>

        <!-- Broker Fee Disclosure -->
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 18px; margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <input type="checkbox" name="has_broker" value="1" <?= $property['has_broker'] ? 'checked' : '' ?>>
                <span style="font-weight: 700; color: #92400e;">Broker Represented Listing</span>
            </label>
            <div class="form-group">
                <label class="form-label" style="color: #78350f;">Disclosed Broker Fee</label>
                <input type="text" name="broker_fee_display" class="form-input" value="<?= htmlspecialchars($property['broker_fee_display'] ?? '') ?>" placeholder="e.g. 2.5% Commission upon closing">
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;">Save Changes</button>
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
function applyTitlePreset(val) {
    if (val) {
        const input = document.getElementById('propTitle');
        input.value = val;
        input.focus();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const lat = <?= (float)$property['latitude'] ?>;
    const lng = <?= (float)$property['longitude'] ?>;
    initPropertyPickerMap('pickerMap', 'propLat', 'propLng', lat, lng);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
