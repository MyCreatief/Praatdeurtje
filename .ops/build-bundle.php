<?php
/**
 * WP-CLI eval-file: exporteert theme mods + sidebars/widgets naar een sync bundle.
 * Gebruik: wp --path=<wp-root> eval-file build-bundle.php bundle_path=<pad> stdout=1 write_file=1
 */

$kv = array();
foreach ((array) $args as $a) {
    if (strpos($a, '=') !== false) {
        list($k, $v) = explode('=', $a, 2);
        $kv[trim($k)] = trim($v);
    }
}

$bundle_path  = isset($kv['bundle_path']) ? $kv['bundle_path'] : '';
$write_file   = !isset($kv['write_file']) || filter_var($kv['write_file'], FILTER_VALIDATE_BOOLEAN);
$print_stdout = !isset($kv['stdout'])     || filter_var($kv['stdout'],     FILTER_VALIDATE_BOOLEAN);

$stylesheet = (string) get_option('stylesheet', 'flatsome');
$template   = (string) get_option('template',   'flatsome');

$mods_parent = (array) get_option('theme_mods_' . $template,   array());
$mods_active = (array) get_option('theme_mods_' . $stylesheet, array());
$mods_merged = array_merge($mods_parent, $mods_active);

$widget_options = array();
foreach (array('widget_custom_html', 'widget_text', 'widget_block') as $key) {
    $val = get_option($key, null);
    if (null !== $val && false !== $val) {
        $widget_options[$key] = $val;
    }
}

$source_url = untrailingslashit(home_url());

$bundle = array(
    'version'          => 1,
    'generated_at_utc' => gmdate('c'),
    'source_url'       => $source_url,
    'site_settings'    => array(
        'theme_mods_flatsome'   => $mods_merged,
        'theme_mods_stylesheet' => $stylesheet,
        'sidebars_widgets'      => (array) get_option('sidebars_widgets', array()),
        'widgets'               => $widget_options,
        'source_url'            => $source_url,
    ),
    'options'          => array(),
);

if ($write_file && '' !== $bundle_path) {
    wp_mkdir_p(dirname($bundle_path));
    file_put_contents(
        $bundle_path,
        wp_json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
}

$summary = array(
    'ok'          => true,
    'bundle_path' => $bundle_path,
    'wrote_file'  => $write_file && '' !== $bundle_path,
    'counts'      => array(
        'theme_mods'    => count($mods_merged),
        'sidebars'      => count((array) ($bundle['site_settings']['sidebars_widgets'])),
        'widget_options' => count($widget_options),
    ),
    'source_url'  => $source_url,
);

if ($print_stdout) {
    echo wp_json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
