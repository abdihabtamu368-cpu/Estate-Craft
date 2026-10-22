# EstateCraft &mdash; Premium Real Estate & Obsidian Auth Experience

<div align="center">

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/abdihabtamu368-cpu/Estate-Craft)
[![CI Status](https://img.shields.io/github/actions/workflow/status/abdihabtamu368-cpu/Estate-Craft/ci.yml?branch=main&label=CI%20Build&logo=github&style=flat-square)](https://github.com/abdihabtamu368-cpu/Estate-Craft/actions)
[![Vercel Deployment](https://img.shields.io/badge/Vercel-Deployed-000000?style=flat-square&logo=vercel&logoColor=white)](https://vercel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.4-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Leaflet](https://img.shields.io/badge/Leaflet-Maps-199900?style=flat-square&logo=leaflet&logoColor=white)](https://leafletjs.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

<p align="center">
  <b>A state-of-the-art real estate marketplace portal paired with a Linear & Stripe-caliber Obsidian Authentication experience.</b>
</p>

[Explore Portal](#-portal-features) &bull; [Obsidian Auth Experience](#-next-gen-obsidian-auth-experience) &bull; [Deploy on Vercel](#-deploy-to-vercel-in-60-seconds) &bull; [Local WampServer Setup](#-local-development-wampserver) &bull; [Database Schema](#-database-architecture)

</div>

---

## 🚀 Deploy to Vercel in 60 Seconds

Deploy EstateCraft directly to your personal or team Vercel cloud with one click:

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/abdihabtamu368-cpu/Estate-Craft)

### What Vercel Configures Automatically:
The repository includes a battle-tested [`vercel.json`](file:///vercel.json) that provisions:
- **Clean URLs**: Access `/auth`, `/login`, `/register`, or `/preview` without `.html` extensions.
- **Intelligent Rewrites**: Direct mapping of all authentication routes (`/auth`, `/login`, `/register`, `/signin`, `/signup`) to the next-gen `auth.html` interface.
- **Enterprise Security Headers**: Automated delivery of `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, and `Referrer-Policy`.
- **Global Edge Asset Caching**: 1-year immutable caching for static images, vector assets, and CSS scripts.

---

## 💎 Next-Gen Obsidian Auth Experience

Awwwards & Linear-caliber dark authentication screen designed for ultra-modern digital platforms:

* **Location:** [`auth.html`](file:///auth.html) (Live Web) & [`frontend/AuthScreen.tsx`](file:///frontend/AuthScreen.tsx) (React / TypeScript / Tailwind)
* **Obsidian Palette:** Deep slate/zinc `#08090D` background with layered glassmorphism, 1px semi-transparent borders, and SVG anti-banding film grain.
* **Dynamic Physics & Lighting:**
  * **Interactive Ambient Stardust Canvas:** Micro-particles that gently form proximity constellations and respond dynamically to cursor velocity.
  * **Mouse-Tracking Spotlight Border:** A radial cursor glow that tracks your mouse along the card boundary using CSS variables and mask composites.
  * **3D Tactile Card Tilt:** Smooth perspective response (`perspective(1200px)`) that reacts to mouse coordinates.
* **Component Innovations:**
  * **Spring-Sliding Pill Switcher:** Seamless transition between *Sign In* and *Create Account* without page reload or layout shift.
  * **Social Authentication:** Instant Google, GitHub, and Apple SSO buttons with hover lift and specular border glare.
  * **Dynamic Password Strength Meter:** 4-tier real-time criteria evaluator (Weak, Fair, Good, Strong) with visual feedback.
  * **Primary CTA Glow:** Shimmering gradient sweep, click ripple physics, loading spinner state, and success celebrations.
  * **Haptic Audio Synthesis:** Subtle Web Audio API acoustic clicks (muted by default, toggleable in header).
  * **1-Click Demo Autofill:** Instant population of sample credentials for rapid previewing.

---

## 🌟 Real Estate Portal Features

EstateCraft offers an end-to-end residential and commercial property marketplace:

1. **Smart Search & Filtering**:
   - Segmented search controls for **Buy a Home**, **Rent a House**, and **Commercial Properties**.
   - Multi-criteria filtering by Location / City, Property Type, Bedroom/Bathroom counts, and Price Range.
   - Live Leaflet / OpenStreetMap interactive GPS explorer with custom property pins.

2. **Transparent Broker Representation & Fee Disclosures**:
   - Upfront commission policies: listings clearly disclose broker fees (e.g. `2.5% upon closing` or `$500 lease fee`) before user contact.
   - Direct owner listings are highlighted with an emerald badge: **"Direct Owner Listing &mdash; Zero Broker Fees"**.

3. **Multi-Role Authentication & Safety Center**:
   - Roles for **Buyers / Renters**, **Licensed Real Estate Brokers**, and **Administrators / Moderators**.
   - Verified Mobile SMS workflow with 6-digit OTP verification.
   - Built-in community **Report Listing** queue to quarantine fraudulent or inaccurate listings.

4. **Mortgage Calculator & Financial Tools**:
   - Built-in loan amortization calculator estimating monthly payments based on interest rate, term, and down payment.

---

## 📂 Codebase & Architecture

```text
├── .github/
│   └── workflows/
│       └── ci.yml               # Automated GitHub Actions syntax & asset validation
├── api/                         # Backend AJAX endpoints (PHP)
│   ├── get_properties.php       # Dynamic property search & JSON query API
│   ├── report_listing.php       # Community listing report endpoint
│   └── theme.php                # User dark/light preference storage
├── auth/                        # PHP-based Authentication Handlers
│   ├── login.php                # Session login & CSRF validation
│   ├── register.php             # User & broker onboarding
│   ├── forgot_password.php      # Password recovery handler
│   └── google_callback.php      # OAuth 2.0 Google callback
├── config/                      # Database & environment configurations
│   ├── config.php               # System constants & API keys
│   └── database.php             # PDO database connection provider
├── database/                    # SQL Data Definitions
│   └── schema.sql               # Complete MySQL schema (users, properties, reports)
├── frontend/                    # Frontend UI Modules
│   ├── AuthScreen.tsx           # Reusable React + TypeScript Obsidian Auth component
│   └── css/                     # Core stylesheets
├── auth.html                    # Next-Gen Obsidian Glassmorphic Auth Application
├── index.html                   # High-performance single-page property portal
├── index.php                    # Full dynamic PHP rendering portal
├── setup.php                    # 1-Click Database Setup & Sample Data Seeder
├── vercel.json                  # Production Vercel deployment configuration
└── README.md                    # Project documentation
```

---

## 💻 Local Development (WampServer & PHP)

### Step 1: Start WampServer / XAMPP
1. Launch **WampServer** (or your local Apache + MySQL stack).
2. Ensure Apache and MySQL services are active (Green icon).

### Step 2: Configure Directory
Place this repository in your local server web directory:
- **WampServer:** `C:\wamp64\www\Estate-Craft\`
- **URL:** `http://localhost/Estate-Craft/`

### Step 3: Run 1-Click Database Seeder
Navigate to the web installer in your browser:
```text
http://localhost/Estate-Craft/setup.php
```
Click **"Install Database & Seed Data Now"** to execute `database/schema.sql` and populate sample properties, verified brokers, direct owners, and safety reports.

### Pre-Seeded Sample Accounts

All demo accounts share the password: `Password123!`

| Role | Name | Email | Highlights |
| :--- | :--- | :--- | :--- |
| **Verified Broker** | Sarah Jenkins | `sarah.broker@realestate.local` | Apex Premier Realty, BRE# 01928374, 2.5% fee policy |
| **Direct Owner** | Michael Chang | `michael.chang@example.com` | Zero broker fee seller, verified mobile |
| **Commercial Agent** | Elena Vance | `elena.broker@realestate.local` | Skyline Metro Properties, commercial lease specialist |
| **Safety Admin** | Admin Moderator | `admin@realestate.local` | Safety Reports review queue & listing moderation |

---

## 🔒 Security Implementations

- **100% Prepared Statements:** All database interactions use PDO prepared statements with named parameters to prevent SQL injection.
- **Cryptographic Password Hashing:** Uses `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt).
- **Anti-CSRF Verification:** State-changing requests enforce nonces to prevent cross-site request forgery.
- **MIME & File Validation:** Uploaded media is validated via magic bytes MIME inspection (`image/jpeg`, `image/png`, `image/webp`), sanitized filenames, and strict file size enforcement (max 6MB).
- **XSS Protection:** Context-aware `htmlspecialchars()` escaping across all dynamic outputs.

---

## 🤝 Contributing

1. Fork the Project (`gh repo fork abdihabtamu368-cpu/Estate-Craft`)
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'feat: Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

Distributed under the **MIT License**. See `LICENSE` for more information.
