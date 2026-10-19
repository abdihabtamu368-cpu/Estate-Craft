</main>

<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <h3 style="display:flex; align-items:center; gap:8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <?= SITE_NAME ?>
            </h3>
            <p><?= SITE_TAGLINE ?>. Empowering Ethiopian home buyers, renters, verified brokers, and direct property owners with complete transparency.</p>
            
            <!-- Quick Language Switcher in Footer -->
            <div style="margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= BASE_URL ?>/user/settings.php?tab=language&lang=en" style="background:#1e293b; color:#cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; text-decoration:none;">🇬🇧 English</a>
                <a href="<?= BASE_URL ?>/user/settings.php?tab=language&lang=am" style="background:#1e293b; color:#cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; text-decoration:none;">🇪🇹 አማርኛ</a>
                <a href="<?= BASE_URL ?>/user/settings.php?tab=language&lang=om" style="background:#1e293b; color:#cbd5e1; padding: 4px 10px; border-radius: 6px; font-size: 12px; text-decoration:none;">🌳 Afaan Oromoo</a>
            </div>
        </div>

        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul class="footer-links">
                <li><a href="<?= BASE_URL ?>/properties/index.php?purpose=sale">Properties for Sale</a></li>
                <li><a href="<?= BASE_URL ?>/properties/index.php?purpose=rent">Homes for Rent</a></li>
                <li><a href="<?= BASE_URL ?>/properties/create.php">List a Property</a></li>
                <li><a href="<?= BASE_URL ?>/properties/index.php?view=map">Satellite Map View</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Settings & Security</h4>
            <ul class="footer-links">
                <li><a href="<?= BASE_URL ?>/user/settings.php?tab=language">Language & Regional Preferences</a></li>
                <li><a href="<?= BASE_URL ?>/user/settings.php?tab=theme">🎨 Theme Mode (Day, Night, Default)</a></li>
                <li><a href="<?= BASE_URL ?>/auth/forgot_password.php">Password Reset Recovery</a></li>
                <li><a href="<?= BASE_URL ?>/user/settings.php?tab=feedback">User Feedback & Rating</a></li>
                <li><a href="<?= BASE_URL ?>/user/settings.php?tab=reports">Safety & Moderation Reports</a></li>
                <li><a href="<?= BASE_URL ?>/user/settings.php?tab=ai_assistant" style="color: #c084fc; font-weight: 600;">✨ Antigravity AI Engine</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Platform Details</h4>
            <ul class="footer-links">
                <li style="color:#94a3b8; font-size:13px;">Region: Ethiopia (Addis Ababa)</li>
                <li style="color:#cbd5e1; font-size:13px;">Currency: Ethiopian Birr (ETB)</li>
                <li style="color:#cbd5e1; font-size:13px;">Mapping: Esri High-Res Satellite</li>
                <li style="color:#cbd5e1; font-size:13px;">Multi-Lang: EN | አማ | OM</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Built for local WampServer development in Ethiopia.</div>
        <div style="display:flex; gap:16px;">
            <a href="<?= BASE_URL ?>/user/settings.php" style="color:#60a5fa;">Settings</a>
            <span style="color:#475569;">|</span>
            <a href="<?= BASE_URL ?>/setup.php" style="color:#94a3b8;">Setup Wizard</a>
            <span style="color:#475569;">|</span>
            <a href="<?= BASE_URL ?>/index.php" style="color:#94a3b8;">Home</a>
        </div>
    </div>
</footer>

<!-- Core Application Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/map.js"></script>

</body>
</html>
