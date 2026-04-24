<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'flatsome-child-style',
        get_stylesheet_uri(),
        array('flatsome-style'),
        wp_get_theme()->get('Version')
    );
});

// Preconnect voor externe origins — versnelt GTM, GA4 en Google Fonts
add_action('wp_head', function () {
    echo '<link rel="preconnect" href="https://www.googletagmanager.com">' . "\n";
    echo '<link rel="preconnect" href="https://www.google-analytics.com">' . "\n";
    echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
    echo '<link rel="dns-prefetch" href="https://www.google-analytics.com">' . "\n";
}, 0);

// GTM Consent Mode — defaults op 'denied' VÓÓR het GTM-script laden (AVG-vereiste)
add_action('wp_head', function () {
    ?>
<script>
window.dataLayer=window.dataLayer||[];
function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{analytics_storage:'denied',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',wait_for_update:500});
(function(){var m=document.cookie.match(/\bmcr_consent=([^;]+)/);if(m&&m[1]==='yes'){gtag('consent','update',{analytics_storage:'granted',ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted'});}})();
</script>
<?php
}, 0);

// Google Tag Manager — head script (priority 1 = zo vroeg mogelijk in <head>)
add_action('wp_head', function () {
    ?><!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-TWNMX8GG');</script>
<!-- End Google Tag Manager -->
<?php
}, 1);

// Google Tag Manager — noscript direct na <body>
add_action('wp_body_open', function () {
    ?><!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TWNMX8GG"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php
}, 1);

// Cookie-toestemmingsbanner (AVG/GDPR) — verborgen voor terugkerende bezoekers via JS
add_action('wp_footer', function () {
    $privacy_url = get_privacy_policy_url() ?: '/privacy-statement/';
    ?>
<div id="mcr-cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie-instellingen" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:99999;background:#fff;border-top:3px solid #446084;box-shadow:0 -4px 20px rgba(0,0,0,.1);padding:18px 24px;align-items:center;gap:16px;flex-wrap:wrap;">
    <p style="margin:0;flex:1;min-width:200px;font-size:.875em;color:#444;line-height:1.55;">
        Wij gebruiken cookies om het websitegebruik te analyseren en je ervaring te verbeteren.
        Lees ons <a href="<?php echo esc_url($privacy_url); ?>" style="color:#446084;text-decoration:underline;">privacybeleid</a>.
    </p>
    <div style="display:flex;gap:10px;flex-shrink:0;flex-wrap:wrap;">
        <button onclick="mcrConsent('no')" style="padding:10px 20px;border:2px solid #446084;background:transparent;color:#446084;border-radius:4px;cursor:pointer;font-size:.875em;font-weight:600;white-space:nowrap;">Alleen noodzakelijk</button>
        <button onclick="mcrConsent('yes')" style="padding:10px 20px;border:2px solid #C05530;background:#C05530;color:#fff;border-radius:4px;cursor:pointer;font-size:.875em;font-weight:600;white-space:nowrap;">Accepteren</button>
    </div>
</div>
<script>
(function(){
    if(!document.cookie.match(/\bmcr_consent=/)){
        document.getElementById('mcr-cookie-banner').style.display='flex';
    }
})();
function mcrConsent(c){
    var e=new Date();e.setFullYear(e.getFullYear()+1);
    document.cookie='mcr_consent='+c+';expires='+e.toUTCString()+';path=/;SameSite=Lax';
    document.getElementById('mcr-cookie-banner').style.display='none';
    if(c==='yes'){
        gtag('consent','update',{analytics_storage:'granted',ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted'});
    }
}
</script>
<?php
}, 100);

// Kritieke CSS inline — bypast AccelerateWP minificatiecache volledig.
// Bevat regels die direct zichtbaar effect hebben en niet vertraagd mogen worden.
add_action('wp_head', function () {
    ?>
<style id="mcr-critical">
/* Productgrid: geen omschrijving, links uitgelijnd */
.products .box-excerpt { display: none !important; }
.products .box-text-products { text-align: left; }
/* Categorie-labels verbergen in productgrid */
.products .product-cats,
.products .woocommerce-loop-product__categories { display: none; }
/* Homepage stap-nummers (JS voegt .step-num/.step-label toe) */
.home .col-inner h3 .step-num { display:block; font-size:4.2em; font-weight:900; color:rgba(74,46,24,.09); line-height:.85; margin-bottom:16px; letter-spacing:-.04em; font-family:'Domine',Georgia,serif; user-select:none; }
.home .col-inner h3 .step-label { display:block; font-size:.70em; font-weight:800; text-transform:uppercase; letter-spacing:.16em; color:#4b2e18; border-top:2px solid #c9a98a; padding-top:14px; }
</style>
<?php
}, 5);

// Splits "01 — Ontwerp" stap-koppen op de homepage in .step-num + .step-label
// zodat CSS de grote ghostnummers boven het label kan tonen.
add_action('wp_footer', function () {
    if (!is_front_page()) {
        return;
    }
    ?>
<script>
(function () {
    document.querySelectorAll('.col-inner h3').forEach(function (h3) {
        var m = (h3.textContent || '').match(/^(\d{2})\s*[—–\-]\s*(.+)$/);
        if (!m) return;
        h3.innerHTML = '<span class="step-num">' + m[1] + '<\/span><span class="step-label">' + m[2] + '<\/span>';
    });
}());
</script>
<?php
}, 20);

// Preload self-hosted fonts to reduce LCP delay.
add_action('wp_head', function () {
    $base = content_url('fonts');
    $fonts = array(
        'lato/S6uyw4BMUTPHjx4wXg.woff2',
        'albert-sans/i7dZIFdwYjGaAMFtZd_QA3xXSKZqhr-TenSHq5PPq4f3.woff2',
        'nunito/XRXI3I6Li01BKofiOc5wtlZ2di8HDFwmdTQ3jw.woff2',
        'dancing-script/If2cXTr6YS-zF4S-kcSWSVi_sxjsohD9F50Ruu7BMSo3Sup8.woff2',
        'domine/L0xhDFMnlVwD4h3Lt9JWnbX3jG-2X3LAE1ofEw.woff2',
    );
    foreach ($fonts as $font) {
        echo '<link rel="preload" href="' . esc_url($base . '/' . $font) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
}, 1);
