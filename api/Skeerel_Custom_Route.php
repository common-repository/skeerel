<?php

class Skeerel_Custom_Route extends WP_REST_Controller
{

    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace;
    /**
     * Version of the REST API
     *
     * @var string
     */
    protected $version = '1';

    /**
     * Skeerel_Custom_Route constructor.
     */
    public function __construct()
    {
        $this->namespace = 'skeerel/v' . $this->version;
    }

    /**
     * Register the routes for the objects of the controller.
     * Route : submit-api-key
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . 'submit-api-key', array(
            array(
                'methods' => WP_REST_Server::CREATABLE, // POST
                'callback' => array($this, 'submit_api_key'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args' => array(
                    'website_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => array($this, 'validate_text_field')
                    ),
                    'secret' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => array($this, 'validate_text_field')
                    )
                )
            )
        ));

        /**
         * Route: check-api-key
         */
        register_rest_route($this->namespace, '/' . 'check-api-key', array(
                array(
                    'methods' => WP_REST_Server::CREATABLE, // POST
                    'callback' => array($this, 'check_api_key'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args' => array(
                        'website_id' => array(
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => array($this, 'validate_text_field')
                        ),
                        'secret' => array(
                            'required' => false,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => array($this, 'validate_text_field')
                        )
                    )
                ))
        );


        /**
         * Route: get-custom-checkbox
         */
        register_rest_route($this->namespace, '/' . 'get-custom-checkbox', array(
                array(
                    'methods' => WP_REST_Server::READABLE, // GET
                    'callback' => array($this, 'get_custom_checkbox'),
                    'permission_callback' => array($this, 'get_items_permissions_check')
                ))
        );


        /**
         * Route: get-delivery-methods
         */
        register_rest_route($this->namespace, '/' . 'get-delivery-methods', array(
                array(
                    'methods' => WP_REST_Server::READABLE, // GET
                    'callback' => array($this, 'get_delivery_methods'),
                    'args' => array(
                        'country' => array(
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => function ($param, $request, $key) {
                                return (bool)preg_match('/^[a-zA-Z]+$/i', $param);
                            }
                        ),
                        'zip_code' => array(
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => function ($param, $request, $key) {
                                return (bool)is_numeric($param);
                            }
                        ),
                        'product_id' => array(
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => function ($param, $request, $key) {
                                return (bool)preg_match('/^([0-9]+:[0-9]+:[0-9]+,?)+$/i', $param);
                            }
                        )
                    )
                ))
        );

        /**
         * Route: get-authorized-domain-names
         */
        register_rest_route($this->namespace, '/' . 'get-authorized-domain-names', array(
                array(
                    'methods' => WP_REST_Server::READABLE, // GET
                    'callback' => array($this, 'get_authorized_domain_names'),
                    'permission_callback' => array($this, 'get_items_permissions_check')
                )
            )
        );


        /**
         * Route: submit-settings
         */
        register_rest_route($this->namespace, '/' . 'submit-settings', array(
            array(
                'methods' => WP_REST_Server::CREATABLE, // POST
                'callback' => array($this, 'submit_settings'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args' => array(
                    'test-mode' => array(
                        'required' => true,
                        'sanitize_callback' => array($this, 'to_boolean'),
                        'validate_callback' => array($this, 'is_boolean')
                    ),
                    'admin-mode' => array(
                        'required' => true,
                        'sanitize_callback' => array($this, 'to_boolean'),
                        'validate_callback' => array($this, 'is_boolean')
                    ),
                    'checkout-button' => array(
                        'required' => true,
                        'sanitize_callback' => array($this, 'to_boolean'),
                        'validate_callback' => array($this, 'is_boolean')
                    ),
                    'checkout-encart-button' => array(
                        'required' => true,
                        'sanitize_callback' => array($this, 'to_boolean'),
                        'validate_callback' => array($this, 'is_boolean')
                    ),
                    'product-button' => array(
                        'required' => true,
                        'sanitize_callback' => array($this, 'to_boolean'),
                        'validate_callback' => array($this, 'is_boolean')
                    )
                )
            )
        ));

    }

    /**
     * Verify Skeerel's website id and secret, and store them in the database
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function submit_api_key($request)
    {
        $params = $request->get_params();  // $params is already sanitized

        try {
            $skeerel = new \Skeerel\Skeerel($params['website_id'], $params['secret']);
            $data = $skeerel->getWebsiteDetails();

            // store website id & secret in the database
            $current_options = get_option('skeerel', skeerel_options_default());

            $current_options['custom_skeerel_id'] = $params['website_id'];
            $current_options['custom_skeerel_secret_key'] = $params['secret'];
            $current_options['step_id'] = 2;

            update_option('skeerel', $current_options);

            $site_url = $_SERVER['HTTP_HOST'];
            $site_url = preg_replace("/:[0-9]+/", "", $site_url); // fix: remove port

            $skeerel_status = $data->getStatus();
            $skeerel_domains = $data->getDomains();

            // create the associated regex /(mydomain\.com|www.mydomain\.com|...)/
            $regex = '/(' . str_replace('.', '\.', implode('|', $skeerel_domains)) . ')/';
            $skeerel_domain_OK = (bool)preg_match($regex, $site_url);

            if ($skeerel_status == "VERIFIED" && $skeerel_domain_OK) {
                $step_id = '3';
            } else if ($skeerel_status == "PENDING" && $skeerel_domain_OK) {
                $step_id = '3_1';
            } else if ($skeerel_status == "NOT_VERIFIED" && $skeerel_domain_OK) {
                $step_id = '3_2';
            } else if (($skeerel_status == "PENDING" || $skeerel_status == "NOT_VERIFIED") && !$skeerel_domain_OK) {
                $step_id = '3_3';
            } else if ($skeerel_status == "VERIFIED" && !$skeerel_domain_OK) {
                $step_id = '3_4';
            }

        } catch (Exception $e) {
            return new WP_Error('api-key-error', esc_html__('Connexion échouée: identifiants incorrects', 'skeerel'), array('status' => 500));
        }

        return new WP_REST_Response($step_id, 200);
    }

    /**
     * Check Skeerel's website id and secret
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function check_api_key($request)
    {
        $params = $request->get_params(); // $params is already sanitized
        $current_options = get_option('woocommerce_skeerel_settings');

        if (!isset($params['secret']) || empty($params['secret']))
            $secret = sanitize_text_field($current_options['skeerel_secret_key']);
        else
            $secret = $params['secret'];

        try {
            $skeerel = new \Skeerel\Skeerel($params['website_id'], $secret);
            $data = $skeerel->getWebsiteDetails();
        } catch (Exception $e) {
            return new WP_Error('api-key-error', esc_html__('Connexion échouée: identifiants incorrects', 'skeerel'), array('status' => 500));
        }

        return new WP_REST_Response(array('message' => esc_html__('Connexion réussie !', 'skeerel')), 200);
    }


    /** Disable custom checkbox
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_custom_checkbox($request)
    {
        $current_options = get_option('woocommerce_skeerel_settings');

        try {
            $skeerel = new \Skeerel\Skeerel(sanitize_text_field($current_options['skeerel_id']), sanitize_text_field($current_options['skeerel_secret_key']));
            $data = $skeerel->getWebsiteDetails();

            $skeerel_status = $data->getStatus();

            // default
            $checkbox_enabled = array(
                'admin_only' => true,
                'use_test_mode' => true,
                'display_skeerel_at_checkout' => true,
                'display_skeerel_encart_at_checkout' => true,
                'display_skeerel_on_product_page' => true
            );

            $site_url = $_SERVER['HTTP_HOST'];
            $site_url = preg_replace("/:[0-9]+/", "", $site_url); // fix: remove port

            $skeerel_domains = $data->getDomains();

            // create the associated regex /(mydomain\.com|www.mydomain\.com|...)/
            $regex = '/(' . str_replace('.', '\.', implode('|', $skeerel_domains)) . ')/';
            $skeerel_domain_OK = (bool)preg_match($regex, $site_url);

            // depending on the website configuration, some settings will be disabled
            if ($skeerel_status == "VERIFIED" && $skeerel_domain_OK) {
                // step 3 : nothing to do
            } else if ($skeerel_status == "PENDING" && $skeerel_domain_OK) {
                // step 3_1
                $checkbox_enabled['use_test_mode'] = false;
            } else if ($skeerel_status == "NOT_VERIFIED" && $skeerel_domain_OK) {
                // step 3_2
                $checkbox_enabled['use_test_mode'] = false;
            } else if (($skeerel_status == "PENDING" || $skeerel_status == "NOT_VERIFIED") && !$skeerel_domain_OK) {
                // step 3_3
                $checkbox_enabled['use_test_mode'] = false;
                $checkbox_enabled['display_skeerel_at_checkout'] = false;
                $checkbox_enabled['display_skeerel_on_product_page'] = false;
                $checkbox_enabled['display_skeerel_encart_at_checkout'] = false;
            } else if ($skeerel_status == "VERIFIED" && !$skeerel_domain_OK) {
                // step 3_4
                $checkbox_enabled['display_skeerel_at_checkout'] = false;
                $checkbox_enabled['display_skeerel_on_product_page'] = false;
                $checkbox_enabled['display_skeerel_encart_at_checkout'] = false;
            }

            return new WP_REST_Response($checkbox_enabled, 200);

        } catch (Exception $e) {
        }

    }


    /**
     * Get delivery methods from Woocommerce
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function get_delivery_methods($request)
    {
        require_once WC_ABSPATH . 'includes/wc-cart-functions.php';
        require_once WC_ABSPATH . 'includes/wc-notice-functions.php';

        WC()->session = new WC_Session_Handler();
        WC()->session->init();
        WC()->customer = new WC_Customer(get_current_user_id(), true);
        WC()->cart = new WC_Cart();

        $products = explode(',', $_GET['product_id']);

        // make country case insentive, i.e fr => FR
        $_GET['country'] = strtoupper($_GET['country']);

        foreach ($products as $product) {
            $product = explode(':', $product);
            WC()->cart->add_to_cart($product[0], $product[1], $product[2]);
        }

        WC()->customer->set_shipping_location($_GET['country'], '', $_GET['zip_code']);
        WC()->cart->calculate_shipping();

        // no package ?
        if (empty(WC()->shipping()->packages))
            return new WP_REST_RESPONSE(array(), 200);

        $package = WC()->shipping()->packages[0];

        $equivalence = array(
            'flat_rate' => 'home',
            'local_pickup' => 'relay',
            'free_shipping' => 'home'
        );

        $shipping_methods = array();
        $primary = true;

        foreach ($package['rates'] as $rate) {

            $rate_details = array(
                "id" => $rate->get_id(),
                "type" => (array_key_exists($rate->get_method_id(), $equivalence)) ? $equivalence[$rate->get_method_id()] : "home",
                "primary" => $primary,
                "name" => $rate->get_label(),
                "delivery_text_content" => $rate->get_label(),
                "price" => $rate->get_cost() * 100
            );

            if ($rate->get_method_id() == 'local_pickup') {
                // create default pickup
                $pickup = array(
                    "id" => "1",
                    "name" => esc_html__("Point relais", 'skeerel'),
                    "address" => esc_html__("Adresse communiquée par le vendeur", 'skeerel'),
                    "zip_code" => null,
                    "city" => null,
                    "country" => null
                );

                $rate_details['pick_up_points'] = array($pickup);
            }


            $shipping_methods[] = $rate_details;
            if ($primary) $primary = false;

        }

        return new WP_REST_RESPONSE($shipping_methods, 200);
    }


    /**
     * Update Skeerel settings
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function submit_settings($request)
    {
        $params = $request->get_params();

        $current_options = get_option('skeerel', skeerel_options_default());

        $skeerel_id = sanitize_text_field($current_options['custom_skeerel_id']);
        $skeerel_secret_key = sanitize_text_field($current_options['custom_skeerel_secret_key']);

        try {
            $skeerel = new \Skeerel\Skeerel($skeerel_id, $skeerel_secret_key);
            $data = $skeerel->getWebsiteDetails();

            $skeerel_status = $data->getStatus();

            // default
            $checkbox_enabled = array(
                'test-mode' => true,
                'admin-mode' => true,
                'checkout-button' => true,
                'checkout-encart-button' => true,
                'product-button' => true
            );

            if (!isset($current_options['custom_use_test_mode']))
                $current_options['custom_use_test_mode'] = 1;

            if (!isset($current_options['custom_admin_only']))
                $current_options['custom_admin_only'] = 1;

            if (!isset($current_options['custom_display_skeerel_at_checkout']))
                $current_options['custom_display_skeerel_at_checkout'] = 1;

            if (!isset($current_options['custom_display_skeerel_on_product_page']))
                $current_options['custom_display_skeerel_on_product_page'] = 1;

            if (!isset($current_options['custom_display_skeerel_encart_at_checkout']))
                $current_options['custom_display_skeerel_encart_at_checkout'] = 1;

            // default: current values
            $checkbox_custom_values = array(
                'test-mode' => (bool)$current_options['custom_use_test_mode'],
                'admin-mode' => (bool)$current_options['custom_admin_only'],
                'checkout-button' => (bool)$current_options['custom_display_skeerel_at_checkout'],
                'product-button' => (bool)$current_options['custom_display_skeerel_on_product_page'],
                'checkout-encart-button' => (bool)$current_options['custom_display_skeerel_encart_at_checkout']
            );

            $site_url = $_SERVER['HTTP_HOST'];
            $site_url = preg_replace("/:[0-9]+/", "", $site_url); // fix: remove port

            $skeerel_domains = $data->getDomains();

            // create the associated regex /(mydomain\.com|www.mydomain\.com|...)/
            $regex = '/(' . str_replace('.', '\.', implode('|', $skeerel_domains)) . ')/';
            $skeerel_domain_OK = (bool)preg_match($regex, $site_url);

            // depending on the website configuration, some settings will be disabled
            if ($skeerel_status == "VERIFIED" && $skeerel_domain_OK) {
                // step 3 : nothing to do
            } else if ($skeerel_status == "PENDING" && $skeerel_domain_OK) {
                // step 3_1
                $checkbox_enabled['test-mode'] = false;
                $checkbox_custom_values['test-mode'] = true;
            } else if ($skeerel_status == "NOT_VERIFIED" && $skeerel_domain_OK) {
                // step 3_2
                $checkbox_enabled['test-mode'] = false;
                $checkbox_custom_values['test-mode'] = true;
            } else if (($skeerel_status == "PENDING" || $skeerel_status == "NOT_VERIFIED") && !$skeerel_domain_OK) {
                // step 3_3
                $checkbox_enabled['test-mode'] = false;
                $checkbox_custom_values['test-mode'] = true;

                $checkbox_enabled['checkout-button'] = false;
                $checkbox_custom_values['checkout-button'] = false;

                $checkbox_enabled['product-button'] = false;
                $checkbox_custom_values['product-button'] = false;

                $checkbox_enabled['checkout-encart-button'] = false;
                $checkbox_custom_values['checkout-encart-button'] = false;
            } else if ($skeerel_status == "VERIFIED" && !$skeerel_domain_OK) {
                // step 3_4
                $checkbox_enabled['checkout-button'] = false;
                $checkbox_custom_values['checkout-button'] = false;

                $checkbox_enabled['product-button'] = false;
                $checkbox_custom_values['product-button'] = false;

                $checkbox_enabled['checkout-encart-button'] = false;
                $checkbox_custom_values['checkout-encart-button'] = false;
            }

            // update skeerel settings
            $current_options['custom_use_test_mode'] = ($checkbox_enabled['test-mode']) ? $params['test-mode'] : $checkbox_custom_values['test-mode'];
            $current_options['custom_admin_only'] = ($checkbox_enabled['admin-mode']) ? $params['admin-mode'] : $checkbox_custom_values['admin-mode'];

            $current_options['custom_display_skeerel_at_checkout'] = ($checkbox_enabled['checkout-button']) ? $params['checkout-button'] : $checkbox_custom_values['checkout-button'];
            $current_options['custom_display_skeerel_encart_at_checkout'] = ($checkbox_enabled['checkout-encart-button']) ? $params['checkout-encart-button'] : $checkbox_custom_values['checkout-encart-button'];
            $current_options['custom_display_skeerel_on_product_page'] = ($checkbox_enabled['product-button']) ? $params['product-button'] : $checkbox_custom_values['product-button'];

            $current_options['step_id'] = 4; // onboarding completed
            update_option('skeerel', $current_options);

        } catch (Exception $e) {
            return new WP_Error('api-key-error', esc_html__('Connexion échouée: identifiants incorrects', 'skeerel'), array('status' => 500));
        }

        return new WP_REST_Response($current_options, 200);
    }


    /* Get authorized domain names from Skeerel
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_authorized_domain_names($request)
    {

        $current_options = get_option('skeerel', skeerel_options_default());

        $skeerel_id = sanitize_text_field($current_options['custom_skeerel_id']);
        $skeerel_secret_key = sanitize_text_field($current_options['custom_skeerel_secret_key']);

        try {
            $skeerel = new \Skeerel\Skeerel($skeerel_id, $skeerel_secret_key);
            $domains = $skeerel->getWebsiteDetails()->getDomains();

            return new WP_REST_Response(implode(', ', $domains), 200);
        } catch (Exception $e) {
            return new WP_Error('api-key-error', esc_html__('[erreur]', 'skeerel'));
        }

    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_items_permissions_check($request)
    {
        return current_user_can('manage_woocommerce');
    }


    /**
     * Check whether the parameter is a boolean or not
     */
    public function is_boolean($param, $request, $key)
    {
        return ($param == "true" || $param == "false");
    }

    /**
     * Check whether the parameter is a text element or not
     */
    public function validate_text_field($param, $request, $key)
    {
        return (bool)preg_match('/^[a-zA-Z0-9\-\_]*$/i', $param);
    }

    /**
     * Transfrom the parameter to a boolean
     */
    public function to_boolean($param)
    {
        return ($param == "true");
    }


}
