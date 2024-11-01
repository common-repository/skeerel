<?php

// disable direct file access
if (!defined('ABSPATH')) {

    exit;

}

?>
<main role="main" class="">
    <div class="container">
        <img src="<?php echo plugin_dir_url(__DIR__) . 'admin/images/logo.png' ?> " width="200px"
             style="margin-left:-20px; margin-top: 20px; margin-bottom:20px"/>

        <div class="card w-75" id="step1">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__('Etape 1', 'skeerel') ?></span>
                    : <?php echo esc_html__('Créer mon compte', 'skeerel') ?></h5>
                <p class="card-text"><?php echo esc_html__('Optimisez votre taux de conversion e-Commerce avec Skeerel.', 'skeerel') ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <a href="https://admin.skeerel.com/register" class="btn btn-danger btn-skeerel"
                           id="signup_btn"><?php echo esc_html__('Créer mon compte Skeerel e-commercant', 'skeerel') ?></a>&nbsp;
                    </div>
                    <div class="col-md-5">
                        <a href="#" class="btn btn-danger btn-skeerel"
                           id="login_btn"><?php echo esc_html__("J'ai déjà un compte", 'skeerel') ?></a>
                    </div>
                </div>
                <p class="text-right" id="next"
                   style="margin-right:5em; cursor: pointer; display:none"><?php echo esc_html__("Suivant ", 'skeerel') ?>
                    <i class="fas fa-arrow-right"></i></p>
            </div>
        </div>

        <div class="card w-75 wrapped-up" id="step1_completed">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-check-circle" style="font-size:25px; margin-right:10px;"></i>
                    <span style="font-weight:600"><?php echo esc_html__('Etape 1', 'skeerel') ?></span>
                    : <?php echo esc_html__('Créer mon compte', 'skeerel') ?></h5>
            </div>
        </div>

        <div class="card w-75" id="step2">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__('Etape 2', 'skeerel') ?></span>
                    : <?php echo esc_html__("Insérer les identifiants d'API", 'skeerel') ?></h5>

                <form style="margin-top:30px" action="javascript:void(0);">
                    <div class="form-group row">
                        <label for="secret_id" class="col-sm-3 col-form-label"
                               style="text-align: right;"><?php echo esc_html__('Identifiant du site', 'skeerel') ?></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="secret_id"
                                   placeholder="<?php echo esc_html__('Identifiant', 'skeerel') ?>"
                                   value="<?php echo sanitize_text_field($options['custom_skeerel_id']) ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="secret_key" class="col-sm-3 col-form-label"
                               style="text-align: right;"><?php echo esc_html__('Clé secrète', 'skeerel') ?></label>
                        <div class="col-sm-9">
                            <input type="password" data-toggle="password"
                                   data-message="<?php echo esc_html__("Cliquez ici pour afficher/masquer la clé d'API", 'skeerel') ?>"
                                   class="form-control" id="secret_key" placeholder="xxxxxxxxxxxxxxxx" value="">
                        </div>
                    </div>


                    <div class="form-group row">
                        <div class="col-sm-3"></div>
                        <div class="col-md-5">
                            <p id="error-msg" class="text-danger"
                               style="display:none; font-size: 13.5px; font-weight: 600;"></p>
                            <button type="submit" id="test_api_key"
                                    class="btn btn-danger btn-skeerel"><?php echo esc_html__("Tester la connexion", 'skeerel') ?></button>&nbsp;
                        </div>
                    </div>

                </form>

            </div>
        </div>

        <div class="card w-75 wrapped-up" id="step2_completed">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-check-circle" style="font-size:25px; margin-right:10px;"></i>
                    <span style="font-weight:600"><?php echo esc_html__("Etape 2", 'skeerel') ?></span>
                    : <?php echo esc_html__("Insérer les identifiants d'API", 'skeerel') ?></h5>
            </div>
        </div>

        <div class="card w-75 wrapped-up" id="step3_completed">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-check-circle" style="font-size:25px; margin-right:10px;"></i>
                    <span style="font-weight:600"><?php echo esc_html__("Etape 3", 'skeerel') ?></span>
                    : <?php echo esc_html__("Commencez à utiliser Skeerel", 'skeerel') ?></h5>
            </div>
        </div>

        <div id="loading" class="row w-75 justify-content-center" style="padding-top: 25px !important; display:none">
            <div class="col-xs-1">
                <div class="loader"></div>
            </div>
        </div>

        <div class="card w-75" id="step3">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__('Etape 3', 'skeerel') ?></span>
                    : <?php echo esc_html__("C'est tout bon", 'skeerel') ?></h5>
                <i class="fas fa-check-circle"></i> <?php echo esc_html__("Votre site est prêt à recevoir des paiements live.", 'skeerel') ?>
                <br>
                <i class="fas fa-check-circle"></i> <?php echo esc_html__("Votre site est correctement configuré", 'skeerel') ?>
                <br><br>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="test-mode" id="test-mode">
                    <label class="custom-control-label"
                           for="test-mode"><?php echo esc_html__("Utiliser Skeerel en mode test (transactions fictives)", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="admin-mode" id="admin-mode">
                    <label class="custom-control-label"
                           for="admin-mode"><?php echo esc_html__("Activer Skeerel uniquement pour les administrateurs", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-button" id="checkout-button"
                           checked>
                    <label class="custom-control-label"
                           for="checkout-button"><?php echo esc_html__("Activer Skeerel sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-encart-button" id="checkout-encart-button"
                           checked>
                    <label class="custom-control-label"
                           for="checkout-encart-button"><?php echo esc_html__("Activer l'encart achat en 1-clic sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox ">
                    <input type="checkbox" class="custom-control-input" name="product-button" id="product-button"
                           checked>
                    <label class="custom-control-label"
                           for="product-button"><?php echo esc_html__("Activer Skeerel sur les pages produits", 'skeerel') ?></label>
                </div>
                <br><br>
                <div class="form-group row">
                    <div class="col-md-8">
                        <button type="submit" name="finish"
                                class="btn btn-danger btn-skeerel"><?php echo esc_html__("Terminer", 'skeerel') ?></button>&nbsp;
                    </div>
                </div>

            </div>
        </div>

        <div class="card w-75" id="step3_1">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__("Etape 3", 'skeerel') ?></span>
                    : <?php echo esc_html__("C'est (presque) tout bon", 'skeerel') ?></h5>
                <i class="far fa-check-circle"></i> <?php echo esc_html__("Votre site est en cours de vérification pour pouvoir effectuer des paiements live.", 'skeerel') ?>
                <br>
                <i class="fas fa-check-circle"></i> <?php echo esc_html__("Votre site est correctement configuré", 'skeerel') ?>
                <br><br>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="test-mode" id="test-mode-1" disabled
                           checked>
                    <label class="custom-control-label"
                           for="test-mode-1"><?php echo esc_html__("Utiliser Skeerel en mode test (transactions fictives)", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="admin-mode" id="admin-mode-1">
                    <label class="custom-control-label"
                           for="admin-mode-1"><?php echo esc_html__("Activer Skeerel uniquement pour les administrateurs", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-button" id="checkout-button-1"
                           checked>
                    <label class="custom-control-label"
                           for="checkout-button-1"><?php echo esc_html__("Activer Skeerel sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-encart-button" id="checkout-encart-button"
                           checked>
                    <label class="custom-control-label"
                           for="checkout-encart-button"><?php echo esc_html__("Activer l'encart achat en 1-clic sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox ">
                    <input type="checkbox" class="custom-control-input" name="product-button" id="product-button-1"
                           checked>
                    <label class="custom-control-label"
                           for="product-button-1"><?php echo esc_html__("Activer Skeerel sur les pages produits", 'skeerel') ?></label>
                </div>
                <br><br>
                <div class="form-group row">
                    <div class="col-md-8">
                        <button type="submit" name="finish"
                                class="btn btn-danger btn-skeerel"><?php echo esc_html__("Terminer", 'skeerel') ?></button>&nbsp;
                    </div>
                </div>

            </div>
        </div>

        <div class="card w-75" id="step3_2">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__("Etape 3", 'skeerel') ?></span>
                    : <?php echo esc_html__("C'est (presque) tout bon", 'skeerel') ?></h5>
                <i class="fas fa-times-circle"></i> <?php echo esc_html__("Pour pouvoir accepter des paiements live, vous devez vous rendre sur votre espace e-commerçant Skeerel et demander la vérification de votre site.", 'skeerel') ?>
                <br>
                <i class="fas fa-check-circle"></i> <?php echo esc_html__("Votre site est correctement configuré", 'skeerel') ?>
                <br><br>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="test-mode" id="test-mode-2" disabled
                           checked>
                    <label class="custom-control-label"
                           for="test-mode-2"><?php echo esc_html__("Utiliser Skeerel en mode test (transactions fictives)", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="admin-mode" id="admin-mode-2">
                    <label class="custom-control-label"
                           for="admin-mode-2"><?php echo esc_html__("Activer Skeerel uniquement pour les administrateurs", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-button" id="checkout-button-2"
                           checked>
                    <label class="custom-control-label"
                           for="checkout-button-2"><?php echo esc_html__("Activer Skeerel sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-encart-button" id="checkout-encart-button"
                           checked>
                    <label class="custom-control-label"
                           for="checkout-encart-button"><?php echo esc_html__("Activer l'encart achat en 1-clic sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox ">
                    <input type="checkbox" class="custom-control-input" name="product-button" id="product-button-2"
                           checked>
                    <label class="custom-control-label"
                           for="product-button-2"><?php echo esc_html__("Activer Skeerel sur les pages produits", 'skeerel') ?></label>
                </div>
                <br><br>
                <div class="form-group row">
                    <div class="col-md-8">
                        <button type="submit" name="finish"
                                class="btn btn-danger btn-skeerel"><?php echo esc_html__("Terminer", 'skeerel') ?></button>&nbsp;
                    </div>
                </div>

            </div>
        </div>

        <div class="card w-75" id="step3_3">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__("Etape 3", 'skeerel') ?></span>
                    : <?php echo esc_html__("C'est (presque) tout bon", 'skeerel') ?></h5>
                <i class="far fa-clock"></i> <?php echo esc_html__("Votre site est en cours de vérification pour pouvoir effectuer des paiements live.", 'skeerel') ?>
                <br>
                <i class="fas fa-times-circle"></i>
                <div class="col-md-11" style="margin-top: -25px; margin-left: 7px;">
                    <?php echo esc_html__("Le nom de domaine de votre site n'est pas enregistré sur votre espace e-commerçant Skeerel. ", 'skeerel') ?>
                    <br><?php echo esc_html__("Pour recevoir des paiements (test ou live), vous devez l'autoriser en vous rendant sur votre espace e-commerçant Skeerel.", 'skeerel') ?>
                    <br>
                    <?php echo esc_html__("Noms de domaines actuellement autorisés: ", 'skeerel') ?> <span
                            id="authorized_domain_names"></span>
                </div>
                <br>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="test-mode" id="test-mode-3" disabled
                           checked>
                    <label class="custom-control-label"
                           for="test-mode-3"><?php echo esc_html__("Utiliser Skeerel en mode test (transactions fictives)", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="admin-mode" id="admin-mode-3">
                    <label class="custom-control-label"
                           for="admin-mode-3"><?php echo esc_html__("Activer Skeerel uniquement pour les administrateurs", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-button" id="checkout-button-3"
                           disabled>
                    <label class="custom-control-label"
                           for="checkout-button-3"><?php echo esc_html__("Activer Skeerel sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-encart-button" id="checkout-encart-button"
                           disabled>
                    <label class="custom-control-label"
                           for="checkout-encart-button"><?php echo esc_html__("Activer l'encart achat en 1-clic sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox ">
                    <input type="checkbox" class="custom-control-input" name="product-button" id="product-button-3"
                           disabled>
                    <label class="custom-control-label"
                           for="product-button-3"><?php echo esc_html__("Activer Skeerel sur les pages produits", 'skeerel') ?></label>
                </div>
                <br><br>
                <div class="form-group row">
                    <div class="col-md-8">
                        <button type="submit" name="finish"
                                class="btn btn-danger btn-skeerel"><?php echo esc_html__("Terminer", 'skeerel') ?></button>&nbsp;
                    </div>
                </div>

            </div>
        </div>

        <div class="card w-75" id="step3_4">
            <div class="card-body">
                <h5 class="card-title"><span
                            style="font-weight:600"><?php echo esc_html__("Etape 3", 'skeerel') ?></span>
                    : <?php echo esc_html__("C'est (presque) tout bon", 'skeerel') ?></h5>
                <i class="fas fa-check-circle"></i> <?php echo esc_html__("Votre site est prêt à recevoir des paiements en ligne.", 'skeerel') ?>
                <br>
                <i class="fas fa-times-circle"></i>
                <div class="col-md-11" style="margin-top: -25px; margin-left: 7px;">
                    <?php echo esc_html__("Le nom de domaine de votre site n'est pas enregistré sur votre espace e-commerçant Skeerel. ", 'skeerel') ?>
                    <br><?php echo esc_html__("Pour recevoir des paiements (test ou live), vous devez l'autoriser en vous rendant sur votre espace e-commerçant Skeerel.", 'skeerel') ?>
                    <br><?php echo esc_html__("Noms de domaines actuellement autorisés: ", 'skeerel') ?> <span
                            id="authorized_domain_names"></span>
                </div>
                <br>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="test-mode" id="test-mode-4">
                    <label class="custom-control-label"
                           for="test-mode-4"><?php echo esc_html__("Utiliser Skeerel en mode test (transactions fictives)", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="admin-mode" id="admin-mode-4">
                    <label class="custom-control-label"
                           for="admin-mode-4"><?php echo esc_html__("Activer Skeerel uniquement pour les administrateurs", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-button" id="checkout-button-4"
                           disabled>
                    <label class="custom-control-label"
                           for="checkout-button-4"><?php echo esc_html__("Activer Skeerel sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="checkout-encart-button" id="checkout-encart-button"
                           disabled>
                    <label class="custom-control-label"
                           for="checkout-encart-button"><?php echo esc_html__("Activer l'encart achat en 1-clic sur la page panier", 'skeerel') ?></label>
                </div>
                <div class="custom-control custom-checkbox ">
                    <input type="checkbox" class="custom-control-input" name="product-button" id="product-button-4"
                           disabled>
                    <label class="custom-control-label"
                           for="product-button-4"><?php echo esc_html__("Activer Skeerel sur les pages produits", 'skeerel') ?></label>
                </div>
                <br><br>
                <div class="form-group row">
                    <div class="col-md-8">
                        <button type="submit" name="finish"
                                class="btn btn-danger btn-skeerel"><?php echo esc_html__("Terminer", 'skeerel') ?></button>&nbsp;
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>
