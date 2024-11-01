<?php
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'skeerel_wc_settings',
    array(
        'enabled' => array(
            'title' => __('Paramètres généraux', 'skeerel'),
            'label' => __('Activer Skeerel', 'skeerel'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no',
        ),
        /* Display */
        'display_skeerel_at_checkout' => array(
            'title' => __('Affichage', 'skeerel'),
            'label' => __('Activer Skeerel sur la page panier', 'skeerel'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no',
        ),
        'display_skeerel_on_product_page' => array(
            'label' => __('Activer Skeerel sur les pages produits', 'skeerel'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no',
        ),
        'display_skeerel_encart_at_checkout' => array(
            'label' => __("Activer l'encart achat en 1-clic sur la page panier", 'skeerel'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no',
        ),
        /* Settings */
        'use_test_mode' => array(
            'title' => __('Mode test', 'skeerel'),
            'label' => __('Utiliser Skeerel en mode test (transactions fictives)', 'skeerel'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'yes',
        ),
        'admin_only' => array(
            'title' => __('Autorisations', 'skeerel'),
            'label' => __('Activer Skeerel uniquement pour les administrateurs.', 'skeerel'),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'yes',
        ),
        /* API credentials */
        'skeerel_id' => array(
            'title' => __("Identifiant d'API", 'skeerel'),
            'type' => 'text',
            'description' => __("Identifiant du site internet", 'skeerel'),
            'default' => __('Identifiant du site internet', 'skeerel'),
            'desc_tip' => true,
        ),
        'skeerel_secret_key' => array(
            'title' => __("Clé secrète", 'skeerel'),
            'type' => 'password',
            'description' => __("La clé secrète n'est pas affichée par défaut pour des raisons de sécurité. Vous pouvez continuer à utiliser Skeerel", 'skeerel'),
            'default' => '',
            'desc_tip' => true,
        ),
        /* Text buttons */
        'button_text_at_checkout' => array(
            'title' => __('Texte bouton page panier', 'skeerel'),
            'type' => 'text',
            'description' => __("Ce paramètre contrôle le texte que l'utilisateur voit lors de la commande", 'skeerel'),
            'default' => __('Achat express avec Skeerel', 'skeerel'),
            'desc_tip' => true,
        ),
        'button_text_on_product_page' => array(
            'title' => __('Texte bouton pages produit', 'skeerel'),
            'type' => 'text',
            'description' => __("Texte bouton pages produit", 'skeerel'),
            'default' => __('Achat express avec Skeerel', 'skeerel'),
            'desc_tip' => true,
        )
    )
);
