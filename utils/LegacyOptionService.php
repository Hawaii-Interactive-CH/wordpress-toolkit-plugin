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

    public static function register()
    {
        self::migrate();

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
}
