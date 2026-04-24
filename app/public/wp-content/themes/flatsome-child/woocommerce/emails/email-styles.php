<?php
/**
 * Email styles — MyCreatief brand override.
 * Extend WooCommerce default styles with brand colours.
 */
defined('ABSPATH') || exit;

// Pull WooCommerce default styles first.
$default_styles = WC()->mailer()->get_default_styles();
echo $default_styles; // phpcs:ignore WordPress.Security.EscapeOutput
?>

<style type="text/css">
    /* === Brand overrides === */
    #wrapper      { background-color: #f5f0eb !important; }
    #template_container { border: 1px solid #ddd5c8 !important; border-radius: 6px !important; }
    #template_header  { background-color: #446084 !important; border-radius: 6px 6px 0 0 !important; }
    #template_header h1,
    #template_header h1 a { color: #ffffff !important; }
    #template_body    { background-color: #ffffff !important; }
    #template_footer td { background-color: #f5f0eb !important; color: #7a6f66 !important; font-size: 12px !important; }
    h1, h2, h3 { color: #446084 !important; }
    .button a, .button a:hover { background-color: #C05530 !important; border-color: #C05530 !important; }
    a { color: #446084 !important; }
    .order_details th { background-color: #446084 !important; color: #ffffff !important; }
</style>
