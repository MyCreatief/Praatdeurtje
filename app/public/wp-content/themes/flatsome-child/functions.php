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
