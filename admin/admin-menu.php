<?php // skeerel - Admin Menu


// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}


// add top-level administrative menu
function skeerel_add_toplevel_menu()
{

    /*

    add_menu_page(
        string   $page_title,
        string   $menu_title,
        string   $capability,
        string   $menu_slug,
        callable $function = '',
        string   $icon_url = '',
        int      $position = null
    )

    */

    add_menu_page(
        esc_html__('Espace de configuration', 'skeerel'),
        esc_html__('Skeerel', 'skeerel'),
        'manage_options',
        'skeerel',
        'skeerel_display_settings_page',
        'data:image/svg+xml;base64,' . base64_encode(file_get_contents(__DIR__ . '/images/logo.svg')),
        55
    );

}

add_action('admin_menu', 'skeerel_add_toplevel_menu');


