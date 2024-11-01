<?php
/*
Plugin Name:  Skeerel
Description:  Le plugin s'intègre à Woocommerce pour permettre à vos clients de payer en 1-clic avec Skeerel.
Plugin URI:   https://wordpress.org/plugins/skeerel/
Author:       Skeerel
Version:      1.1.0
Text Domain:  skeerel
Domain Path:  /languages
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
WC requires at least: 3.5.0
WC tested up to: 4.0.1
*/


// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}


// load text domain
function skeerel_load_textdomain()
{

    load_plugin_textdomain('skeerel', false, plugin_dir_path(__FILE__) . 'languages/');

}

add_action('plugins_loaded', 'skeerel_load_textdomain');


// include plugin dependencies: admin only
if (is_admin()) {

    require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
    require_once plugin_dir_path(__FILE__) . 'admin/check-woocommerce.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';

    // PHP library for the Skeerel API
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

// include plugin dependencies: admin and public
require_once plugin_dir_path(__FILE__) . 'includes/core-functions.php';

// woocommerce
function skeerel_init_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'skeerel_missing_wc_notice');
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'admin/check-woocommerce.php';
    require_once plugin_dir_path(__FILE__) . 'woocommerce/Skeerel_Gateway.php';
    require_once plugin_dir_path(__FILE__) . 'woocommerce/add-payment-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'woocommerce/skeerel-core-functions.php';

}

add_action('plugins_loaded', 'skeerel_init_woocommerce');

add_action('admin_notices', 'skeerel_check_if_api_key_still_valid');

add_action('admin_notices', 'skeerel_warning_wc_notice', 20);
add_action('admin_notices', 'skeerel_warning_domain_name', 20);
add_action('admin_notices', 'skeerel_warning_api_key_not_valid', 20);


// Registrer API routes.
function skeerel_registrer_controller()
{
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    require_once plugin_dir_path(__FILE__) . 'api/Skeerel_Custom_Route.php';

    $controller = new Skeerel_Custom_Route();
    $controller->register_routes();
}

add_action('rest_api_init', 'skeerel_registrer_controller');

// default plugin options
function skeerel_options_default()
{

    return array(
        'custom_activate_skeerel' => true,
        'custom_display_skeerel_at_checkout' => true,
        'custom_display_skeerel_encart_at_checkout' => true,
        'custom_display_skeerel_on_product_page' => true,
        'custom_use_test_mode' => false,
        'custom_admin_only' => true,
        'custom_skeerel_id' => '',
        'custom_skeerel_secret_key' => '',
        'custom_button_text_at_checkout' => esc_html__('Skeerel Pay', 'skeerel'),
        'custom_button_text_on_product_page' => esc_html__('Achat express', 'skeerel'),

        'step_id' => 1 // used for the onboarding procedure
    );

}

// enqueue admin style
function skeerel_enqueue_style_admin_page($hook)
{

    /*
        wp_enqueue_style(
            string           $handle,
            string           $src = '',
            array            $deps = array(),
            string|bool|null $ver = false,
            string           $media = 'all'
        )
    */

    if ('toplevel_page_skeerel' === $hook) {
        $src = plugin_dir_url(__FILE__) . 'admin/css/bootstrap.css';
        wp_enqueue_style('skeerel-admin-bootstrap', $src, array(), true, 'all');

        $src = plugin_dir_url(__FILE__) . 'admin/fontawesome/css/all.css';
        wp_enqueue_style('skeerel-admin-fontawesome', $src, array(), true, 'all');

        $src = plugin_dir_url(__FILE__) . 'admin/css/onboarding.css';
        wp_enqueue_style('skeerel-admin-onboarding', $src, array('skeerel-admin-bootstrap'), true, 'all');
    }

}

add_action('admin_enqueue_scripts', 'skeerel_enqueue_style_admin_page');


// enqueue admin script
function skeerel_enqueue_script_admin_page($hook)
{

    /*
        wp_enqueue_script(
            string           $handle,
            string           $src = '',
            array            $deps = array(),
            string|bool|null $ver = false,
            bool             $in_footer = false
        )
    */

    if ('toplevel_page_skeerel' === $hook) {
        $src = plugin_dir_url(__FILE__) . 'admin/js/show-password/bootstrap-show-password.min.js';
        wp_enqueue_script('skeerel-admin-showpassword', $src, array(), true, false);

        $src = plugin_dir_url(__FILE__) . 'admin/js/onboarding.js';
        wp_enqueue_script('skeerel-admin-onboarding', $src, array('skeerel-admin-showpassword'), true, false);

        $add_js = "var loader_url = '" . plugin_dir_url(__FILE__) . "woocommerce/images/loader.svg'";
        wp_add_inline_script('skeerel-admin-onboarding', $add_js, 'before');
    }
}

add_action('admin_enqueue_scripts', 'skeerel_enqueue_script_admin_page');

// whenever an user activates the plugin, redirect to the onboarding section
function skeerel_activate()
{

    set_transient('skeerel_activated_plugin', 'Skeerel', 60);
}

register_activation_hook(__FILE__, 'skeerel_activate');

// redirect to the onboarding section if Woocommerce is active
function skeerel_load_plugin()
{
    $options = get_option('skeerel', skeerel_options_default());

    if (is_admin() && get_transient('skeerel_activated_plugin') == 'Skeerel' && class_exists('WooCommerce')
        && $options['step_id'] != "woocommerce_settings") {

        delete_transient('activated_plugin');

        wp_redirect(admin_url('admin.php?page=skeerel'));
        exit();
    }
}

add_action('admin_init', 'skeerel_load_plugin');

// start session before headers are sent (to generate a session state parameter) \Skeerel\Skeerel::generateSessionStateParameter()
function skeerel_session_start()
{
    session_start();
}

add_action('plugins_loaded', 'skeerel_session_start');
