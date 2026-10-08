<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

/**
 * Backward compatibility with legacy (unprefixed) option names.
 *
 * The plugin now stores these options under prefixed names. Values saved under
 * the legacy names are migrated once per site, only when they have the format
 * this plugin stores, so options of other plugins using the same generic names
 * are never read, overwritten or deleted.
 */
class LegacyOptionService
{
    /**
     * Legacy option name => prefixed option name, migrated once (no read/write
     * redirection: redirecting generic names could hijack another plugin's option).
     */
    const MIGRATE_ONLY_OPTIONS = [
        'file_size'           => 'hithto_file_size',
        'maintenance_mode'    => 'hithto_maintenance_mode',
        'calendar'            => 'hithto_calendar',
        'fly_images_queue'    => 'hithto_images_queue',
        'fly_images_webp_log' => 'hithto_webp_log',
    ];

    /**
     * Small settings read on every request, stored with autoload.
     */
    const AUTOLOADED_OPTIONS = [
        'file_size',
        'maintenance_mode',
        'calendar',
    ];

    const LEGACY_MEDIA_TAXONOMY = 'media_category';

    /**
     * Option storing the version of the one-time migrations already run.
     */
    const MIGRATION_VERSION_OPTION = 'hithto_legacy_migration_version';
    const MIGRATION_VERSION        = 3;

    /**
     * Cron hooks renamed with the hithto prefix: the old scheduled events are removed,
     * the new ones are scheduled by the plugin on the next load.
     */
    const LEGACY_CRON_HOOKS = [
        'fly_images_process_queue',
    ];

    /**
     * Options with names specific to this plugin: if one of them exists,
     * the plugin was already installed on this site.
     */
    const INSTALL_MARKER_OPTIONS = [
        'hithto_file_size',
        'hithto_maintenance_mode',
        'hithto_menu_settings',
        'toolkit_enabled_models',
        'hithto_calendar',
        'toolkit_calendar_settings',
        'hithto_api_encryption_key',
        'hithto_legacy_migration_version',
    ];

    /**
     * Generic option names used by previous versions of the plugin: they only count
     * as install markers when their value has the format this plugin stores.
     */
    const GENERIC_INSTALL_MARKER_OPTIONS = [
        'file_size',
        'maintenance_mode',
        'calendar',
        'cookie_consent',
        'custom_menu_settings',
    ];

    /**
     * Features that used to be always on and are now opt-in settings: they stay
     * enabled on sites where the plugin was already installed, off on new installs.
     */
    const FORMERLY_DEFAULT_FEATURES = [
        'hithto_disable_comments',
        'hithto_allow_svg_upload',
        'hithto_limit_upload_size',
    ];

    public static function register()
    {
        // Checked before any migration, as migrations create options themselves
        $is_existing_install = self::is_existing_install();

        if ((int) get_option(self::MIGRATION_VERSION_OPTION, 0) < self::MIGRATION_VERSION) {
            self::migrate_prefixed_names();
            update_option(self::MIGRATION_VERSION_OPTION, self::MIGRATION_VERSION);
        }

        // Set once per site: the option exists afterwards, whatever its value
        foreach (self::FORMERLY_DEFAULT_FEATURES as $feature_option) {
            if (null === get_option($feature_option, null)) {
                update_option($feature_option, $is_existing_install ? 1 : 0);
            }
        }
    }

    /**
     * @return bool True if a previous version of the plugin was installed on this site
     */
    private static function is_existing_install()
    {
        foreach (self::INSTALL_MARKER_OPTIONS as $option) {
            if (null !== get_option($option, null)) {
                return true;
            }
        }

        foreach (self::GENERIC_INSTALL_MARKER_OPTIONS as $option) {
            $value = get_option($option, null);
            if (null !== $value && self::is_own_legacy_value($option, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * One-time migration of the options and taxonomy renamed with the hithto prefix.
     */
    public static function migrate_prefixed_names()
    {
        foreach (self::MIGRATE_ONLY_OPTIONS as $legacy_name => $option_name) {
            $legacy_value = get_option($legacy_name, null);

            // Only move values in the format this plugin stores, so an option with the
            // same generic name belonging to another plugin is left untouched
            if (null === $legacy_value || !self::is_own_legacy_value($legacy_name, $legacy_value)) {
                continue;
            }

            if (null === get_option($option_name, null)) {
                update_option($option_name, $legacy_value, in_array($legacy_name, self::AUTOLOADED_OPTIONS, true));
            }
            delete_option($legacy_name);
        }

        self::migrate_media_taxonomy();

        foreach (self::LEGACY_CRON_HOOKS as $legacy_hook) {
            wp_clear_scheduled_hook($legacy_hook);
        }
    }

    /**
     * @param string $legacy_name
     * @param mixed $value
     * @return bool
     */
    private static function is_own_legacy_value($legacy_name, $value)
    {
        // On/off settings stored as 0 or 1
        if (in_array($legacy_name, ['calendar', 'maintenance_mode', 'cookie_consent'], true)) {
            return is_scalar($value) && in_array((string) $value, ['0', '1'], true);
        }

        // Upload limit stored as a positive number of bytes
        if ('file_size' === $legacy_name) {
            return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0;
        }

        // fly_images_queue, fly_images_webp_log and custom_menu_settings are always stored as arrays
        return is_array($value);
    }

    /**
     * Renames the media category taxonomy in the database, keeping terms,
     * their hierarchy, their attachments and the nav menu items using them.
     */
    private static function migrate_media_taxonomy()
    {
        global $wpdb;

        $legacy_taxonomy = self::LEGACY_MEDIA_TAXONOMY;
        $taxonomy        = \Toolkit\models\MediaTaxonomy::TYPE;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time migration
        $term_ids = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", $legacy_taxonomy));
        if (empty($term_ids)) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-time migration
        $wpdb->update($wpdb->term_taxonomy, ['taxonomy' => $taxonomy], ['taxonomy' => $legacy_taxonomy]);

        // Nav menu items pointing to these terms
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery -- one-time migration
        $wpdb->update($wpdb->postmeta, ['meta_value' => $taxonomy], ['meta_key' => '_menu_item_object', 'meta_value' => $legacy_taxonomy]);

        // Hierarchy cache is rebuilt by WordPress under the new name
        delete_option($legacy_taxonomy . '_children');
        delete_option($taxonomy . '_children');

        clean_term_cache(array_map('intval', $term_ids), '', false);
        wp_cache_delete('last_changed', 'terms');
    }
}
