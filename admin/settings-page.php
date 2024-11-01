<?php // skeerel - Settings Page


// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}


// display the plugin settings page
function skeerel_display_settings_page()
{

    // check if user is allowed access
    if (!current_user_can('manage_options')) return;

    // redirect the user to the woocommerce settings, if the onboarding was already completed
    $skeerel_options = get_option('skeerel', skeerel_options_default());

    if ($skeerel_options['step_id'] == "woocommerce_settings") {
        wp_redirect('admin.php?page=wc-settings&tab=checkout&section=skeerel');
        exit;
    }

    wp_enqueue_script('wp-api');

    // add a nonce to prevent CSRF attacks
    wp_localize_script('wp-api', 'wpApiSettings', array(
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest')
    ));

    // get skeerel options
    $options = get_option('skeerel', skeerel_options_default());

    if (isset($options['step_id']) && !empty($options['step_id'])) {
        $step_id = sanitize_text_field($options['step_id']);
    }

    $step_id = 1;
    ?>

    <input type="hidden" value="<?php echo $step_id ?>" id="step_id"/>
    <?php

    include_once __DIR__ . '/onboarding.php';


}


