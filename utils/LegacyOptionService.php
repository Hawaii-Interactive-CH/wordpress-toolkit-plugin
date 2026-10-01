<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

/**
 * Backward compatibility with legacy (unprefixed) option names.
 *
 * The plugin now stores these options under prefixed names. Themes that still
 * call get_option() / update_option() with the legacy names are transparently
 * redirected to the prefixed options, and existing values are migrated once.
 */
class LegacyOptionService
{
    /**
     * Legacy option name => prefixed option name.
     */
    const OPTIONS = [
        'file_size'        => 'hithto_file_size',
        'maintenance_mode' => 'hithto_maintenance_mode',
    ];

    /**
     * Legacy option name => prefixed option name, migrated once without read/write
     * redirection: no theme uses them, and redirecting a name as generic as
     * "calendar" could hijack another plugin's option.
     */
    const MIGRATE_ONLY_OPTIONS = [
        'calendar'            => 'hithto_calendar',
        'fly_images_queue'    => 'hithto_images_queue',
        'fly_images_webp_log' => 'hithto_webp_log',
    ];

    const LEGACY_MEDIA_TAXONOMY = 'media_category';

    /**
     * Option storing the version of the one-time migrations already run.
     */
    const MIGRATION_VERSION_OPTION = 'hithto_legacy_migration_version';
    const MIGRATION_VERSION        = 2;

    /**
     * Cron hooks renamed with the hithto prefix: the old scheduled events are removed,
     * the new ones are scheduled by the plugin on the next load.
     */
    const LEGACY_CRON_HOOKS = [
        'fly_images_process_queue',
    ];

    public static function register()
    {
        self::migrate();

        if ((int) get_option(self::MIGRATION_VERSION_OPTION, 0) < self::MIGRATION_VERSION) {
            self::migrate_prefixed_names();
            update_option(self::MIGRATION_VERSION_OPTION, self::MIGRATION_VERSION);
        }

        foreach (self::OPTIONS as $legacy_name => $option_name) {
            // Reads of the legacy name return the prefixed option.
            add_filter("pre_option_{$legacy_name}", function () use ($option_name) {
                return get_option($option_name, false);
            });

            // Writes to the legacy name go to the prefixed option; returning the
            // old value makes WordPress skip writing the legacy option itself.
            add_filter("pre_update_option_{$legacy_name}", function ($value, $old_value) use ($option_name) {
                update_option($option_name, $value);
                return $old_value;
            }, 10, 2);
        }
    }

    /**
     * Copies values saved under the legacy names to the prefixed ones, then
     * deletes the legacy rows. Does nothing once the legacy rows are gone.
     */
    public static function migrate()
    {
        foreach (self::OPTIONS as $legacy_name => $option_name) {
            $legacy_value = get_option($legacy_name, null);
            if (null === $legacy_value) {
                continue;
            }

            if (null === get_option($option_name, null)) {
                update_option($option_name, $legacy_value);
            }
            delete_option($legacy_name);
        }
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
                update_option($option_name, $legacy_value, 'calendar' === $legacy_name);
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
        if ('calendar' === $legacy_name) {
            return in_array((string) $value, ['0', '1'], true);
        }

        // fly_images_queue and fly_images_webp_log are always stored as arrays
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
