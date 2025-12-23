<?php
/**
 * Template Name: Über Uns Page
 *
 * @package Seminargo
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <!-- Hero Section -->
        <section class="about-hero">
            <div class="container">
                <div class="about-hero-content">
                    <span class="hero-tagline">Über seminargo</span>
                    <h1 class="page-title">Ihr Nr. 1 Partner für Seminarhotel-Buchungen</h1>
                    <p class="hero-subtitle">Seit 2001 (ehemals symposionline) vermitteln wir erfolgreich Seminarhotels und verarbeiten rund 18.400 Veranstaltungen jährlich.</p>
                </div>
            </div>
        </section>

        <!-- Leadership Section -->
        <section class="about-leadership">
            <div class="container">
                <div class="leadership-content">
                    <div class="leadership-image">
                        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/about-team-4.png' ); ?>" alt="Andrea & Andreas Kernreiter" loading="lazy">
                    </div>
                    <div class="leadership-text">
                        <div class="section-header-left">
                            <span class="section-tagline">Unsere Geschäftsführung</span>
                            <h2 class="section-title">Andrea & Andreas Kernreiter</h2>
                        </div>
                        <p class="leadership-description">Als Geschäftsführer der seminargo GmbH und seminargo Deutschland GmbH setzen wir uns mit Leidenschaft dafür ein, Ihnen den besten Service für Ihre Veranstaltungsplanung zu bieten. Seit über 20 Jahren sind wir Ihr verlässlicher Partner in der Seminarhotel-Branche.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="about-stats">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Zahlen & Fakten</span>
                    <h2 class="section-title">seminargo in Zahlen</h2>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="stat-number">8.000+</div>
                        <div class="stat-label">Buchungen pro Jahr</div>
                        <p class="stat-description">Jährlich vermitteln wir tausende erfolgreiche Veranstaltungen</p>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div class="stat-number">25.000+</div>
                        <div class="stat-label">Seminarhotels & Veranstaltungsorte</div>
                        <p class="stat-description">Europaweit haben Sie Zugriff auf eine riesige Auswahl</p>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="stat-number">&lt; 2 Stunden</div>
                        <div class="stat-label">Hotelsuche-Service</div>
                        <p class="stat-description">Kostenlos und blitzschnell zum perfekten Hotel</p>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20M2 12h20"></path>
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                        </div>
                        <div class="stat-number">Seit 2001</div>
                        <div class="stat-label">Marktführer</div>
                        <p class="stat-description">Der seminargo-Katalog ist der führende Seminarhotel-Führer am Markt</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="about-services">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Unsere Leistungen</span>
                    <h2 class="section-title">Was wir für Sie tun</h2>
                </div>

                <div class="services-grid">
                    <!-- Service 1 -->
                    <div class="service-card">
                        <div class="service-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </div>
                        <h3 class="service-title">seminargo.com Plattform</h3>
                        <p class="service-description">Online-Hotelbuchung mit standardisierten Angeboten und transparenter Gesamtpreisdarstellung. Schnell, einfach und übersichtlich.</p>
                    </div>

                    <!-- Service 2 -->
                    <div class="service-card">
                        <div class="service-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3 class="service-title">Persönliche Meeting-Planer</h3>
                        <p class="service-description">Unser Expertenteam steht Ihnen werktags von 8:00 bis 18:00 Uhr zur Verfügung und unterstützt Sie bei Ihrer perfekten Veranstaltungsplanung.</p>
                    </div>

                    <!-- Service 3 -->
                    <div class="service-card">
                        <div class="service-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <h3 class="service-title">Print-Katalog</h3>
                        <p class="service-description">Der führende Seminarhotel-Führer am Markt. Alle wichtigen Informationen kompakt und übersichtlich in gedruckter Form.</p>
                    </div>

                    <!-- Service 4 -->
                    <div class="service-card service-card-premium">
                        <div class="service-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                            </svg>
                        </div>
                        <h3 class="service-title">seminargo Premium</h3>
                        <p class="service-description">E-Procurement-Lösung mit ausverhandelten Vertragsraten, einheitlichen Stornobedingungen, individueller Rechnungsstellung und Kostenreporting für Unternehmen.</p>
                        <ul class="service-features">
                            <li>Ausverhandelte Vertragsraten</li>
                            <li>Einheitliche Stornobedingungen</li>
                            <li>Individuelle Rechnungsstellung</li>
                            <li>Detailliertes Kostenreporting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Offices Section -->
        <section class="about-offices">
            <div class="container">
                <div class="section-header">
                    <span class="section-tagline">Unsere Standorte</span>
                    <h2 class="section-title">Wir sind für Sie da</h2>
                </div>

                <div class="about-offices-grid">
                    <!-- Map -->
                    <div class="about-map-wrapper">
                        <div id="about-map" class="about-map"></div>
                    </div>

                    <!-- Office Locations -->
                    <div class="about-office-locations">
                        <!-- Vienna Office -->
                        <div class="office-card">
                            <div class="office-flag">
                                <span class="flag-at">AT</span>
                            </div>
                            <div class="office-details">
                                <h3>seminargo GmbH</h3>
                                <address>
                                    Liebhartsgasse 16<br>
                                    1160 Wien, Österreich
                                </address>
                                <p class="office-contact">
                                    <a href="tel:+43190858">+43/1/90 858</a>
                                </p>
                            </div>
                        </div>

                        <!-- Munich Office -->
                        <div class="office-card">
                            <div class="office-flag">
                                <span class="flag-de">DE</span>
                            </div>
                            <div class="office-details">
                                <h3>seminargo Deutschland GmbH</h3>
                                <address>
                                    Hermann-Weinhauser-Straße 73<br>
                                    81673 München, Deutschland
                                </address>
                                <p class="office-contact">
                                    <a href="tel:+4989700741669">+49 89 700 741 69</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="about-cta-section">
            <div class="container">
                <div class="about-cta-content">
                    <div class="about-cta-text">
                        <h2>Bereit für Ihre nächste Veranstaltung?</h2>
                        <p>Lassen Sie uns gemeinsam das perfekte Seminarhotel für Ihre Anforderungen finden. Unser Team steht Ihnen jederzeit zur Verfügung.</p>
                    </div>
                    <div class="about-cta-buttons">
                        <a href="tel:+43190858" class="button button-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            Jetzt anrufen
                        </a>
                        <a href="/kontakt" class="button button-outline-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            Kontakt aufnehmen
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const map = L.map('about-map', {
        scrollWheelZoom: false,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true
    }).setView([48.5, 11.5], 6); // Center between Vienna and Munich

    // Use CartoDB Positron - clean, modern style matching hotel pages
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // Custom marker icon
    var markerIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div class="marker-pin"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#AC2A6E" stroke="#fff" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3" fill="#fff"></circle></svg></div>',
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -40]
    });

    // Vienna marker
    var viennaMarker = L.marker([48.2082, 16.3738], {icon: markerIcon})
        .addTo(map)
        .bindPopup('<strong>seminargo GmbH</strong><br>Liebhartsgasse 16<br>1160 Wien');

    // Munich marker
    var municMarker = L.marker([48.1351, 11.5820], {icon: markerIcon})
        .addTo(map)
        .bindPopup('<strong>seminargo Deutschland GmbH</strong><br>Hermann-Weinhauser-Straße 73<br>81673 München');

    // Fit bounds to show both markers
    var bounds = L.latLngBounds([
        [48.2082, 16.3738], // Vienna
        [48.1351, 11.5820]  // Munich
    ]);
    map.fitBounds(bounds.pad(0.1));
});
</script>

<?php get_footer(); ?>
