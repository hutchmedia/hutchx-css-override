<?php
/**
 * Plugin Name: HutchX CSS Override
 * Description: Manage custom site-wide CSS, header scripts, external-link behaviour, login logo — with GitHub-powered updates baked in.
 * Version: 1.6.2
 * Author: HutchX
 * License: GPL-2.0-or-later
 * Text Domain: hutchx-css-override
 */

if (!defined('ABSPATH')) exit;

/**
 * On activation, seed defaults.
 */
register_activation_hook(__FILE__, function () {
    add_option('hutchx_custom_css', '');
    add_option('hutchx_header_scripts', '');
    add_option('hutchx_open_external_links', 1);
    add_option('hutchx_login_logo_url', '');
});

/**
 * Admin menu — put right near the top (under Dashboard).
 */
add_action('admin_menu', function () {
    add_menu_page(
        __('HutchX CSS Override', 'hutchx-css-override'),
        'HutchX CSS',
        'manage_options',
        'hutchx-css',
        'hutchx_css_settings_page',
        'dashicons-admin-customizer',
        3
    );
});

/**
 * Register settings we store.
 */
add_action('admin_init', function () {
    register_setting('hutchx_css_group', 'hutchx_custom_css');          // raw CSS
    register_setting('hutchx_css_group', 'hutchx_header_scripts');      // raw head HTML/JS
    register_setting('hutchx_css_group', 'hutchx_open_external_links'); // 0/1
    register_setting('hutchx_css_group', 'hutchx_login_logo_url');      // URL
});

/**
 * Admin page renderer.
 */
function hutchx_css_settings_page() {
    if (!current_user_can('manage_options')) return;
    $css   = get_option('hutchx_custom_css', '');
    $head  = get_option('hutchx_header_scripts', '');
    $links = (int) get_option('hutchx_open_external_links', 1);
    $logo  = get_option('hutchx_login_logo_url', '');
    ?>
    <div class="wrap">
        <h1>HutchX — Site Overrides</h1>
        <p style="max-width:800px;">CSS loads last in <code>&lt;head&gt;</code>. Header scripts print in <code>&lt;head&gt;</code>. External-link behaviour is JS-based. Login logo customises wp-login.</p>

        <form method="post" action="options.php">
            <?php settings_fields('hutchx_css_group'); ?>

            <h2 class="title">Custom CSS</h2>
            <p><em>Injected late in <code>&lt;head&gt;</code> so it wins most specificity fights.</em></p>
            <textarea name="hutchx_custom_css" style="width:100%;height:280px;font-family:ui-monospace, SFMono-Regular, Menlo, monospace;"><?php echo esc_textarea($css); ?></textarea>

            <hr>

            <h2 class="title">Header Scripts (<code>&lt;head&gt;</code>)</h2>
            <p><em>Prints as-is. Only trusted admins should edit.</em></p>
            <textarea name="hutchx_header_scripts" style="width:100%;height:200px;font-family:ui-monospace, SFMono-Regular, Menlo, monospace;"><?php echo esc_textarea($head); ?></textarea>

            <hr>

            <h2 class="title">External Links Behaviour</h2>
            <label>
                <input type="checkbox" name="hutchx_open_external_links" value="1" <?php checked($links, 1); ?>>
                Open external links in a new window (adds <code>target="_blank"</code> and <code>rel="noopener noreferrer"</code>)
            </label>
            <p><em>Excludes same-domain links, anchors, <code>mailto:</code>, <code>tel:</code>, and <code>javascript:</code>.</em></p>

            <hr>

            <h2 class="title">WP-Login Logo</h2>
            <p>URL to a logo image for the login page (SVG/PNG recommended).</p>
            <input type="url" name="hutchx_login_logo_url" value="<?php echo esc_attr($logo); ?>" style="width:100%;max-width:600px;">
            <p><em>Tip: ~320×80-ish works well. SVG scales nicely.</em></p>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}

/**
 * Output custom CSS (late in <head>)
 */
add_action('wp_head', function () {
    $css = get_option('hutchx_custom_css', '');
    if ($css) {
        echo "\n<style id=\"hutchx-css-overrides\">\n{$css}\n</style>\n";
    }
}, 999);

/**
 * Output header scripts (in <head>).
 */
add_action('wp_head', function () {
    $head = get_option('hutchx_header_scripts', '');
    if ($head) {
        echo "\n<!-- HutchX Header Scripts -->\n{$head}\n<!-- /HutchX Header Scripts -->\n";
    }
}, 998);

/**
 * External links enhancer (footer) if enabled.
 */
add_action('wp_footer', function () {
    if (!get_option('hutchx_open_external_links', 1)) return;
    ?>
<script id="hutchx-external-links">
(function(){
    var here = window.location.hostname;
    function isExternal(a){
        try {
            var u = new URL(a.href, window.location.href);
            if (!/^https?:$/.test(u.protocol)) return false; // ignore mailto:, tel:, javascript:
            if (!u.hostname || u.hostname === here) return false;
            return true;
        } catch(e){ return false; }
    }
    function enhance(){
        var as = document.querySelectorAll('a[href]');
        for (var i=0;i<as.length;i++){
            var a = as[i];
            var href = a.getAttribute('href') || '';
            if (href[0] === '#' || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0 || href.indexOf('javascript:') === 0) continue;
            if (isExternal(a)) {
                if (!a.target) a.target = '_blank';
                var rel = (a.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
                if (rel.indexOf('noopener') === -1) rel.push('noopener');
                if (rel.indexOf('noreferrer') === -1) rel.push('noreferrer');
                a.setAttribute('rel', rel.join(' ').trim());
            }
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }
})();
</script>
    <?php
}, 99);

/**
 * Login page logo styling.
 */
add_action('login_enqueue_scripts', function () {
    $logo = trim(get_option('hutchx_login_logo_url', ''));
    if (!$logo) return;
    ?>
<style id="hutchx-login-logo">
    .login h1 a {
        background-image: url('<?php echo esc_url($logo); ?>') !important;
        width: 320px !important;
        height: 80px !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        margin: 0 auto 20px !important;
    }
</style>
    <?php
});
add_filter('login_headerurl', function(){ return home_url('/'); });
add_filter('login_headertext', function(){ return get_bloginfo('name'); });

/**
 * ──────────────────────────────────────────────────────────────────────────────
 * Hard-coded GitHub Updater (public repo)
 * Repo: hutchmedia/hutchx-css-override
 * ──────────────────────────────────────────────────────────────────────────────
 */
class HutchX_GitHub_Updater {
    private $file;
    private $plugin_basename;
    private $slug;
    private $version;

    public function __construct($file) {
        $this->file = $file;
        $this->plugin_basename = plugin_basename($file);
        $this->slug = dirname($this->plugin_basename);
        if ($this->slug === '.') $this->slug = basename($this->plugin_basename, '.php');
        $this->version = $this->get_plugin_version();

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugins_api'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
    }

    private function get_plugin_version() {
        if (!function_exists('get_plugin_data')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $data = get_plugin_data($this->file, false, false);
        return isset($data['Version']) ? $data['Version'] : '0.0.0';
    }

    private function get_repo() {
        return 'hutchmedia/hutchx-css-override';
    }

    private function fetch_latest_release() {
        $url = 'https://api.github.com/repos/' . $this->get_repo() . '/releases/latest';
        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'WordPress; ' . home_url('/'),
            ),
            'timeout' => 15,
        );
        $res = wp_remote_get($url, $args);
        if (is_wp_error($res)) return null;
        $code = wp_remote_retrieve_response_code($res);
        if ($code !== 200) return null;
        $body = wp_remote_retrieve_body($res);
        $json = json_decode($body, true);
        return is_array($json) ? $json : null;
    }

    private function normalize_tag($tag) {
        if (!$tag) return '';
        return ltrim($tag, 'vV');
    }

    public function check_for_update($transient) {
        if (empty($transient->checked)) return $transient;

        $latest = $this->fetch_latest_release();
        if (!$latest) return $transient;

        $new_version = $this->normalize_tag(isset($latest['tag_name']) ? $latest['tag_name'] : '');
        if (!$new_version) return $transient;

        if (version_compare($new_version, $this->version, '<=')) return $transient;

        $zip_url = isset($latest['zipball_url']) ? $latest['zipball_url'] : '';
        if (!$zip_url) return $transient;

        $obj = new stdClass();
        $obj->slug = $this->slug;
        $obj->plugin = $this->plugin_basename;
        $obj->new_version = $new_version;
        $obj->url = 'https://github.com/' . $this->get_repo();
        $obj->package = $zip_url;
        $obj->tested = get_bloginfo('version');
        $obj->requires = '5.0';
        $obj->icons = array();

        $transient->response[$this->plugin_basename] = $obj;
        return $transient;
    }

    public function plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information') return $result;
        if (!isset($args->slug) || $args->slug !== $this->slug) return $result;

        $latest = $this->fetch_latest_release();
        if (!$latest) return $result;

        $info = new stdClass();
        $info->name = 'HutchX CSS Override';
        $info->slug = $this->slug;
        $info->version = $this->normalize_tag(isset($latest['tag_name']) ? $latest['tag_name'] : '');
        $info->author = '<a href="https://hutchx.com/">HutchX</a>';
        $info->homepage = 'https://github.com/' . $this->get_repo();
        $info->download_link = isset($latest['zipball_url']) ? $latest['zipball_url'] : '';
        $info->sections = array(
            'description' => wp_kses_post(isset($latest['body']) ? $latest['body'] : 'Managed CSS, header scripts, external-link behaviour, and login logo.'),
            'changelog'   => wp_kses_post(isset($latest['body']) ? $latest['body'] : ''),
        );
        return $info;
    }

    public function post_install($response, $hook_extra, $result) {
        $proper_folder_name = dirname($this->plugin_basename);
        if ($proper_folder_name === '.' ) $proper_folder_name = 'hutchx-css-override';

        $installed_folder = $result['destination'];
        $destination = trailingslashit(WP_PLUGIN_DIR) . $proper_folder_name;

        if ($installed_folder !== $destination) {
            global $wp_filesystem;
            if ($wp_filesystem->move($installed_folder, $destination, true)) {
                $result['destination'] = $destination;
            }
        }
        if (is_plugin_active($this->plugin_basename)) {
            activate_plugin($this->plugin_basename);
        }
        return $result;
    }
}

if (is_admin()) {
    new HutchX_GitHub_Updater(__FILE__);
}
