<?php

if (!defined('ABSPATH')) {
    exit;
}

class Praatdeurtje_Sync_Center
{
    const OPTION_LAST_APPLIED = 'prd_sync_last_applied_at_utc';
    const REST_NAMESPACE      = 'prd/v1';
    const ADMIN_SLUG          = 'praatdeurtje-sync';
    const SITE_LABEL          = 'Praatdeurtje';

    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
        if (is_admin()) {
            add_action('admin_menu', array($this, 'register_admin_page'));
        }
    }

    public function register_routes()
    {
        register_rest_route(
            self::REST_NAMESPACE,
            '/sync/apply',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'handle_apply'),
                'permission_callback' => static function () {
                    return current_user_can('manage_options');
                },
            )
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/options-dump',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'handle_options_dump'),
                'permission_callback' => static function () {
                    return current_user_can('manage_options');
                },
            )
        );
    }

    public function handle_options_dump(WP_REST_Request $request)
    {
        $stylesheet = (string) get_option('stylesheet', 'flatsome');
        $template   = (string) get_option('template', 'flatsome');

        $dump = array(
            'blogname'                   => get_option('blogname'),
            'blogdescription'            => get_option('blogdescription'),
            'siteurl'                    => get_option('siteurl'),
            'home'                       => get_option('home'),
            'theme_mods_' . $stylesheet  => get_option('theme_mods_' . $stylesheet),
        );
        if ($template !== $stylesheet) {
            $dump['theme_mods_' . $template] = get_option('theme_mods_' . $template);
        }

        return rest_ensure_response(array(
            'ok'        => true,
            'dumped_at' => current_time('c'),
            'options'   => $dump,
        ));
    }

    public function handle_apply(WP_REST_Request $request)
    {
        $body = (string) $request->get_body();
        if ('' === trim($body)) {
            return new WP_Error('empty_body', 'Bundle body is leeg.', array('status' => 400));
        }

        $bundle = json_decode($body, true);
        if (!is_array($bundle)) {
            return new WP_Error('invalid_json', 'Ongeldige JSON in bundle.', array('status' => 400));
        }

        $apply_param = strtolower(trim((string) $request->get_param('apply')));
        $apply       = '1' === $apply_param || 'true' === $apply_param;

        if ($apply) {
            @set_time_limit(300);
            @ini_set('memory_limit', '512M');
        }

        $bundle_generated    = (string) ($bundle['generated_at_utc'] ?? '');
        $target_last_applied = (string) get_option(self::OPTION_LAST_APPLIED, '');

        $newest_wins = array(
            'bundle_generated_at_utc'    => $bundle_generated,
            'target_last_applied_at_utc' => $target_last_applied,
            'verdict'                    => '' === $target_last_applied ? 'eerste_sync' : '',
        );
        if ('' !== $bundle_generated && '' !== $target_last_applied) {
            $newest_wins['verdict'] = $bundle_generated >= $target_last_applied
                ? 'bundle_is_newer_or_equal'
                : 'WARNING: target_has_newer_data';
        }

        $plan = array(
            'options'       => count((array) ($bundle['options'] ?? array())),
            'site_settings' => !empty($bundle['site_settings']) ? 1 : 0,
        );

        if (!$apply) {
            return rest_ensure_response(array(
                'ok'          => true,
                'dry_run'     => true,
                'newest_wins' => $newest_wins,
                'plan'        => $plan,
            ));
        }

        try {
            $changes = array('options' => array(), 'site_settings' => array());

            foreach ((array) ($bundle['options'] ?? array()) as $option_name => $option_value) {
                if ($this->option_is_sensitive((string) $option_name)) {
                    continue;
                }
                update_option((string) $option_name, $option_value, false);
                $changes['options'][] = (string) $option_name;
            }

            if (!empty($bundle['site_settings']) && is_array($bundle['site_settings'])) {
                $changes['site_settings'] = $this->apply_site_settings((array) $bundle['site_settings']);
            }
        } catch (\Throwable $e) {
            return new WP_Error(
                'sync_exception',
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                array('status' => 500)
            );
        }

        if ('' !== $bundle_generated) {
            update_option(self::OPTION_LAST_APPLIED, $bundle_generated, false);
        }

        return rest_ensure_response(array(
            'ok'          => true,
            'dry_run'     => false,
            'newest_wins' => $newest_wins,
            'plan'        => $plan,
            'changes'     => $changes,
        ));
    }

    private function option_is_sensitive($option_name)
    {
        return (bool) preg_match(
            '/(?:api_key|api_secret|client_id|webhook_secret|secret|token|password|salt|license)(?:_|$)/i',
            $option_name
        );
    }

    private function apply_site_settings(array $settings)
    {
        $source_url = (string) ($settings['source_url'] ?? '');
        $target_url = untrailingslashit(home_url());

        $replace_urls = static function ($data) use ($source_url, $target_url) {
            if ('' === $source_url || $source_url === $target_url) {
                return $data;
            }
            $json = wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $json = str_replace($source_url, $target_url, $json);
            return json_decode($json, true);
        };

        $results    = array();
        $stylesheet = (string) get_option('stylesheet', 'flatsome');
        $template   = (string) get_option('template', 'flatsome');

        if (!empty($settings['theme_mods_flatsome']) && is_array($settings['theme_mods_flatsome'])) {
            $incoming = (array) $settings['theme_mods_flatsome'];

            $existing_parent = (array) get_option('theme_mods_' . $template, array());
            update_option('theme_mods_' . $template, array_merge($existing_parent, $incoming));
            wp_cache_delete('theme_mods_' . $template, 'options');

            if ($stylesheet !== $template) {
                $existing_child = (array) get_option('theme_mods_' . $stylesheet, array());
                update_option('theme_mods_' . $stylesheet, array_merge($existing_child, $incoming));
                wp_cache_delete('theme_mods_' . $stylesheet, 'options');
            }

            $results['theme_mods'] = count($incoming);
        }

        if (!empty($settings['widgets']) && is_array($settings['widgets'])) {
            foreach ($settings['widgets'] as $option_name => $value) {
                update_option($option_name, $replace_urls($value));
            }
            $results['widgets'] = array_keys($settings['widgets']);
        }

        if (!empty($settings['sidebars_widgets']) && is_array($settings['sidebars_widgets'])) {
            update_option('sidebars_widgets', (array) $settings['sidebars_widgets']);
            $results['sidebars'] = true;
        }

        return array_merge($results, $this->clear_caches());
    }

    private function clear_caches()
    {
        $results = array();

        wp_cache_flush();

        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_flatsome_%' OR option_name LIKE '_transient_timeout_flatsome_%'");
        $results['flatsome_transients_cleared'] = (int) $wpdb->rows_affected;

        if (function_exists('flatsome_update_style')) {
            flatsome_update_style();
        }

        $cache_dirs = array(
            WP_CONTENT_DIR . '/cache/acceleratewp/',
            WP_CONTENT_DIR . '/cache/sgoptimizer/',
            WP_CONTENT_DIR . '/cache/supercacher/',
            WP_CONTENT_DIR . '/cache/wp-cache/',
            WP_CONTENT_DIR . '/cache/min/',
        );
        $dirs_deleted = array();
        foreach ($cache_dirs as $dir) {
            if (is_dir($dir)) {
                $this->rmdir_recursive($dir);
                $dirs_deleted[] = basename($dir);
            }
        }
        if (!empty($dirs_deleted)) {
            $results['cache_dirs_cleared'] = $dirs_deleted;
        }

        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        $sg_classes = array(
            'SiteGround_Optimizer\Supercacher\Supercacher',
            'SiteGround_Optimizer\Modules\Supercacher\Supercacher',
            'Supercacher',
        );
        foreach ($sg_classes as $cls) {
            if (class_exists($cls) && method_exists($cls, 'purge_cache')) {
                call_user_func(array($cls, 'purge_cache'));
                break;
            }
        }
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }
        do_action('sg_cachepress_purge_cache');
        do_action('acceleratewp_purge_all');
        do_action('sgo_cache_flush');

        $results['cache_flushed'] = true;

        return $results;
    }

    private function rmdir_recursive($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rmdir_recursive($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function register_admin_page()
    {
        add_options_page(
            self::SITE_LABEL . ' Sync',
            self::SITE_LABEL . ' Sync',
            'manage_options',
            self::ADMIN_SLUG,
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $last_applied = (string) get_option(self::OPTION_LAST_APPLIED, '');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(self::SITE_LABEL . ' Sync Center'); ?></h1>
            <p>Sync lokale data naar live via REST endpoint.</p>
            <table class="widefat striped" style="max-width:600px;">
                <tbody>
                    <tr>
                        <td><strong>REST endpoint</strong></td>
                        <td><code><?php echo esc_html(home_url('/wp-json/' . self::REST_NAMESPACE . '/sync/apply')); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Laatste sync</strong></td>
                        <td><?php echo esc_html('' !== $last_applied ? $last_applied : 'Nog geen sync uitgevoerd'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}
