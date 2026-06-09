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

// Meer van Mylene — zustersite-balk onderaan elke pagina (met UTM-tracking)
add_action('wp_footer', function () {
    $all = array(
        'https://www.mycreatief.nl'   => array('MyCreatief.nl',   'Handgemaakte houten producten'),
        'https://www.praatdeurtje.nl' => array('Praatdeurtje.nl', 'Kleine houten deurtjes voor grote gevoelens'),
        'https://www.complimentje.nu' => array('Complimentje.nu', 'Kaarten met een persoonlijk compliment'),
    );
    $current = untrailingslashit((string) home_url());
    $others  = array_filter($all, static function ($url) use ($current) {
        return untrailingslashit($url) !== $current;
    }, ARRAY_FILTER_USE_KEY);
    if (empty($others)) {
        return;
    }
    // UTM-source = huidige site-host zonder www
    $sourceHost = preg_replace('/^www\./', '', (string) parse_url($current, PHP_URL_HOST));
    $sourceHost = $sourceHost ?: 'unknown';
    echo '<div class="mylene-projects-bar"><span class="mylene-projects-label">Meer van Mylene</span>';
    foreach ($others as $url => $info) {
        $tracked = add_query_arg(array(
            'utm_source'   => $sourceHost,
            'utm_medium'   => 'footer-bar',
            'utm_campaign' => 'meer-van-mylene',
        ), $url);
        echo '<a href="' . esc_url($tracked) . '" class="mylene-project-link" rel="noopener">'
            . '<strong>' . esc_html($info[0]) . '</strong>'
            . '<span>' . esc_html($info[1]) . '</span>'
            . '</a>';
    }
    echo '</div>';
}, 2);

// Organization JSON-LD — koppelt de drie Mylene-sites voor Google
add_action('wp_head', function () {
    $map = array(
        'https://www.mycreatief.nl'   => 'MyCreatief',
        'https://www.praatdeurtje.nl' => 'Praatdeurtje',
        'https://www.complimentje.nu' => 'Complimentje',
    );
    $current = untrailingslashit((string) home_url());
    $name    = isset($map[$current]) ? $map[$current] : get_bloginfo('name');
    $related = array_values(array_filter(
        array_keys($map),
        static function ($u) use ($current) { return untrailingslashit($u) !== $current; }
    ));
    $schema = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $name,
        'url'      => trailingslashit($current),
        'founder'  => array('@type' => 'Person', 'name' => 'Mylene Klijn', 'url' => 'https://www.mycreatief.nl'),
        'sameAs'   => $related,
    );
    echo '<script type="application/ld+json">'
        . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}, 5);

// Preload self-hosted fonts (Lato + Dancing Script)
add_action('wp_head', function () {
    $base = content_url('fonts');
    $fonts = array(
        'lato/S6uyw4BMUTPHjx4wXg.woff2',
        'lato/S6uyw4BMUTPHjxAwWCWtFCfQ7A.woff2',
        'dancing-script/If2cXTr6YS-zF4S-kcSWSVi_sxjsohD9F50Ruu7BMSo3Sup8.woff2',
    );
    foreach ($fonts as $font) {
        echo '<link rel="preload" href="' . esc_url($base . '/' . $font) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
}, 1);

// Verhalenpagina: toon "Wie is Mosje?" en een verhalenzoeker boven de speler.
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <style id="prd-story-tools-css">
    .prd-story-tools{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.72fr);gap:24px;max-width:1120px;margin:42px auto;padding:0 16px}
    .prd-mosje-card,.prd-story-search{border:1px solid rgba(114,127,91,.22);border-radius:18px;background:#fff;box-shadow:0 22px 44px -34px rgba(44,40,32,.38)}
    .prd-mosje-card{display:grid;gap:8px;padding:28px;text-decoration:none;background:radial-gradient(circle at 92% 12%,rgba(201,221,198,.55),transparent 130px),#fff}
    .prd-mosje-card span{color:#71805c;font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
    .prd-mosje-card strong{color:#29251f;font-family:Georgia,serif;font-size:clamp(28px,4vw,42px);line-height:1}
    .prd-mosje-card p{max-width:620px;color:#6f6a61;margin:0}
    .prd-mosje-card em{color:#71805c;font-style:normal;font-weight:700}
    .prd-story-search{display:grid;align-content:center;gap:12px;padding:24px}
    .prd-story-search label{font-family:Georgia,serif;font-size:22px;color:#29251f}
    .prd-story-search div{display:flex;gap:8px}
    .prd-story-search input[type=search]{min-width:0;width:100%;border:1px solid rgba(114,127,91,.24);border-radius:999px;padding:11px 14px;background:#fff}
    .prd-story-search button{border:0;border-radius:999px;padding:11px 16px;background:#71805c;color:#fff;font-weight:700;cursor:pointer}
    @media(max-width:760px){.prd-story-tools{grid-template-columns:1fr}.prd-story-search div{display:grid}}
    </style>
    <?php
}, 20);

add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <script>
    (function(){
      if (document.querySelector('.prd-story-tools')) return;
      var player = document.querySelector('.dhs-player-band, .dhs-player');
      if (!player) return;
      var section = player.classList.contains('dhs-player-band') ? player : player.closest('section') || player;
      var html = '<section class="prd-story-tools"><a class="prd-mosje-card" href="/wie-is-mosje/"><span>Begin hier</span><strong>Wie is Mosje?</strong><p>Maak kennis met Mosje, het kleine gezicht van de slaapverhaaltjes achter het Praatdeurtje.</p><em>Lees over Mosje &rarr;</em></a><form class="prd-story-search" role="search" method="get" action="/"><label for="prd-story-search-field-js">Zoek in verhalen</label><div><input id="prd-story-search-field-js" type="search" name="s" placeholder="Bijvoorbeeld: slapen, moed, vriendje..."><input type="hidden" name="category_name" value="verhalen"><button type="submit">Zoeken</button></div></form></section>';
      section.insertAdjacentHTML('beforebegin', html);
    }());
    </script>
    <?php
}, 50);

// Volg-Mosje-balk — alleen op praatdeurtje.nl. Sober knopje boven de zustersite-balk.
add_action('wp_footer', function () {
    if (untrailingslashit((string) home_url()) !== 'https://www.praatdeurtje.nl') { return; }
    if (is_admin()) { return; }
    ?>
    <style id="prd-follow-css">
    .prd-follow-bar{display:flex;justify-content:center;gap:10px;padding:18px 16px 4px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
    .prd-follow-bar a{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;background:#1877f2;color:#fff;border-radius:999px;text-decoration:none;font-weight:600;font-size:14px;box-shadow:0 2px 8px -2px rgba(24,119,242,.45);transition:transform .15s ease}
    .prd-follow-bar a:hover{transform:translateY(-1px);color:#fff}
    .prd-follow-bar svg{width:18px;height:18px;fill:currentColor}
    </style>
    <div class="prd-follow-bar">
        <a href="https://www.facebook.com/1068983399642608" target="_blank" rel="noopener" aria-label="Volg Praatdeurtje op Facebook">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg>
            Volg op Facebook
        </a>
    </div>
    <?php
}, 1);
