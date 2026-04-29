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

// Meer van Mylene — zustersite-balk onderaan elke pagina
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
    echo '<div class="mylene-projects-bar"><span class="mylene-projects-label">Meer van Mylene</span>';
    foreach ($others as $url => $info) {
        echo '<a href="' . esc_url($url) . '" class="mylene-project-link" rel="noopener">'
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
