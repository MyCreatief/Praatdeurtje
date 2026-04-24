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
