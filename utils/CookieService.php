<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

class CookieService
{
    private static function get_allowed_script_hosts()
    {
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        $hosts = [];

        if (is_string($host) && '' !== $host) {
            $hosts[] = strtolower($host);
        }

        /**
         * Filter allowed hosts for cookie-consent injected external scripts.
         *
         * @param array $hosts
         */
        $hosts = apply_filters('toolkit_cookie_consent_allowed_script_hosts', $hosts);

        if (!is_array($hosts)) {
            return [];
        }

        $hosts = array_map(function ($value) {
            return is_string($value) ? strtolower(trim($value)) : '';
        }, $hosts);

        return array_values(array_filter(array_unique($hosts)));
    }

    public static function register()
    {
        add_action('admin_menu', function () {
            add_menu_page(
                'Cookie & Privacy',
                'Cookie & Privacy',
                'publish_pages',
                'toolkit_cookie_settings',
                [self::class, 'display_settings_page'],
                'dashicons-icon-security',
                2
            );
        });
        add_action('admin_init', function () {
            // Register settings
            register_setting('cookie_consent_plugin_settings', 'cookie_consent_message', [
                'sanitize_callback' => 'wp_kses_post',
            ]);
            register_setting('cookie_consent_plugin_settings', 'cookie_consent_accept_button_text', [
                'sanitize_callback' => 'sanitize_text_field',
            ]);
            register_setting('cookie_consent_plugin_settings', 'cookie_consent_refuse_button_text', [
                'sanitize_callback' => 'sanitize_text_field',
            ]);
            register_setting('cookie_consent_plugin_settings', 'cookie_consent_page', [
                'sanitize_callback' => 'absint',
            ]);
            register_setting('cookie_consent_plugin_settings', 'cookie_consent_learn_more_button_text', [
                'sanitize_callback' => 'sanitize_text_field',
            ]);
            register_setting('cookie_consent_plugin_settings', 'cookie_consent_additional_data', [
                'sanitize_callback' => [self::class, 'sanitize_additional_data'],
            ]);
        });
        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_script('aloha-cookie-consent-script', WP_TOOLKIT_URL . 'admin/assets/js/toolkit-cookie-consent.js', array('jquery'), WP_TOOLKIT_VERSION, true);
            wp_enqueue_style('aloha-cookie-consent-style', WP_TOOLKIT_URL . 'admin/assets/css/toolkit-cookie-consent.css', [], WP_TOOLKIT_VERSION);
            $inline = 'localStorage.setItem("cookieConsentAdditionalData", ' . wp_json_encode(get_option('cookie_consent_additional_data', '')) . ');'
                . 'localStorage.setItem("cookieConsentAllowedScriptHosts", ' . wp_json_encode(self::get_allowed_script_hosts()) . ');';
            wp_add_inline_script('aloha-cookie-consent-script', $inline, 'before');
        });

        add_action('wp_footer', function () {
            self::banner();
        });
    }

    public static function banner()
    {
        ob_start();
        include( WP_TOOLKIT_DIR . '/views/banner.php' );
        $banner = ob_get_clean();
        echo wp_kses_post( $banner );

    }

    public static function display_settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Aloha</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('cookie_consent_plugin_settings');
                do_settings_sections('cookie_consent_plugin_settings');
                ?>

                <h2>Configuration</h2>
                <label for="cookie_consent_message">Message:</label><br>
                <textarea cols="80" rows="10" id="cookie_consent_message" name="cookie_consent_message"><?php echo esc_textarea(get_option('cookie_consent_message', "Nous n'utilisons ni ne suivons aucune donnée personnelle sur notre site. Nous utilisons des cookies uniquement pour améliorer l'expérience de l'utilisateur et pour assurer le bon fonctionnement de notre site.")); ?></textarea>

                <br><br>
                <label for="cookie_consent_accept_button_text">Texte du bouton d'acceptation :</label>
                <input type="text" id="cookie_consent_accept_button_text" name="cookie_consent_accept_button_text" value="<?php echo esc_attr(get_option('cookie_consent_accept_button_text', "Oui")); ?>">

                <br><br>
                <label for="cookie_consent_refuse_button_text">Texte du bouton de refus :</label>
                <input type="text" id="cookie_consent_refuse_button_text" name="cookie_consent_refuse_button_text" value="<?php echo esc_attr(get_option('cookie_consent_refuse_button_text', "Non")); ?>">

                <br><br>
                <label for="cookie_consent_page">Page de politique de confidentialité :</label>
                <!-- Page select -->
                <?php
                    $args = array(
                        'name'             => 'cookie_consent_page',
                        'id'               => 'cookie_consent_page',
                        'show_option_none' => 'Select a page',
                        'option_none_value' => '',
                        'selected'         => esc_attr(get_option('cookie_consent_page', '')),
                    );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_dropdown_pages() is a trusted WP core function that handles its own escaping
                    wp_dropdown_pages($args);
                ?>

                <br><br>
                <label for="cookie_consent_learn_more_button_text">Texte du bouton En savoir plus :</label>
                <input type="text" id="cookie_consent_learn_more_button_text" name="cookie_consent_learn_more_button_text" value="<?php echo esc_attr(get_option('cookie_consent_learn_more_button_text', "En savoir plus")); ?>">


                <br><br>
                <h2>Head</h2>
                <label for="cookie_consent_additional_data">Code nécessitant le consentement:</label><br>
                <textarea cols="80" rows="10" id="cookie_consent_additional_data" name="cookie_consent_additional_data">
                    <?php echo esc_textarea(get_option('cookie_consent_additional_data', '')); ?>
                </textarea>
                <?php


                submit_button('Sauvegarder'); ?>
            </form>
        </div>
<?php

    }

    public static function sanitize_additional_data($value)
    {
        return wp_kses($value, [
            'script' => [
                'src' => true,
                'type' => true,
                'async' => true,
                'defer' => true,
                'id' => true,
                'data-*' => true,
            ],
            'noscript' => [],
            'iframe' => [
                'src' => true,
                'width' => true,
                'height' => true,
                'style' => true,
                'title' => true,
                'loading' => true,
                'allow' => true,
                'allowfullscreen' => true,
                'referrerpolicy' => true,
            ],
            'img' => [
                'src' => true,
                'alt' => true,
                'width' => true,
                'height' => true,
                'style' => true,
            ],
        ]);
    }
}
