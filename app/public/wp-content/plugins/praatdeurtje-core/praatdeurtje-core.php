<?php
/**
 * Plugin Name: Praatdeurtje Core
 * Description: Sync-endpoint en beheertools voor Praatdeurtje.nl.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: MyCreatief
 * Text Domain: praatdeurtje-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PRD_CORE_VERSION', '1.0.0');

add_action('init', function () {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        defined('DONOTCACHEPAGE')    || define('DONOTCACHEPAGE', true);
        defined('DONOTCACHEOBJECT') || define('DONOTCACHEOBJECT', true);
        defined('DONOTMINIFY')      || define('DONOTMINIFY', true);
    }
});

require_once plugin_dir_path(__FILE__) . 'includes/class-sync-center.php';

add_action('plugins_loaded', function () {
    (new Praatdeurtje_Sync_Center())->init();
});

// Trustblok boven "Toevoegen aan winkelwagen"
add_action('woocommerce_before_add_to_cart_button', function () {
    echo '<div class="prd-trust" style="margin-bottom:12px;font-size:0.85em;color:#555;line-height:2;">'
        . '<span>&#10003; Handgemaakt houten deurtje</span>'
        . ' &nbsp;·&nbsp; '
        . '<span>&#10003; Verzending via PostNL</span>'
        . ' &nbsp;·&nbsp; '
        . '<span>&#10003; <a href="/contact/">Vragen? Stuur een bericht</a></span>'
        . '</div>';
});
