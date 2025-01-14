<?php

namespace Toolkit\utils;

class ApiAuthService
{
    private static $master_token_option_name = 'api_master_token';
    private static $whitelist_option_name = 'api_auth_whitelist';
    private static $transient_token_name = 'api_auth_token';

    public static function register()
    {
        add_action('admin_menu', [self::class, 'admin_menu']);
        add_action('admin_post_generate_master_token', [self::class, 'generate_master_token']);
        add_action('admin_post_set_transient_expiry', [self::class, 'set_transient_expiry']);
        add_action('admin_post_add_whitelist', [self::class, 'add_to_whitelist']);
        add_action('admin_post_remove_whitelist', [self::class, 'remove_from_whitelist']);
        add_action('admin_post_generate_encryption_key', [self::class, 'generate_encryption_key']);
        add_action('admin_notices', [self::class, 'display_admin_notices']);

        // Planifier l'événement cron
        if (!wp_next_scheduled('api_auth_cleanup_expired_transients')) {
            wp_schedule_event(time(), 'hourly', 'api_auth_cleanup_expired_transients');
        }
        
        // Ajouter l'action pour le nettoyage des transients expirés
        add_action('api_auth_cleanup_expired_transients', [self::class, 'cleanup_expired_transients']);
    }

    public static function admin_menu()
    {
        add_submenu_page(
            'toolkit',
            'API Authentication',
            'API Authentication',
            'manage_options',
            'api-authentication',
            [self::class, 'display_api_authentication_page']
        );
    }

    public static function display_admin_notices()
    {
        if ($message = get_transient('api_auth_error')) {
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
            delete_transient('api_auth_error'); // Supprime le message d'erreur après l'affichage
        }
        if ($message = get_transient('api_auth_message')) {
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            delete_transient('api_auth_message'); // Supprime le message de confirmation après l'affichage
        }
    }

    public static function display_api_authentication_page()
    {
        $master_token = self::get_master_token();
        $expiry = get_option('api_transient_expiry', 10);
        $whitelist = self::get_whitelist();
        $encryption_key_defined = self::get_encryption_key();

        ?>
        <style>
            .api-auth-section {
                margin-bottom: 20px;
                padding: 20px;
                border: 1px solid #ddd;
                background-color: #f9f9f9;
                border-radius: 5px;
            }
            .api-auth-section h2 {
                margin-top: 0;
            }
            .api-auth-section p {
                margin: 10px 0;
            }
            .api-auth-section label {
                display: block;
                font-weight: bold;
            }
            .api-auth-section input[type="number"],
            .api-auth-section input[type="text"] {
                width: 100%;
                padding: 5px;
                margin: 5px 0;
            }
            .api-auth-section .button-primary {
                margin-right: 10px;
            }
        </style>

        <div class="wrap">
            <h1>API Authentication</h1>

            <div class="api-auth-section">
                <h2>Generate Encryption Key</h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('generate_encryption_key_action', 'generate_encryption_key_nonce'); ?>
                    <input type="hidden" name="action" value="generate_encryption_key">
                    <p>
                        <input type="submit" name="generate_encryption_key" class="button-primary" value="Generate Encryption Key" <?php echo $encryption_key_defined ? 'disabled' : ''; ?>>
                    </p>
                    <?php if ($encryption_key_defined) : ?>
                        <p>Encryption key is already defined.</p>
                    <?php endif; ?>
                </form>
            </div>

            <div class="api-auth-section">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('generate_master_token_action', 'generate_master_token_nonce'); ?>
                    <input type="hidden" name="action" value="generate_master_token">
                    <h2>Generate Master Token</h2>
                    <p>
                        <input type="submit" name="generate_master_token" class="button-primary" value="Generate Master Token" <?php echo !$encryption_key_defined ? 'disabled' : ''; ?>>
                    </p>
                    <?php if (!$encryption_key_defined) : ?>
                        <p class="description">Encryption key is not defined. Please generate the encryption key first.</p>
                    <?php endif; ?>
                </form>
                <p><strong>Master Token:</strong> <?php echo $master_token !== false ? esc_html($master_token) : 'No token available.'; ?></p>
            </div>


            <div class="api-auth-section">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('set_transient_expiry_action', 'set_transient_expiry_nonce'); ?>
                    <input type="hidden" name="action" value="set_transient_expiry">
                    <h2>Set Transient Token Expiry</h2>
                    <p>
                        <label for="transient_expiry">Expiry Time (in minutes):</label>
                        <input type="number" name="transient_expiry" id="transient_expiry" value="<?php echo esc_attr($expiry); ?>" min="1">
                    </p>
                    <p>
                        <input type="submit" name="set_transient_expiry" class="button-primary" value="Save Expiry Time">
                    </p>
                </form>
            </div>

            <div class="api-auth-section">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('add_whitelist_action', 'add_whitelist_nonce'); ?>
                    <input type="hidden" name="action" value="add_whitelist">
                    <h2>Whitelist IP/Domain</h2>
                    <p>
                        <label for="whitelist">IP/Domain:</label>
                        <input type="text" name="whitelist" id="whitelist">
                    </p>
                    <p>
                        <input type="submit" name="add_whitelist" class="button-primary" value="Add to Whitelist">
                    </p>
                </form>
            </div>

            <div class="api-auth-section">
                <h2>Current Settings</h2>
                <p><strong>Whitelist:</strong></p>
                <ul>
                    <?php foreach ($whitelist as $item) : ?>
                        <li>
                            <?php echo esc_html($item); ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('remove_whitelist_action', 'remove_whitelist_nonce'); ?>
                                <input type="hidden" name="action" value="remove_whitelist">
                                <input type="hidden" name="whitelist_item" value="<?php echo esc_attr($item); ?>">
                                <input type="submit" class="button-secondary" value="Remove">
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }

    /** Récupère la clé de chiffrement à partir de la configuration de WordPress. */
    private static function get_encryption_key()
    {
        return defined('ENCRYPTION_KEY');
    }

    /** Chiffre le token */
    private static function encrypt_token($token)
    {
        return openssl_encrypt($token, 'aes-256-cbc', self::get_encryption_key(), 0, substr(hash('sha256', self::get_encryption_key()), 0, 16));
    }

    /** Déchiffre le token */
    private static function decrypt_token($encrypted_token)
    {
        return openssl_decrypt($encrypted_token, 'aes-256-cbc', self::get_encryption_key(), 0, substr(hash('sha256', self::get_encryption_key()), 0, 16));
    }

    /** Génère un master token et le stocke dans les options */
    public static function generate_master_token()
    {
        if (isset($_POST['generate_master_token']) && check_admin_referer('generate_master_token_action', 'generate_master_token_nonce')) {
            $token = wp_generate_password(64, false);
            $encrypted_token = self::encrypt_token($token);
            update_option(self::$master_token_option_name, $encrypted_token);
            wp_redirect(admin_url('admin.php?page=api-authentication'));
            exit;
        }
    }

    /** Récupère le master token */
    public static function get_master_token()
    {
        $encrypted_token = get_option(self::$master_token_option_name);
        if ($encrypted_token !== false) {
            return self::decrypt_token($encrypted_token);
        }
        return false;
    }

    /** Vérifie si le token master est valide */
    public static function verify_master_token($token)
    {
        $master_token = self::get_master_token();
        return $master_token !== false && hash_equals($master_token, $token);
    }

    /** Définit la durée de vie des tokens transients */
    public static function set_transient_expiry()
    {
        if (isset($_POST['set_transient_expiry']) && check_admin_referer('set_transient_expiry_action', 'set_transient_expiry_nonce')) {
            $expiry = isset($_POST['transient_expiry']) ? intval($_POST['transient_expiry']) : 10;
            update_option('api_transient_expiry', $expiry);
            wp_redirect(admin_url('admin.php?page=api-authentication'));
            exit;
        }
    }

    /** Génère un token transient et le stocke */
    public static function generate_transient_token()
    {
        if (!session_id()) {
            session_start();
        }
        $_SESSION['transient_token_name'] = strtotime('now');
        $token = wp_generate_password(64, false);
        $expiry = get_option('api_transient_expiry', 10);
        $encrypted_token = self::encrypt_token($token);
        set_transient(self::$transient_token_name . '_' . $_SESSION['transient_token_name'], $encrypted_token, $expiry * MINUTE_IN_SECONDS);
        return $token;
    }

    /** Vérifie si le token transient est valide */
    public static function verify_token($token)
    {
        $stored_token = self::get_token();
        return $stored_token !== false && hash_equals($stored_token, $token);
    }

    /** Récupère le token stocké dans un transient et le déchiffre */
    public static function get_token()
    {
        $encrypted_token = get_transient(self::$transient_token_name . '_' . $_SESSION['transient_token_name']);
        if ($encrypted_token !== false) {
            return self::decrypt_token($encrypted_token);
        }
        return false;
    }

    /** Récupère le temps restant avant l'expiration du token transient. */
    public static function get_transient_remaining_time()
    {
        $transient_timeout = get_option('_transient_timeout_' . self::$transient_token_name . '_' . $_SESSION['transient_token_name']);
        if ($transient_timeout !== false) {
            $current_time = time();
            return $transient_timeout - $current_time;
        }
        return 0;
    }

    /** Ajoute une IP ou un domaine à la liste blanche */
    public static function add_to_whitelist()
    {
        if (isset($_POST['add_whitelist']) && check_admin_referer('add_whitelist_action', 'add_whitelist_nonce')) {
            $ip_or_domain = sanitize_text_field($_POST['whitelist']);
    
            // Vérifie si c'est une adresse IP valide (IPv4 ou IPv6)
            if (filter_var($ip_or_domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
                // Utilise une option transitoire pour stocker le message d'erreur pour une IP/Domaine invalide
                set_transient('api_auth_error', 'Invalid IP address or domain.', 30);
            } else {
                $whitelist = get_option(self::$whitelist_option_name, []);
                if (in_array($ip_or_domain, $whitelist)) {
                    // Utilise une option transitoire pour stocker le message d'erreur pour un doublon
                    set_transient('api_auth_error', 'IP address or domain already in whitelist.', 30);
                } else {
                    $whitelist[] = $ip_or_domain;
                    update_option(self::$whitelist_option_name, $whitelist);
                }
            }
    
            wp_redirect(admin_url('admin.php?page=api-authentication'));
            exit;
        }
    }
    
    /** Supprime une IP ou un domaine de la liste blanche */
    public static function remove_from_whitelist()
    {
        if (isset($_POST['action']) && $_POST['action'] === 'remove_whitelist' && check_admin_referer('remove_whitelist_action', 'remove_whitelist_nonce')) {

            $ip_or_domain = sanitize_text_field($_POST['whitelist_item']);
            $whitelist = get_option(self::$whitelist_option_name, []);
            if (($key = array_search($ip_or_domain, $whitelist)) !== false) {
                unset($whitelist[$key]);
                update_option(self::$whitelist_option_name, array_values($whitelist));
            }
            wp_redirect(admin_url('admin.php?page=api-authentication'));
            exit;
        }
    }

    /** Récupère la liste blanche */
    public static function get_whitelist()
    {
        return get_option(self::$whitelist_option_name, []);
    }

    public static function generate_encryption_key()
    {
        if (isset($_POST['generate_encryption_key']) && check_admin_referer('generate_encryption_key_action', 'generate_encryption_key_nonce')) {
            if (defined('ENCRYPTION_KEY')) {
                wp_redirect(admin_url('admin.php?page=api-authentication'));
                exit;
            }

            // Générer une clé de chiffrement unique
            $key = bin2hex(random_bytes(32));

            // Chemin vers le fichier wp-config.php
            $wp_config_file = ABSPATH . 'wp-config.php';

            // Lire le contenu du fichier wp-config.php
            $config_contents = file_get_contents($wp_config_file);

            // Ajouter la définition de la clé de chiffrement avant la ligne "define('WP_DEBUG',"
            if (strpos($config_contents, "define('ENCRYPTION_KEY',") === false) {
                $config_contents = str_replace(
                    "define('WP_DEBUG',",
                    "define('ENCRYPTION_KEY', '$key');\n\ndefine('WP_DEBUG',",
                    $config_contents
                );

                // Écrire les modifications dans le fichier wp-config.php
                file_put_contents($wp_config_file, $config_contents);

                // Message de confirmation
                set_transient('api_auth_message', 'Encryption key has been generated and added to wp-config.php.', 30);
            } else {
                set_transient('api_auth_error', 'Encryption key is already defined in wp-config.php.', 30);
            }

            wp_redirect(admin_url('admin.php?page=api-authentication'));
            exit;
        }
    }
    
    /** Nettoie les transients expirés */
    public static function cleanup_expired_transients()
    {
        global $wpdb;

        // Rechercher tous les transients ayant "api_auth_token" dans leur nom
        $transients = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name AS name FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_timeout_%api_auth_token%'
            )
        );

        $current_time = time();

        foreach ($transients as $transient) {
            $transient_name = $transient->name;
            $transient_key = str_replace('_transient_timeout_', '', $transient_name);

            // Récupérer le timeout du transient
            $timeout = get_option($transient_name);
            
            // Si le transient a expiré, le supprimer ainsi que son pair
            if ($timeout !== false && $current_time > $timeout) {
                delete_transient($transient_key);
                delete_option($transient_name);
            }
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('api_auth_cleanup_expired_transients');
    }
}