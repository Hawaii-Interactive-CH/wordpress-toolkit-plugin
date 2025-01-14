<div id="cookieConsent" class="cookie-banner">
    <div class="cookie-banner-wrapper">

        <div class="cookie-banner-wrapper-text">
            <p>
            <?php echo get_option('cookie_consent_message', "Nous n'utilisons ni ne suivons aucune donnée personnelle sur notre site. Nous utilisons des cookies uniquement pour améliorer l'expérience de l'utilisateur et pour assurer le bon fonctionnement de notre site."); ?>
            </p>
        </div>
        <div class="btn-wrapper">
            <span  id="acceptButton" class="btn button-accept" href="">
                <?php echo esc_html( get_option( 'cookie_consent_accept_button_text', "Oui" ) ); ?>
            </span>
            <span  id="refuseButton" class="btn button-refuse" href="">
                <?php echo esc_html( get_option( 'cookie_consent_refuse_button_text', "Non" ) ); ?>
            </span>
            <?php if ( get_option( 'cookie_consent_page', '' ) != '' ) { ?>
                <a id="learnMoreButton" class="btn button-learn-more" target="_blank" rel="noopener noreferrer" href="<?php echo get_permalink( get_option( 'cookie_consent_page', '' ) ); ?>">
                    <?php echo esc_html( get_option( 'cookie_consent_learn_more_button_text', "En savoir plus" ) ); ?>
                </a>
            <?php } ?>
        </div>
    </div>
</div>
