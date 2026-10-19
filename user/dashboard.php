<?php
/**
 * User & Broker Dashboard
 * Displays posted listings, performance metrics, phone verification status, and broker controls
 */

$pageTitle = "My Dashboard";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';

$user = requireAuth();
$pdo = getDB();

// Fetch all listings belonging to this user
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        img.image_path as primary_image,
        (SELECT COUNT(*) FROM listing_reports lr WHERE lr.property_id = p.id AND lr.status = 'pending') as pending_reports
    FROM properties p
    LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
    WHERE p.user_id = :uid
    ORDER BY p.created_at DESC
");
$stmt->execute(['uid' => $user['id']]);
$myProperties = $stmt->fetchAll();

// Calculate Stats
$totalListings = count($myProperties);
$activeListings = 0;
$totalViews = 0;

foreach ($myProperties as $p) {
    if ($p['status'] === 'active') $activeListings++;
    $totalViews += (int)$p['views_count'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                <h1 style="font-size: 28px; font-weight: 800; color: var(--secondary); letter-spacing: -0.02em;">
                    Welcome, <?= htmlspecialchars($user['full_name']) ?>
                </h1>
                <span class="badge badge-<?= $user['role'] === 'broker' ? 'broker' : 'sale' ?>">
                    <?= ucfirst($user['role']) ?>
                </span>
                <?php if ($user['phone_verified']): ?>
                    <span class="badge badge-verified">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Phone Verified
                    </span>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/verify_phone.php" class="badge badge-unverified" style="text-decoration: none;">
                        ⚠️ Verify Phone
                    </a>
                <?php endif; ?>
            </div>
            <p style="color: var(--text-muted); font-size: 14px;">Manage your properties, monitor views, and adjust fee disclosures.</p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="<?= BASE_URL ?>/user/settings.php" class="btn btn-secondary btn-sm">⚙️ Settings</a>
            <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-secondary btn-sm">Edit Profile & Fees</a>
            <a href="<?= BASE_URL ?>/properties/create.php" class="btn btn-primary btn-sm">+ Post New Listing</a>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--primary);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div>
                <div class="stat-val"><?= $totalListings ?></div>
                <div class="stat-label">Total Listings</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
            </div>
            <div>
                <div class="stat-val"><?= $activeListings ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--accent);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </div>
            <div>
                <div class="stat-val"><?= number_format($totalViews) ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: <?= $user['phone_verified'] ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; color: <?= $user['phone_verified'] ? 'var(--success)' : 'var(--danger)' ?>;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
                <div class="stat-val" style="font-size: 16px;">
                    <?= $user['phone_verified'] ? 'Verified' : 'Unverified' ?>
                </div>
                <div class="stat-label"><?= htmlspecialchars($user['phone'] ?? 'No phone') ?></div>
            </div>
        </div>
    </div>

    <!-- Phone Verification Alert if not verified -->
    <?php if (!$user['phone_verified']): ?>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 18px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div>
                    <div style="font-weight: 700; color: #1e40af; font-size: 15px;">Complete Mobile Verification</div>
                    <div style="color: #3b82f6; font-size: 13px;">Buyers and renters strongly prefer contacting verified accounts. Verify with an OTP code in 30 seconds.</div>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/auth/verify_phone.php" class="btn btn-primary btn-sm">Verify Mobile Number</a>
        </div>
    <?php endif; ?>

    <!-- Broker Details Card if User is a Broker -->
    <?php if ($user['role'] === 'broker'): ?>
        <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; margin-bottom: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="color: #92400e; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Your Broker Profile & Public Fee Disclosure
                </h3>
                <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-outline btn-sm" style="color: #b45309; border-color: #f59e0b;">Update Fee Policy</a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 13px; color: #78350f;">
                <div><strong>Agency:</strong> <?= htmlspecialchars($user['broker_agency'] ?: 'Independent Broker') ?></div>
                <div><strong>License #:</strong> <?= htmlspecialchars($user['broker_license_no'] ?: 'Not Provided') ?></div>
                <div><strong>Commission:</strong> <?= htmlspecialchars((string)($user['broker_fee_percentage'] ?? '2.5')) ?>%</div>
                <div><strong>Fee Policy:</strong> <?= htmlspecialchars($user['broker_fee_notes'] ?: 'Standard commission upon closing.') ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- User's Property Listings Table -->
    <h2 style="font-size: 20px; font-weight: 700; color: var(--secondary); margin-bottom: 16px;">My Property Listings</h2>

    <?php if (empty($myProperties)): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 50px 20px; text-align: center;">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin: 0 auto 12px;"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 6px;">You haven't listed any properties yet</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Ready to sell or rent out a home? Post your first listing in minutes.</p>
            <a href="<?= BASE_URL ?>/properties/create.php" class="btn btn-primary">+ Post Your First Property</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Purpose</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Broker Fee</th>
                        <th>Views</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myProperties as $p): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= htmlspecialchars(getPropertyImageUrl($p['primary_image'])) ?>" style="width: 56px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);" alt="Thumbnail">
                                    <div>
                                        <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $p['id'] ?>" style="font-weight: 700; color: var(--secondary); font-size: 14px;">
                                            <?= htmlspecialchars($p['title']) ?>
                                        </a>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <?= htmlspecialchars($p['city']) ?>, <?= htmlspecialchars($p['state'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?= $p['purpose'] === 'sale' ? 'sale' : 'rent' ?>">
                                    <?= ucfirst($p['purpose']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; color: var(--primary);">
                                $<?= number_format($p['price']) ?>
                                <?php if ($p['price_frequency'] === 'monthly'): ?><small style="color: var(--text-muted); font-weight: normal;">/mo</small><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'active'): ?>
                                    <span class="badge badge-verified">Active</span>
                                <?php elseif ($p['status'] === 'flagged'): ?>
                                    <span class="badge badge-unverified">Under Review</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#e2e8f0; color:#475569;"><?= ucfirst($p['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px; color: #475569;">
                                <?= $p['has_broker'] ? htmlspecialchars($p['broker_fee_display'] ?: 'Broker Fee Applies') : 'Direct (No Fee)' ?>
                            </td>
                            <td style="font-weight: 600;">
                                <?= number_format($p['views_count']) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="View Public Page">View</a>
                                    <a href="<?= BASE_URL ?>/properties/edit.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm" title="Edit Listing">Edit</a>
                                    <a href="<?= BASE_URL ?>/properties/delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this listing?');" title="Delete Listing">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
