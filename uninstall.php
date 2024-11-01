<?php

// exit if uninstall constant is not defined
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// delete transients
delete_transient('skeerel_activated_plugin');
delete_transient('skeerel_wc_notice');
delete_transient('skeerel_wc_notice_domain_name');
delete_transient('skeerel_wc_api_key_not_valid');
delete_transient('disp_skeerel_wc_error');

// delete plugin options
delete_option('skeerel');
delete_option('woocommerce_skeerel_settings');
