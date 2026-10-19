/**
 * EstateCraft Ethiopia - Frontend JavaScript
 * Handles dynamic property rendering, live search & filtering, modals, and Day/Night theme toggling
 */

// Sample property database (Addis Ababa & Regional Ethiopia)
const initialProperties = [
  {
    id: 1,
    title: "Luxury 5-Bedroom Diplomatic Villa",
    purpose: "sale",
    type: "villa",
    price: 65000000,
    priceFrequency: "total",
    city: "Old Airport, Addis Ababa",
    bedrooms: 5,
    bathrooms: 6,
    areaSqft: 6500,
    image: "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Sara Tefera (Apex Realty)",
    sellerRole: "broker",
    sellerPhone: "+251 91 123 4567",
    sellerVerified: true,
    hasBroker: true,
    brokerFee: "2.0% commission upon closing",
    description: "Exceptional luxury villa situated in the quiet diplomatic enclave of Old Airport. Features expansive landscaped gardens, backup generator, high security perimeter, modern kitchen with European appliances, and dedicated staff quarters."
  },
  {
    id: 2,
    title: "Modern 3-Bedroom Furnished Penthouse",
    purpose: "rent",
    type: "apartment",
    price: 180000,
    priceFrequency: "monthly",
    city: "Bole Atlas, Addis Ababa",
    bedrooms: 3,
    bathrooms: 3,
    areaSqft: 2400,
    image: "https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Dawit Bekele",
    sellerRole: "user",
    sellerPhone: "+251 92 345 6789",
    sellerVerified: true,
    hasBroker: false,
    brokerFee: "Zero Broker Fees (Direct Owner)",
    description: "Fully furnished penthouse with panoramic city views over Bole. Modern open layout, high-speed fiber internet, elevator access with biometric keycard, underground parking, and 24/7 security."
  },
  {
    id: 3,
    title: "Executive Grade-A Commercial Office Floor",
    purpose: "rent",
    type: "commercial",
    price: 450000,
    priceFrequency: "monthly",
    city: "Kazanchis, Addis Ababa",
    bedrooms: 0,
    bathrooms: 4,
    areaSqft: 5200,
    image: "https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Elena Vance (Skyline Metro)",
    sellerRole: "broker",
    sellerPhone: "+251 93 456 7890",
    sellerVerified: true,
    hasBroker: true,
    brokerFee: "One month rental placement fee",
    description: "Centrally positioned in the financial district of Kazanchis, walking distance to UNECA and luxury hotels. Fitted with central HVAC, 10 dedicated parking bays, fiber optic backbone, and emergency dual-generators."
  },
  {
    id: 4,
    title: "Contemporary 4-Bedroom Family Residence",
    purpose: "sale",
    type: "house",
    price: 34000000,
    priceFrequency: "total",
    city: "CMC Michael, Addis Ababa",
    bedrooms: 4,
    bathrooms: 4,
    areaSqft: 3800,
    image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Yonas Mengistu",
    sellerRole: "user",
    sellerPhone: "+251 94 567 8901",
    sellerVerified: true,
    hasBroker: false,
    brokerFee: "Zero Broker Fees (Direct Owner)",
    description: "Brand new contemporary compound residence in CMC Michael. Features high ceilings, hardwood flooring, solar water heating, 3-car gated parking, and quiet residential neighborhood with easy light rail access."
  },
  {
    id: 5,
    title: "Spacious 2-Bedroom Condo with Balcony",
    purpose: "rent",
    type: "apartment",
    price: 75000,
    priceFrequency: "monthly",
    city: "Sarbet, Addis Ababa",
    bedrooms: 2,
    bathrooms: 2,
    areaSqft: 1450,
    image: "https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Sara Tefera (Apex Realty)",
    sellerRole: "broker",
    sellerPhone: "+251 91 123 4567",
    sellerVerified: true,
    hasBroker: true,
    brokerFee: "Half-month rental placement fee",
    description: "Bright and sunny modern apartment in Sarbet, near the African Union. Large balcony, contemporary open kitchen with breakfast bar, master suite with walk-in closet, and dependable water reservoir system."
  },
  {
    id: 6,
    title: "Scenic Waterfront Villa & Retreat",
    purpose: "sale",
    type: "villa",
    price: 28000000,
    priceFrequency: "total",
    city: "Lake Awassa, Hawassa",
    bedrooms: 4,
    bathrooms: 4,
    areaSqft: 4200,
    image: "https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Michael Chang",
    sellerRole: "user",
    sellerPhone: "+251 95 678 9012",
    sellerVerified: true,
    hasBroker: false,
    brokerFee: "Zero Broker Fees (Direct Owner)",
    description: "Stunning lakeside retreat on the shores of Lake Hawassa. Features wrap-around terraces, lush tropical fruit garden, private boat dock access, solar power system, and tranquil bird sanctuary surroundings."
  }
];

let properties = [...initialProperties];
let currentTabFilter = "";

document.addEventListener('DOMContentLoaded', function () {
  initTheme();
  renderProperties(properties);
  setupEventListeners();
  initMap();
});

// Theme Management (Day/Night)
function initTheme() {
  const savedTheme = localStorage.getItem('estate_theme') || 'default';
  applyTheme(savedTheme);

  const toggleBtn = document.getElementById('themeToggleBtn');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      const nextTheme = (current === 'dark') ? 'light' : 'dark';
      applyTheme(nextTheme);
      localStorage.setItem('estate_theme', nextTheme);
    });
  }
}

function applyTheme(theme) {
  const isDark = (theme === 'dark');
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  if (isDark) {
    document.body.classList.add('theme-dark');
  } else {
    document.body.classList.remove('theme-dark');
  }

  const icon = document.getElementById('themeHeaderIcon');
  const label = document.getElementById('themeHeaderLabel');
  if (icon && label) {
    icon.textContent = isDark ? '🌙' : '☀️';
    label.textContent = isDark ? 'Night' : 'Day';
  }
}

// Render Property Cards
function renderProperties(list) {
  const grid = document.getElementById('propertyGrid');
  if (!grid) return;

  if (list.length === 0) {
    grid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px dashed var(--border-color);">
        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">No Properties Match Your Search</h3>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">Try adjusting your keyword, changing the property type, or widening your price range.</p>
        <button class="btn btn-primary btn-sm" onclick="resetFilters()">Reset All Filters</button>
      </div>
    `;
    return;
  }

  grid.innerHTML = list.map(prop => `
    <div class="property-card" data-id="${prop.id}">
      <div class="card-img-wrap">
        <img src="${prop.image}" alt="${escapeHtml(prop.title)}" loading="lazy">
        <div class="card-badges">
          <span class="badge ${prop.purpose === 'sale' ? 'badge-sale' : 'badge-rent'}">
            For ${prop.purpose === 'sale' ? 'Sale' : 'Rent'}
          </span>
          <span class="badge" style="background: rgba(15, 23, 42, 0.85); color: #fff;">
            ${capitalize(prop.type)}
          </span>
        </div>
        <div class="card-price-tag">
          ETB ${formatNumber(prop.price)}
          ${prop.priceFrequency === 'monthly' ? '<span>/mo</span>' : ''}
        </div>
      </div>

      <div class="card-body">
        <div class="card-title" onclick="openDetailsModal(${prop.id})">
          ${escapeHtml(prop.title)}
        </div>

        <div class="card-location">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          ${escapeHtml(prop.city)}
        </div>

        <div class="card-features">
          <div class="card-feat-item" title="Bedrooms">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/></svg>
            <span>${prop.bedrooms} Beds</span>
          </div>
          <div class="card-feat-item" title="Bathrooms">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h6M9 10h6m-9 4h12a2 2 0 0 1 2 2v1a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-1a2 2 0 0 1 2-2z"/></svg>
            <span>${prop.bathrooms} Baths</span>
          </div>
          <div class="card-feat-item" title="Area">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            <span>${formatNumber(prop.areaSqft)} sqft</span>
          </div>
        </div>

        <div class="card-broker-disclosure">
          ${prop.hasBroker 
            ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
               <span><strong>Broker Listed:</strong> ${escapeHtml(prop.brokerFee)}</span>`
            : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
               <span><strong>Direct Owner:</strong> Zero Broker Fees</span>`
          }
        </div>

        <div class="card-footer">
          <div class="seller-info">
            <div class="seller-avatar">${prop.sellerName.charAt(0).toUpperCase()}</div>
            <div>
              <div class="seller-name">${escapeHtml(prop.sellerName)}</div>
              ${prop.sellerVerified ? `
                <span class="badge badge-verified" style="font-size: 10px; padding: 1px 6px;">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                  Verified
                </span>` : ''}
            </div>
          </div>

          <button class="btn btn-outline btn-sm" onclick="openDetailsModal(${prop.id})">
            View Details &rarr;
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

// Search and Filter Handling
function setupEventListeners() {
  // Tabs (All / Buy / Rent)
  const tabs = document.querySelectorAll('.search-tabs .tab-btn');
  tabs.forEach(btn => {
    btn.addEventListener('click', function () {
      tabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      currentTabFilter = this.getAttribute('data-purpose') || "";
      filterProperties();
    });
  });

  // Search input and dropdowns
  const searchForm = document.getElementById('mainSearchForm');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      filterProperties();
    });
  }

  const locationInput = document.getElementById('searchLocation');
  const typeSelect = document.getElementById('searchType');
  const priceSelect = document.getElementById('searchPrice');

  if (locationInput) locationInput.addEventListener('input', filterProperties);
  if (typeSelect) typeSelect.addEventListener('change', filterProperties);
  if (priceSelect) priceSelect.addEventListener('change', filterProperties);
}

function filterProperties() {
  const query = (document.getElementById('searchLocation')?.value || "").toLowerCase().trim();
  const type = document.getElementById('searchType')?.value || "";
  const maxPrice = parseFloat(document.getElementById('searchPrice')?.value) || 0;

  const filtered = properties.filter(prop => {
    // Purpose filter from tabs
    if (currentTabFilter && prop.purpose !== currentTabFilter) {
      return false;
    }
    // Location / Title search query
    if (query && !prop.city.toLowerCase().includes(query) && !prop.title.toLowerCase().includes(query)) {
      return false;
    }
    // Property Type
    if (type && prop.type !== type) {
      return false;
    }
    // Max Price
    if (maxPrice > 0 && prop.price > maxPrice) {
      return false;
    }
    return true;
  });

  renderProperties(filtered);
}

function resetFilters() {
  currentTabFilter = "";
  const tabs = document.querySelectorAll('.search-tabs .tab-btn');
  tabs.forEach(t => t.classList.remove('active'));
  if (tabs[0]) tabs[0].classList.add('active');

  const loc = document.getElementById('searchLocation');
  const type = document.getElementById('searchType');
  const price = document.getElementById('searchPrice');

  if (loc) loc.value = "";
  if (type) type.value = "";
  if (price) price.value = "";

  renderProperties(properties);
}

// Quick Details Modal
function openDetailsModal(id) {
  const prop = properties.find(p => p.id === id);
  if (!prop) return;

  const modal = document.getElementById('propertyModal');
  const content = document.getElementById('modalContent');
  if (!modal || !content) return;

  content.innerHTML = `
    <div style="position: relative; height: 300px; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
      <img src="${prop.image}" alt="${escapeHtml(prop.title)}" style="width: 100%; height: 100%; object-fit: cover;">
      <div style="position: absolute; top: 16px; left: 16px; display: flex; gap: 8px;">
        <span class="badge ${prop.purpose === 'sale' ? 'badge-sale' : 'badge-rent'}">
          For ${prop.purpose === 'sale' ? 'Sale' : 'Rent'}
        </span>
        <span class="badge" style="background: rgba(15, 23, 42, 0.85); color: #fff;">
          ${capitalize(prop.type)}
        </span>
      </div>
      <div style="position: absolute; bottom: 16px; right: 16px; background: rgba(15, 23, 42, 0.9); color: #fff; padding: 8px 16px; border-radius: 8px; font-weight: 800; font-size: 20px;">
        ETB ${formatNumber(prop.price)} ${prop.priceFrequency === 'monthly' ? '<span style="font-size: 13px; font-weight: 400; color: #94a3b8;">/month</span>' : ''}
      </div>
    </div>

    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px; color: var(--text-main);">${escapeHtml(prop.title)}</h2>
    <div style="display: flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 14px; margin-bottom: 16px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
      ${escapeHtml(prop.city)}
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; background: var(--bg-main); padding: 14px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
      <div>
        <div style="font-size: 18px; font-weight: 800; color: var(--primary);">${prop.bedrooms}</div>
        <div style="font-size: 12px; color: var(--text-muted);">Bedrooms</div>
      </div>
      <div>
        <div style="font-size: 18px; font-weight: 800; color: var(--primary);">${prop.bathrooms}</div>
        <div style="font-size: 12px; color: var(--text-muted);">Bathrooms</div>
      </div>
      <div>
        <div style="font-size: 18px; font-weight: 800; color: var(--primary);">${formatNumber(prop.areaSqft)}</div>
        <div style="font-size: 12px; color: var(--text-muted);">Sq. Footage</div>
      </div>
    </div>

    <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">About This Property</h3>
    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
      ${escapeHtml(prop.description)}
    </p>

    <!-- Broker Disclosure Notice -->
    <div style="background: ${prop.hasBroker ? '#fffbeb' : '#ecfdf5'}; border: 1px solid ${prop.hasBroker ? '#fde68a' : '#a7f3d0'}; border-radius: 10px; padding: 12px 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
      <div style="font-size: 20px;">${prop.hasBroker ? '📋' : '🛡️'}</div>
      <div>
        <div style="font-size: 13px; font-weight: 700; color: ${prop.hasBroker ? '#92400e' : '#065f46'};">
          ${prop.hasBroker ? 'Broker Representation & Fee Disclosure' : 'Direct Owner Listing - No Broker Fees'}
        </div>
        <div style="font-size: 12px; color: ${prop.hasBroker ? '#78350f' : '#047857'};">
          ${escapeHtml(prop.brokerFee)}
        </div>
      </div>
    </div>

    <!-- Contact Seller Card -->
    <div style="border-top: 1px solid var(--border-color); padding-top: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: gap; gap: 16px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div class="seller-avatar" style="width: 44px; height: 44px; font-size: 18px;">${prop.sellerName.charAt(0).toUpperCase()}</div>
        <div>
          <div style="font-weight: 700; font-size: 15px;">${escapeHtml(prop.sellerName)}</div>
          <div style="font-size: 13px; color: var(--text-muted);">${escapeHtml(prop.sellerPhone)}</div>
        </div>
      </div>

      <a href="tel:${prop.sellerPhone.replace(/\\s+/g, '')}" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        Call Contact
      </a>
    </div>
  `;

  modal.classList.add('active');
}

function closeModal() {
  const modal = document.getElementById('propertyModal');
  if (modal) modal.classList.remove('active');

  const postModal = document.getElementById('postPropertyModal');
  if (postModal) postModal.classList.remove('active');
}

function openPostModal() {
  const postModal = document.getElementById('postPropertyModal');
  if (postModal) postModal.classList.add('active');
}

function handlePostProperty(event) {
  event.preventDefault();
  const title = document.getElementById('newTitle').value.trim();
  const purpose = document.getElementById('newPurpose').value;
  const type = document.getElementById('newType').value;
  const price = parseFloat(document.getElementById('newPrice').value) || 0;
  const city = document.getElementById('newCity').value.trim();
  const beds = parseInt(document.getElementById('newBeds').value) || 1;
  const baths = parseInt(document.getElementById('newBaths').value) || 1;
  const sqft = parseInt(document.getElementById('newSqft').value) || 1000;
  const desc = document.getElementById('newDesc').value.trim();

  const newProp = {
    id: Date.now(),
    title: title || "Modern Property",
    purpose: purpose,
    type: type,
    price: price,
    priceFrequency: purpose === 'rent' ? 'monthly' : 'total',
    city: city || "Addis Ababa, Ethiopia",
    bedrooms: beds,
    bathrooms: baths,
    areaSqft: sqft,
    image: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1000&q=80",
    sellerName: "Direct Property Owner",
    sellerRole: "user",
    sellerPhone: "+251 91 000 0000",
    sellerVerified: true,
    hasBroker: false,
    brokerFee: "Zero Broker Fees (Direct Owner)",
    description: desc || "Newly listed property in Ethiopia."
  };

  properties.unshift(newProp);
  renderProperties(properties);
  closeModal();
  alert("Success! Your property has been added to the catalog.");
}

// Leaflet Map Initialization
let frontendMarkersLayer = null;

function initMap() {
  const mapElement = document.getElementById('homeCatalogMap');
  if (!mapElement) return;
  if (typeof L === 'undefined') {
    setTimeout(initMap, 150);
    return;
  }

  if (mapElement._leaflet_id && window.homeMap) {
    try { window.homeMap.remove(); } catch(e) {}
    window.homeMap = null;
  }

  // Base Street Map Layer
  const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  });

  // High-Resolution Esri Satellite Imagery
  const satelliteImagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri &mdash; Earthstar Geographics'
  });

  // Road and Neighborhood Labels Overlay
  const satelliteLabels = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: ''
  });

  const satelliteLayer = L.layerGroup([satelliteImagery, satelliteLabels]);

  // Addis Ababa coordinates with Satellite Layer enabled by default
  const map = L.map('homeCatalogMap', {
    center: [9.0108, 38.7613],
    zoom: 12,
    minZoom: 5,
    maxZoom: 19,
    layers: [satelliteLayer]
  });

  // Layer Switcher Control
  const baseMaps = {
    "🛰️ Satellite (Hybrid)": satelliteLayer,
    "🗺️ Street Map": streetLayer
  };
  L.control.layers(baseMaps, null, { position: 'topright' }).addTo(map);

  frontendMarkersLayer = L.layerGroup().addTo(map);

  // Markers for sample properties
  const locations = [
    { name: "Old Airport Luxury Villa", coords: [8.9880, 38.7300], price: "ETB 65,000,000", id: 1, type: "Villa", city: "Old Airport, Addis Ababa" },
    { name: "Bole Atlas Penthouse", coords: [9.0020, 38.7840], price: "ETB 180,000/mo", id: 2, type: "Apartment", city: "Bole Atlas, Addis Ababa" },
    { name: "Kazanchis Commercial Space", coords: [9.0190, 38.7690], price: "ETB 450,000/mo", id: 3, type: "Commercial", city: "Kazanchis, Addis Ababa" },
    { name: "CMC Michael Family Home", coords: [9.0260, 38.8250], price: "ETB 34,000,000", id: 4, type: "House", city: "CMC, Addis Ababa" },
    { name: "Sarbet 2-Bed Condo", coords: [8.9950, 38.7450], price: "ETB 75,000/mo", id: 5, type: "Apartment", city: "Sarbet, Addis Ababa" }
  ];

  locations.forEach(loc => {
    const marker = L.marker(loc.coords);
    marker.bindPopup(`
      <div style="font-family: 'Plus Jakarta Sans', sans-serif; min-width: 180px;">
        <div style="font-weight: 800; color: #2563eb; font-size: 15px;">${loc.price}</div>
        <div style="font-weight: 700; color: #0f172a; font-size: 13px; margin: 3px 0;">${loc.name}</div>
        <div style="font-size: 12px; color: #64748b; margin-bottom: 6px;">📍 ${loc.city}</div>
        <button onclick="openDetailsModal(${loc.id})" style="background: #2563eb; color: #ffffff; border: none; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 11px; font-weight: 600; width: 100%;">View Details</button>
      </div>
    `);
    frontendMarkersLayer.addLayer(marker);
    loc._marker = marker;
  });

  // Save map instance
  window.homeMap = map;
  window.frontendMapLocations = locations;
  window.frontendMarkersLayer = frontendMarkersLayer;

  setTimeout(function() {
    if (window.homeMap) window.homeMap.invalidateSize();
  }, 300);
}

// Ethiopian Locations Dictionary for Map Search
const ETHIOPIAN_MAP_LOCATIONS = {
  'bole': { lat: 8.9954, lng: 38.7884, zoom: 15, name: 'Bole Subcity, Addis Ababa' },
  'bole atlas': { lat: 9.0020, lng: 38.7840, zoom: 16, name: 'Bole Atlas, Addis Ababa' },
  'kazanchis': { lat: 9.0190, lng: 38.7690, zoom: 16, name: 'Kazanchis, Kirkos, Addis Ababa' },
  'old airport': { lat: 8.9880, lng: 38.7300, zoom: 15, name: 'Old Airport, Nefas Silk, Addis Ababa' },
  'cmc': { lat: 9.0260, lng: 38.8250, zoom: 15, name: 'CMC Michael, Yeka, Addis Ababa' },
  'sarbet': { lat: 8.9950, lng: 38.7450, zoom: 15, name: 'Sarbet (African Union), Addis Ababa' },
  'piazza': { lat: 9.0345, lng: 38.7523, zoom: 16, name: 'Piazza / Arada, Addis Ababa' },
  'megenagna': { lat: 9.0188, lng: 38.8023, zoom: 16, name: 'Megenagna, Yeka, Addis Ababa' },
  'hawassa': { lat: 7.0504, lng: 38.4764, zoom: 14, name: 'Hawassa, Sidama' }
};

let frontendSearchMarker = null;

function searchMapLocation(query) {
  if (!query || !window.homeMap) return;
  const clean = query.toLowerCase().trim();

  let match = null;
  if (ETHIOPIAN_MAP_LOCATIONS[clean]) {
    match = ETHIOPIAN_MAP_LOCATIONS[clean];
  } else {
    for (const [key, loc] of Object.entries(ETHIOPIAN_MAP_LOCATIONS)) {
      if (clean.includes(key) || key.includes(clean)) {
        match = loc;
        break;
      }
    }
  }

  const applyStaticResult = (lat, lng, name, zoom) => {
    window.homeMap.flyTo([lat, lng], zoom || 15, { duration: 1.2 });
    if (frontendSearchMarker) {
      window.homeMap.removeLayer(frontendSearchMarker);
    }
    const pulseIcon = L.divIcon({
      className: 'frontend-search-pulse-icon',
      html: `<div style="background: #2563eb; color: #ffffff; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 0 0 4px rgba(37,99,235,0.35), 0 4px 10px rgba(0,0,0,0.3); border: 2px solid #ffffff;">📍</div>`,
      iconSize: [34, 34],
      iconAnchor: [17, 17]
    });
    frontendSearchMarker = L.marker([lat, lng], { icon: pulseIcon }).addTo(window.homeMap);
    frontendSearchMarker.bindPopup(`
      <div style="font-family: inherit; font-size: 13px;">
        <strong style="color: #2563eb; font-size: 14px;">📍 ${name}</strong><br>
        <span style="font-size: 12px; color: #475569;">Centered on searched location.</span>
      </div>
    `).openPopup();

    const statusEl = document.getElementById('frontMapSearchStatus');
    if (statusEl) {
      statusEl.innerHTML = `📍 Located: <strong>${name}</strong>`;
    }
  };

  if (match) {
    applyStaticResult(match.lat, match.lng, match.name, match.zoom);
    return;
  }

  // Fallback to OpenStreetMap Nominatim
  fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Ethiopia')}&countrycodes=et&limit=1`)
    .then(res => res.json())
    .then(data => {
      if (data && data.length > 0) {
        applyStaticResult(parseFloat(data[0].lat), parseFloat(data[0].lon), data[0].display_name, 15);
      } else {
        const statusEl = document.getElementById('frontMapSearchStatus');
        if (statusEl) {
          statusEl.textContent = `Location "${query}" not found. Try Bole, Kazanchis, CMC, or Hawassa.`;
          setTimeout(() => { statusEl.textContent = ''; }, 4500);
        } else {
          alert(`Location "${query}" not found. Try searching Bole, Kazanchis, CMC, or Hawassa.`);
        }
      }
    })
    .catch(() => {
      if (clean.includes('bole')) {
        const b = ETHIOPIAN_MAP_LOCATIONS['bole'];
        applyStaticResult(b.lat, b.lng, b.name, b.zoom);
      }
    });
}

// Helper utilities
function formatNumber(num) {
  return Number(num).toLocaleString();
}

function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
  if (!text) return '';
  return String(text)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
