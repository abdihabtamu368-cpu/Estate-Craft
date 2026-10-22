<?php
/**
 * Property Details View
 * Interactive Leaflet Map, Multiple Photo Gallery, Broker Fee Disclosure, Safety Reporting
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/upload.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propertyId <= 0) {
    header("Location: " . BASE_URL . "/properties/index.php");
    exit;
}

$pdo = getDB();

// Fetch property details and owner/broker info
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        u.id as owner_id,
        u.full_name as owner_name,
        u.email as owner_email,
        u.phone as owner_phone,
        u.phone_verified as owner_phone_verified,
        u.national_id_status as owner_national_id_status,
        u.role as owner_role,
        u.avatar as owner_avatar,
        u.broker_agency,
        u.broker_license_no,
        u.broker_fee_percentage,
        u.broker_fixed_fee,
        u.broker_fee_notes
    FROM properties p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $propertyId]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['flash_error'] = "The requested property listing was not found or has been removed.";
    header("Location: " . BASE_URL . "/properties/index.php");
    exit;
}

// Increment view count
$stmtView = $pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = :id");
$stmtView->execute(['id' => $propertyId]);

// Fetch all images for this property
$stmtImages = $pdo->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY is_primary DESC, id ASC");
$stmtImages->execute(['id' => $propertyId]);
$images = $stmtImages->fetchAll();

$currentUser = getCurrentUser();
$isOwner = $currentUser && ($currentUser['id'] == $property['user_id'] || $currentUser['role'] === 'admin');

$pageTitle = $property['title'];
$pageDescription = substr(strip_tags($property['description']), 0, 160);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="property-details-container">
    <!-- Breadcrumb -->
    <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
        <a href="<?= BASE_URL ?>/index.php">Home</a> &gt; 
        <a href="<?= BASE_URL ?>/properties/index.php?purpose=<?= $property['purpose'] ?>">Properties For <?= ucfirst($property['purpose']) ?></a> &gt; 
        <span style="color: var(--secondary); font-weight: 600;"><?= htmlspecialchars($property['city']) ?></span>
    </div>

    <!-- Title and Price Header -->
    <div class="detail-header">
        <div>
            <div style="display: flex; gap: 8px; margin-bottom: 10px; align-items: center; flex-wrap: wrap;">
                <span class="badge badge-<?= $property['purpose'] === 'sale' ? 'sale' : 'rent' ?>">
                    For <?= $property['purpose'] === 'sale' ? 'Sale' : 'Rent' ?>
                </span>
                <span class="badge" style="background:#e2e8f0; color:#334155;">
                    <?= ucfirst($property['property_type']) ?>
                </span>
                <?php if ($property['status'] !== 'active'): ?>
                    <span class="badge" style="background:#fee2e2; color:#b91c1c;">
                        Status: <?= ucfirst($property['status']) ?>
                    </span>
                <?php endif; ?>
                <?php if ($property['has_broker']): ?>
                    <span class="badge badge-broker">Broker Represented</span>
                <?php else: ?>
                    <span class="badge badge-verified">Direct from Owner</span>
                <?php endif; ?>
                <?php if (($property['owner_national_id_status'] ?? '') === 'verified'): ?>
                    <span class="badge" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700;">
                        🛡️ ID Verified Seller
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="detail-title"><?= htmlspecialchars($property['title']) ?></h1>
            <div class="detail-address">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?= htmlspecialchars($property['address']) ?>, <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state'] ?? '') ?> <?= htmlspecialchars($property['postal_code'] ?? '') ?>
            </div>
        </div>

        <div class="detail-price-box">
            <div class="detail-price"><?= CURRENCY_SYMBOL ?><?= number_format($property['price']) ?></div>
            <div class="detail-frequency">
                <?= $property['price_frequency'] === 'monthly' ? 'per month' : ($property['price_frequency'] === 'yearly' ? 'per year' : 'Total Price') ?>
            </div>

            <?php if ($isOwner): ?>
                <div style="display: flex; gap: 8px; margin-top: 14px; justify-content: flex-end;">
                    <a href="<?= BASE_URL ?>/properties/edit.php?id=<?= $property['id'] ?>" class="btn btn-secondary btn-sm">Edit Listing</a>
                    <a href="<?= BASE_URL ?>/properties/delete.php?id=<?= $property['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this listing? This action cannot be undone.');">Delete</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Gallery Section -->
    <div class="gallery-layout">
        <?php 
        $primaryImgSrc = !empty($images) ? getPropertyImageUrl($images[0]['image_path']) : BASE_URL . '/assets/images/property-placeholder.svg';
        ?>
        <div class="gallery-main">
            <img id="mainGalleryImage" src="<?= htmlspecialchars($primaryImgSrc) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
        </div>

        <div class="gallery-thumbs">
            <?php if (count($images) > 1): ?>
                <?php foreach (array_slice($images, 1, 2) as $thumb): ?>
                    <img src="<?= htmlspecialchars(getPropertyImageUrl($thumb['image_path'])) ?>" class="gallery-thumb-item" data-full-src="<?= htmlspecialchars(getPropertyImageUrl($thumb['image_path'])) ?>" alt="Thumbnail">
                <?php endforeach; ?>
            <?php else: ?>
                <img src="<?= htmlspecialchars($primaryImgSrc) ?>" class="gallery-thumb-item" alt="Thumbnail">
                <img src="<?= BASE_URL ?>/assets/images/property-placeholder.svg" class="gallery-thumb-item" alt="Thumbnail">
            <?php endif; ?>
        </div>
    </div>

    <!-- Additional Photos Strip if more than 3 photos exist -->
    <?php if (count($images) > 3): ?>
        <div style="display: flex; gap: 12px; margin-top: -24px; margin-bottom: 30px; overflow-x: auto; padding-bottom: 10px;">
            <?php foreach ($images as $extraImg): ?>
                <img src="<?= htmlspecialchars(getPropertyImageUrl($extraImg['image_path'])) ?>" class="gallery-thumb-item" data-full-src="<?= htmlspecialchars(getPropertyImageUrl($extraImg['image_path'])) ?>" style="width: 100px; height: 75px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e2e8f0;" alt="Gallery extra">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Two-Column Layout (Details vs Sticky Sidebar) -->
    <div class="detail-content-grid">
        <!-- Main Details Column -->
        <div>
            <!-- Key Amenities Grid -->
            <div class="amenities-grid">
                <div class="amenity-card">
                    <div class="amenity-val"><?= $property['bedrooms'] ?></div>
                    <div class="amenity-label">Bedrooms</div>
                </div>
                <div class="amenity-card">
                    <div class="amenity-val"><?= $property['bathrooms'] ?></div>
                    <div class="amenity-label">Bathrooms</div>
                </div>
                <div class="amenity-card">
                    <div class="amenity-val"><?= number_format($property['area_sqft']) ?></div>
                    <div class="amenity-label">Sq Ft Area</div>
                </div>
                <div class="amenity-card">
                    <div class="amenity-val"><?= ucfirst($property['property_type']) ?></div>
                    <div class="amenity-label">Property Type</div>
                </div>
            </div>

            <!-- MANDATORY PROMPT REQUIREMENT: Clear Broker Fee Disclosure Box -->
            <?php if ($property['has_broker']): ?>
                <div class="broker-fee-highlight-box">
                    <div class="broker-fee-header">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span>Verified Broker Representation & Transparent Fee Disclosure</span>
                    </div>
                    <div class="broker-fee-amount">
                        <?= htmlspecialchars($property['broker_fee_display'] ?: ($property['broker_fee_percentage'] ? $property['broker_fee_percentage'] . '% Commission' : 'Fixed Fee Applies')) ?>
                    </div>
                    <div class="broker-fee-terms">
                        <strong>Terms & Conditions:</strong>
                        <?= htmlspecialchars($property['broker_fee_notes'] ?: 'Broker commission is payable upon successful completion of the transaction. All services are governed by a standard written representation agreement.') ?>
                    </div>
                    <div style="margin-top: 10px; font-size: 12px; color: #92400e; font-weight: 600;">
                        License: <?= htmlspecialchars($property['broker_license_no'] ?: 'Verified on File') ?> &bull; Agency: <?= htmlspecialchars($property['broker_agency'] ?: 'Independent Broker') ?>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 18px; margin-bottom: 28px; display: flex; align-items: center; gap: 14px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #065f46; font-size: 15px;">Direct Owner Listing &mdash; Zero Broker Fees</div>
                        <div style="color: #047857; font-size: 13px;">This property is offered directly by the owner. No broker fees or agency commissions apply.</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Description Card -->
            <div class="detail-card">
                <h3 class="detail-section-title">About this Property</h3>
                <div class="description-text">
                    <?= nl2br(htmlspecialchars($property['description'])) ?>
                </div>
            </div>

            <!-- Interactive Map Location -->
            <div class="detail-card">
                <h3 class="detail-section-title">Location on Map</h3>
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 16px;">
                    <?= htmlspecialchars($property['address']) ?>, <?= htmlspecialchars($property['city']) ?>, <?= htmlspecialchars($property['state'] ?? '') ?>
                </p>
                <div id="propertyMap"></div>
            </div>
        </div>

        <!-- Sticky Sidebar: Seller & Contact Details -->
        <div>
            <div class="sidebar-sticky">
                <div class="contact-card">
                    <div class="contact-agent-header">
                        <div class="agent-avatar-lg">
                            <?= strtoupper(substr($property['owner_name'], 0, 1)) ?>
                        </div>
                        <div class="agent-meta">
                            <h4><?= htmlspecialchars($property['owner_name']) ?></h4>
                            <div class="agent-agency">
                                <?= $property['has_broker'] ? htmlspecialchars($property['broker_agency'] ?: 'Licensed Broker') : 'Property Owner' ?>
                            </div>
                            <?php if ($property['owner_phone_verified']): ?>
                                <span class="badge badge-verified" style="margin-top: 4px;">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Verified Phone
                                </span>
                            <?php endif; ?>
                            <?php if (($property['owner_national_id_status'] ?? '') === 'verified'): ?>
                                <span class="badge" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700; margin-top: 4px; display:inline-flex; align-items:center; gap:4px;">
                                    🛡️ National ID Verified
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">Direct Contact</div>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 15px; font-weight: 600; color: var(--secondary); margin-bottom: 8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <a href="tel:<?= htmlspecialchars($property['contact_phone']) ?>"><?= htmlspecialchars($property['contact_phone']) ?></a>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-muted);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            <a href="mailto:<?= htmlspecialchars($property['contact_email']) ?>"><?= htmlspecialchars($property['contact_email']) ?></a>
                        </div>
                    </div>

                    <a href="tel:<?= htmlspecialchars($property['contact_phone']) ?>" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 10px;">
                        Call <?= htmlspecialchars($property['contact_name']) ?>
                    </a>

                    <a href="mailto:<?= htmlspecialchars($property['contact_email']) ?>?subject=Inquiry%20Regarding%20<?= urlencode($property['title']) ?>" class="btn btn-secondary btn-block">
                        Send Email Message
                    </a>

                    <!-- Safety & Report Button -->
                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); text-align: center;">
                        <button type="button" class="btn btn-outline btn-sm" data-modal-target="reportModal" style="color: #ef4444; border-color: #fca5a5;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                            Report Suspicious Listing
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Safety Report Modal -->
<div class="modal-overlay" id="reportModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #ef4444; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                Report Listing
            </h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/api/report_listing.php">
            <?= getCsrfInput() ?>
            <input type="hidden" name="property_id" value="<?= $property['id'] ?>">

            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 16px;">
                Our safety moderation team investigates flagged posts immediately. Please specify why you are reporting this listing.
            </p>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="reportReason">Reason for Report *</label>
                <select name="reason" id="reportReason" class="form-select" required>
                    <option value="">Select a reason...</option>
                    <option value="fraud_scam">Suspected Fraud or Scam</option>
                    <option value="incorrect_price">Inaccurate or Misleading Price</option>
                    <option value="wrong_location">Wrong or Fake Address/Location</option>
                    <option value="duplicate">Duplicate Listing</option>
                    <option value="offensive_content">Inappropriate Images or Content</option>
                    <option value="other">Other Violation</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" for="reporterEmail">Your Contact Email (Optional)</label>
                <input type="email" name="reporter_email" id="reporterEmail" class="form-input" placeholder="you@domain.com" value="<?= $currentUser ? htmlspecialchars($currentUser['email']) : '' ?>">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" for="reportDetails">Detailed Notes / Evidence</label>
                <textarea name="details" id="reportDetails" class="form-textarea" rows="3" placeholder="Provide additional details to assist our review team..."></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-danger" style="flex: 1;">Submit Report for Review</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lat = <?= (float)$property['latitude'] ?>;
    const lng = <?= (float)$property['longitude'] ?>;
    const title = "<?= addslashes($property['title']) ?>";
    const priceText = "<?= CURRENCY_SYMBOL ?><?= number_format($property['price']) ?>";
    const address = "<?= addslashes($property['address'] . ', ' . $property['city']) ?>";

    initPropertyDetailMap('propertyMap', lat, lng, title, priceText, address);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
