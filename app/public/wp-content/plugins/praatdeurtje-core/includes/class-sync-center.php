<?php

if (!defined('ABSPATH')) {
    exit;
}

class Praatdeurtje_Sync_Center
{
    const OPTION_LAST_APPLIED  = 'prd_sync_last_applied_at_utc';
    const OPTION_LAST_EXPORTED = 'prd_sync_last_exported_at_utc';
    const OPTION_PENDING       = 'prd_sync_pending_bundle';
    const REST_NAMESPACE       = 'prd/v1';
    const ADMIN_SLUG           = 'praatdeurtje-sync';
    const SITE_LABEL           = 'Praatdeurtje';
    const ACTION_EXPORT        = 'prd_sync_export';
    const ACTION_PREVIEW       = 'prd_sync_preview';
    const ACTION_APPLY         = 'prd_sync_apply';
    const ACTION_DISCARD       = 'prd_sync_discard';

    public function init()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_post_' . self::ACTION_EXPORT,  array($this, 'handle_export'));
        add_action('admin_post_' . self::ACTION_PREVIEW, array($this, 'handle_preview'));
        add_action('admin_post_' . self::ACTION_APPLY,   array($this, 'handle_apply_bundle'));
        add_action('admin_post_' . self::ACTION_DISCARD, array($this, 'handle_discard'));
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
                'callback'            => array($this, 'handle_rest_apply'),
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
                'callback'            => array($this, 'handle_rest_options_dump'),
                'permission_callback' => static function () {
                    return current_user_can('manage_options');
                },
            )
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/sql/run',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'handle_rest_sql_run'),
                'permission_callback' => static function () {
                    return current_user_can('manage_options');
                },
            )
        );
    }

    public function handle_rest_sql_run(WP_REST_Request $request)
    {
        global $wpdb;

        $body = json_decode((string) $request->get_body(), true);
        if (!isset($body['statements']) || !is_array($body['statements'])) {
            return new WP_Error('missing_statements', 'Veld "statements" ontbreekt of is geen array.', array('status' => 400));
        }

        $wpdb->query('SET NAMES utf8mb4');
        $wpdb->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

        $results = array();
        foreach ($body['statements'] as $i => $sql) {
            $sql = trim((string) $sql);
            if ('' === $sql || strpos($sql, '--') === 0) {
                continue;
            }
            $result = $wpdb->query($sql);
            if (false === $result) {
                return new WP_Error(
                    'sql_error',
                    sprintf('Fout bij statement %d: %s', $i + 1, $wpdb->last_error),
                    array('status' => 500)
                );
            }
            $results[] = array('statement' => $i + 1, 'rows_affected' => $result);
        }

        $this->clear_caches();

        return rest_ensure_response(array('ok' => true, 'ran' => count($results), 'results' => $results));
    }

    public function handle_rest_options_dump(WP_REST_Request $request)
    {
        $stylesheet = (string) get_option('stylesheet', 'flatsome');
        $template   = (string) get_option('template', 'flatsome');
        $dump = array(
            'blogname'                  => get_option('blogname'),
            'blogdescription'           => get_option('blogdescription'),
            'siteurl'                   => get_option('siteurl'),
            'home'                      => get_option('home'),
            'theme_mods_' . $stylesheet => get_option('theme_mods_' . $stylesheet),
        );
        if ($template !== $stylesheet) {
            $dump['theme_mods_' . $template] = get_option('theme_mods_' . $template);
        }
        return rest_ensure_response(array('ok' => true, 'dumped_at' => current_time('c'), 'options' => $dump));
    }

    public function handle_rest_apply(WP_REST_Request $request)
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
        $newest_wins = $this->build_newest_wins($bundle_generated, $target_last_applied);
        $plan = array(
            'options'       => count((array) ($bundle['options'] ?? array())),
            'site_settings' => !empty($bundle['site_settings']) ? 1 : 0,
        );

        if (!$apply) {
            return rest_ensure_response(array('ok' => true, 'dry_run' => true, 'newest_wins' => $newest_wins, 'plan' => $plan));
        }

        try {
            $changes = $this->apply_bundle($bundle);
        } catch (\Throwable $e) {
            return new WP_Error('sync_exception', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), array('status' => 500));
        }

        if ('' !== $bundle_generated) {
            update_option(self::OPTION_LAST_APPLIED, $bundle_generated, false);
        }
        return rest_ensure_response(array('ok' => true, 'dry_run' => false, 'newest_wins' => $newest_wins, 'plan' => $plan, 'changes' => $changes));
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

        $last_applied  = (string) get_option(self::OPTION_LAST_APPLIED, '');
        $last_exported = (string) get_option(self::OPTION_LAST_EXPORTED, '');
        $pending       = get_option(self::OPTION_PENDING, null);

        if (!empty($_GET['sync_notice'])) {
            $msgs = array(
                'preview_loaded' => 'Bundle geladen — bekijk de voorvertoning hieronder.',
                'applied'        => 'Bundle succesvol toegepast.',
                'discarded'      => 'Voorvertoning verwijderd.',
            );
            $msg = $msgs[sanitize_key($_GET['sync_notice'])] ?? '';
            if ($msg) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
            }
        }
        if (!empty($_GET['sync_error'])) {
            $msgs = array(
                'no_file'      => 'Geen bestand geüpload.',
                'invalid_json' => 'Het bestand bevat geen geldige JSON.',
                'no_pending'   => 'Geen bundle in voorvertoning.',
            );
            $msg = $msgs[sanitize_key($_GET['sync_error'])] ?? 'Er is een fout opgetreden.';
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(self::SITE_LABEL . ' Sync Center'); ?></h1>

            <h2>Status</h2>
            <table class="widefat striped" style="max-width:600px;">
                <tbody>
                    <tr><td><strong>Site URL</strong></td><td><?php echo esc_html(home_url()); ?></td></tr>
                    <tr><td><strong>Laatste export</strong></td><td><?php echo esc_html('' !== $last_exported ? $last_exported : '—'); ?></td></tr>
                    <tr><td><strong>Laatste sync toegepast</strong></td><td><?php echo esc_html('' !== $last_applied ? $last_applied : '—'); ?></td></tr>
                </tbody>
            </table>

            <h2 style="margin-top:28px;">Bundle downloaden</h2>
            <p>Exporteer huidige theme-instellingen en opties als JSON-bestand.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_EXPORT); ?>">
                <?php wp_nonce_field(self::ACTION_EXPORT, '_wpnonce_export'); ?>
                <?php submit_button('Download sync bundle', 'primary', 'submit', false); ?>
            </form>

            <h2 style="margin-top:28px;">Bundle uploaden en voorvertonen</h2>
            <p>Upload een eerder geëxporteerde bundle om te bekijken wat er verandert.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_PREVIEW); ?>">
                <?php wp_nonce_field(self::ACTION_PREVIEW, '_wpnonce_preview'); ?>
                <input type="file" name="bundle_file" accept=".json" required style="margin-right:8px;">
                <?php submit_button('Voorvertoon bundle', 'secondary', 'submit', false); ?>
            </form>

            <?php if (is_array($pending) && !empty($pending)): ?>
                <hr style="margin:28px 0 20px;">
                <h2>Voorvertoning</h2>
                <?php $this->render_preview($pending); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_preview(array $bundle)
    {
        $generated  = esc_html((string) ($bundle['generated_at_utc'] ?? '—'));
        $source_url = esc_html((string) (($bundle['site_settings']['source_url'] ?? '') ?: ($bundle['source_url'] ?? '—')));
        $n_mods     = count((array) ($bundle['site_settings']['theme_mods_flatsome'] ?? array()));
        $n_options  = count((array) ($bundle['options'] ?? array()));
        $has_sw     = !empty($bundle['site_settings']['sidebars_widgets']);
        ?>
        <table class="widefat striped" style="max-width:600px;margin-bottom:16px;">
            <tbody>
                <tr><td><strong>Gegenereerd op</strong></td><td><?php echo $generated; ?></td></tr>
                <tr><td><strong>Bronsite</strong></td><td><?php echo $source_url; ?></td></tr>
                <tr><td><strong>Theme mods</strong></td><td><?php echo esc_html($n_mods); ?> sleutels</td></tr>
                <tr><td><strong>Opties</strong></td><td><?php echo esc_html($n_options); ?></td></tr>
                <tr><td><strong>Sidebars/widgets</strong></td><td><?php echo $has_sw ? 'Ja' : 'Nee'; ?></td></tr>
            </tbody>
        </table>
        <div style="display:flex;gap:12px;align-items:center;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_APPLY); ?>">
                <?php wp_nonce_field(self::ACTION_APPLY, '_wpnonce_apply'); ?>
                <?php submit_button('Pas bundle toe', 'primary', 'submit', false); ?>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_DISCARD); ?>">
                <?php wp_nonce_field(self::ACTION_DISCARD, '_wpnonce_discard'); ?>
                <?php submit_button('Verwijder preview', 'delete', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public function handle_export()
    {
        if (!current_user_can('manage_options')) { wp_die('Geen toegang.'); }
        check_admin_referer(self::ACTION_EXPORT, '_wpnonce_export');
        update_option(self::OPTION_LAST_EXPORTED, gmdate('c'), false);
        $bundle   = $this->collect_bundle();
        $filename = 'praatdeurtje-sync-' . gmdate('Y-m-d-His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo wp_json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function handle_preview()
    {
        if (!current_user_can('manage_options')) { wp_die('Geen toegang.'); }
        check_admin_referer(self::ACTION_PREVIEW, '_wpnonce_preview');
        $redirect = admin_url('options-general.php?page=' . self::ADMIN_SLUG);
        if (empty($_FILES['bundle_file']['tmp_name'])) {
            wp_redirect(add_query_arg('sync_error', 'no_file', $redirect)); exit;
        }
        $bundle = json_decode(file_get_contents($_FILES['bundle_file']['tmp_name']), true); // phpcs:ignore
        if (!is_array($bundle)) {
            wp_redirect(add_query_arg('sync_error', 'invalid_json', $redirect)); exit;
        }
        update_option(self::OPTION_PENDING, $bundle, false);
        wp_redirect(add_query_arg('sync_notice', 'preview_loaded', $redirect));
        exit;
    }

    public function handle_apply_bundle()
    {
        if (!current_user_can('manage_options')) { wp_die('Geen toegang.'); }
        check_admin_referer(self::ACTION_APPLY, '_wpnonce_apply');
        $redirect = admin_url('options-general.php?page=' . self::ADMIN_SLUG);
        $bundle   = get_option(self::OPTION_PENDING, null);
        if (!is_array($bundle)) {
            wp_redirect(add_query_arg('sync_error', 'no_pending', $redirect)); exit;
        }
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
        $this->apply_bundle($bundle);
        update_option(self::OPTION_LAST_APPLIED, (string) ($bundle['generated_at_utc'] ?? gmdate('c')), false);
        delete_option(self::OPTION_PENDING);
        wp_redirect(add_query_arg('sync_notice', 'applied', $redirect));
        exit;
    }

    public function handle_discard()
    {
        if (!current_user_can('manage_options')) { wp_die('Geen toegang.'); }
        check_admin_referer(self::ACTION_DISCARD, '_wpnonce_discard');
        delete_option(self::OPTION_PENDING);
        wp_redirect(add_query_arg('sync_notice', 'discarded', admin_url('options-general.php?page=' . self::ADMIN_SLUG)));
        exit;
    }

    private function collect_bundle()
    {
        $stylesheet  = (string) get_option('stylesheet', 'flatsome');
        $template    = (string) get_option('template', 'flatsome');
        $mods_merged = array_merge(
            (array) get_option('theme_mods_' . $template, array()),
            (array) get_option('theme_mods_' . $stylesheet, array())
        );
        $widget_options = array();
        foreach (array('widget_custom_html', 'widget_text', 'widget_block') as $key) {
            $val = get_option($key, null);
            if (null !== $val && false !== $val) { $widget_options[$key] = $val; }
        }
        return array(
            'version'          => 1,
            'generated_at_utc' => gmdate('c'),
            'source_url'       => untrailingslashit(home_url()),
            'site_settings'    => array(
                'theme_mods_flatsome'   => $mods_merged,
                'theme_mods_stylesheet' => $stylesheet,
                'sidebars_widgets'      => (array) get_option('sidebars_widgets', array()),
                'widgets'               => $widget_options,
                'source_url'            => untrailingslashit(home_url()),
            ),
            'options'          => array(),
        );
    }

    private function apply_bundle(array $bundle)
    {
        $changes = array('options' => array(), 'site_settings' => array());
        foreach ((array) ($bundle['options'] ?? array()) as $option_name => $option_value) {
            if ($this->option_is_sensitive((string) $option_name)) { continue; }
            update_option((string) $option_name, $option_value, false);
            $changes['options'][] = (string) $option_name;
        }
        if (!empty($bundle['site_settings']) && is_array($bundle['site_settings'])) {
            $changes['site_settings'] = $this->apply_site_settings((array) $bundle['site_settings']);
        }
        return $changes;
    }

    private function build_newest_wins($bundle_generated, $target_last_applied)
    {
        $result = array(
            'bundle_generated_at_utc'    => $bundle_generated,
            'target_last_applied_at_utc' => $target_last_applied,
            'verdict'                    => '' === $target_last_applied ? 'eerste_sync' : '',
        );
        if ('' !== $bundle_generated && '' !== $target_last_applied) {
            $result['verdict'] = $bundle_generated >= $target_last_applied ? 'bundle_is_newer_or_equal' : 'WARNING: target_has_newer_data';
        }
        return $result;
    }

    private function option_is_sensitive($option_name)
    {
        return (bool) preg_match('/(?:api_key|api_secret|client_id|webhook_secret|secret|token|password|salt|license)(?:_|$)/i', $option_name);
    }

    private function apply_site_settings(array $settings)
    {
        $source_url   = (string) ($settings['source_url'] ?? '');
        $target_url   = untrailingslashit(home_url());
        $replace_urls = static function ($data) use ($source_url, $target_url) {
            if ('' === $source_url || $source_url === $target_url) { return $data; }
            $json = wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return json_decode(str_replace($source_url, $target_url, $json), true);
        };
        $results    = array();
        $stylesheet = (string) get_option('stylesheet', 'flatsome');
        $template   = (string) get_option('template', 'flatsome');
        if (!empty($settings['theme_mods_flatsome']) && is_array($settings['theme_mods_flatsome'])) {
            $incoming = (array) $settings['theme_mods_flatsome'];
            update_option('theme_mods_' . $template, array_merge((array) get_option('theme_mods_' . $template, array()), $incoming));
            wp_cache_delete('theme_mods_' . $template, 'options');
            if ($stylesheet !== $template) {
                update_option('theme_mods_' . $stylesheet, array_merge((array) get_option('theme_mods_' . $stylesheet, array()), $incoming));
                wp_cache_delete('theme_mods_' . $stylesheet, 'options');
            }
            $results['theme_mods'] = count($incoming);
        }
        if (!empty($settings['widgets']) && is_array($settings['widgets'])) {
            foreach ($settings['widgets'] as $k => $v) { update_option($k, $replace_urls($v)); }
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
        if (function_exists('flatsome_update_style')) { flatsome_update_style(); }
        $dirs_deleted = array();
        foreach (array('acceleratewp', 'sgoptimizer', 'supercacher', 'wp-cache', 'min') as $d) {
            $dir = WP_CONTENT_DIR . '/cache/' . $d . '/';
            if (is_dir($dir)) { $this->rmdir_recursive($dir); $dirs_deleted[] = $d; }
        }
        if (!empty($dirs_deleted)) { $results['cache_dirs_cleared'] = $dirs_deleted; }
        if (function_exists('wp_cache_clear_cache'))  { wp_cache_clear_cache(); }
        if (function_exists('rocket_clean_domain'))    { rocket_clean_domain(); }
        if (function_exists('w3tc_flush_all'))          { w3tc_flush_all(); }
        foreach (array('SiteGround_Optimizer\Supercacher\Supercacher', 'SiteGround_Optimizer\Modules\Supercacher\Supercacher', 'Supercacher') as $cls) {
            if (class_exists($cls) && method_exists($cls, 'purge_cache')) { call_user_func(array($cls, 'purge_cache')); break; }
        }
        if (function_exists('sg_cachepress_purge_cache')) { sg_cachepress_purge_cache(); }
        do_action('sg_cachepress_purge_cache'); do_action('acceleratewp_purge_all'); do_action('sgo_cache_flush');
        $results['cache_flushed'] = true;
        return $results;
    }

    private function rmdir_recursive($dir)
    {
        if (!is_dir($dir)) { return; }
        foreach (scandir($dir) as $item) {
            if ('.' === $item || '..' === $item) { continue; }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rmdir_recursive($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
