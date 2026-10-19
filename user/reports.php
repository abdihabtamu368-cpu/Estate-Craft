<?php
/**
 * Safety & Moderation Review Dashboard
 * Allows Admins/Moderators to review reported listings, dismiss reports, or remove fraudulent listings
 */

$pageTitle = "Listing Safety Reports";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Requires admin / moderator role
$admin = requireAdmin();
$pdo = getDB();

// Handle Actions (Dismiss, Flag, Delete, Reinstate)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $reportId   = (int)($_POST['report_id'] ?? 0);
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $action     = $_POST['action'] ?? '';

    if ($action === 'dismiss' && $reportId > 0) {
        $stmt = $pdo->prepare("UPDATE listing_reports SET status = 'dismissed', moderator_notes = 'Dismissed by moderator' WHERE id = :id");
        $stmt->execute(['id' => $reportId]);
        $_SESSION['flash_success'] = "Report #{$reportId} dismissed.";
    } elseif ($action === 'flag' && $propertyId > 0) {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'flagged' WHERE id = :pid");
        $stmt->execute(['pid' => $propertyId]);
        $stmt = $pdo->prepare("UPDATE listing_reports SET status = 'action_taken' WHERE property_id = :pid");
        $stmt->execute(['pid' => $propertyId]);
        $_SESSION['flash_warning'] = "Listing #{$propertyId} has been taken down and flagged as suspicious.";
    } elseif ($action === 'reinstate' && $propertyId > 0) {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'active' WHERE id = :pid");
        $stmt->execute(['pid' => $propertyId]);
        $stmt = $pdo->prepare("UPDATE listing_reports SET status = 'reviewed' WHERE property_id = :pid");
        $stmt->execute(['pid' => $propertyId]);
        $_SESSION['flash_success'] = "Listing #{$propertyId} has been cleared and reinstated as active.";
    } elseif ($action === 'delete_listing' && $propertyId > 0) {
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = :pid");
        $stmt->execute(['pid' => $propertyId]);
        $_SESSION['flash_success'] = "Fraudulent listing #{$propertyId} has been permanently deleted.";
    }

    header("Location: " . BASE_URL . "/user/reports.php");
    exit;
}

// Fetch all reports
$stmt = $pdo->query("
    SELECT 
        r.*,
        p.title as property_title,
        p.price as property_price,
        p.status as property_status,
        p.city as property_city,
        u.full_name as seller_name,
        u.email as seller_email
    FROM listing_reports r
    LEFT JOIN properties p ON r.property_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY r.status = 'pending' DESC, r.created_at DESC
");
$reports = $stmt->fetchAll();

// Fetch all user feedback
$stmtFeedback = $pdo->query("SELECT * FROM user_feedback ORDER BY created_at DESC");
$allFeedback = $stmtFeedback->fetchAll();

$activePage = 'reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--secondary);">Safety & Community Moderation</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Review suspicious property reports and take action to prevent fraud.</p>
        </div>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-secondary btn-sm">&larr; My Dashboard</a>
    </div>

    <?php if (empty($reports)): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 50px 20px; text-align: center;">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="margin: 0 auto 12px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 6px;">Zero Pending Reports!</h3>
            <p style="color: var(--text-muted); font-size: 14px;">All property listings meet community standards.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reported Property</th>
                        <th>Reason Flagged</th>
                        <th>Report Details</th>
                        <th>Reported On</th>
                        <th>Listing Status</th>
                        <th>Review Status</th>
                        <th style="text-align: right;">Moderator Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td>
                                <?php if ($r['property_title']): ?>
                                    <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $r['property_id'] ?>" target="_blank" style="font-weight: 700; color: var(--secondary);">
                                        <?= htmlspecialchars($r['property_title']) ?>
                                    </a>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        Seller: <?= htmlspecialchars($r['seller_name'] ?? 'Unknown') ?> (<?= htmlspecialchars($r['seller_email'] ?? '') ?>)
                                    </div>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">[Listing Deleted]</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background:#fee2e2; color:#b91c1c;">
                                    <?= htmlspecialchars(str_replace('_', ' ', strtoupper($r['reason']))) ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; max-width: 250px;">
                                <?= htmlspecialchars($r['details'] ?: 'No additional comments provided') ?>
                                <?php if ($r['reporter_email']): ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Reporter: <?= htmlspecialchars($r['reporter_email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 13px; color: var(--text-muted);">
                                <?= date('M d, Y', strtotime($r['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($r['property_status'] === 'flagged'): ?>
                                    <span class="badge badge-unverified">Flagged / Hidden</span>
                                <?php elseif ($r['property_status'] === 'active'): ?>
                                    <span class="badge badge-verified">Active</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#e2e8f0; color:#475569;"><?= ucfirst($r['property_status'] ?? 'N/A') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <span class="badge" style="background:#fef3c7; color:#92400e;">Pending</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#e2e8f0; color:#475569;"><?= ucfirst($r['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="dismiss">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Dismiss this report">Dismiss</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($r['property_title'] && $r['property_status'] === 'active'): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="property_id" value="<?= $r['property_id'] ?>">
                                            <input type="hidden" name="action" value="flag">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Take down listing">Flag Listing</button>
                                        </form>
                                    <?php elseif ($r['property_title'] && $r['property_status'] === 'flagged'): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="property_id" value="<?= $r['property_id'] ?>">
                                            <input type="hidden" name="action" value="reinstate">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color:#10b981; border-color:#10b981;">Reinstate</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($r['property_title']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this property?');">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="property_id" value="<?= $r['property_id'] ?>">
                                            <input type="hidden" name="action" value="delete_listing">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- User Feedback Section -->
    <div style="margin-top: 48px;">
        <h2 style="font-size: 22px; font-weight: 800; color: var(--secondary); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <span>💬 User Feedback & Ratings</span>
            <span class="badge" style="background:#e0e7ff; color:#3730a3; font-size:12px;"><?= count($allFeedback) ?> Total</span>
        </h2>

        <?php if (empty($allFeedback)): ?>
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; text-align: center; color: var(--text-muted);">
                No user feedback submitted yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Category</th>
                            <th>Feedback Message</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allFeedback as $fb): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($fb['name']) ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($fb['email']) ?></div>
                                </td>
                                <td><?= str_repeat('⭐', $fb['rating']) ?> (<?= $fb['rating'] ?>/5)</td>
                                <td>
                                    <span class="badge" style="background:#f1f5f9; color:#475569;">
                                        <?= ucfirst(str_replace('_', ' ', $fb['category'])) ?>
                                    </span>
                                </td>
                                <td style="font-size: 13px; max-width: 320px;"><?= nl2br(htmlspecialchars($fb['message'])) ?></td>
                                <td style="font-size: 12px; color: var(--text-muted);"><?= date('M d, Y', strtotime($fb['created_at'])) ?></td>
                                <td>
                                    <span class="badge" style="background: <?= $fb['status'] === 'new' ? '#dbeafe' : '#f1f5f9' ?>; color: <?= $fb['status'] === 'new' ? '#1e40af' : '#475569' ?>;">
                                        <?= ucfirst($fb['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
