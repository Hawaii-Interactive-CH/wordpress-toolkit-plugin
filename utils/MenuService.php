<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

class MenuService
{
    public static function register()
    {
        add_action('init', [self::class, 'create_menus'], 0);
        add_action('after_setup_theme', [self::class, 'register_menus']);
    }

    public static function default_menus()
    {
        return [
            "main_menu" => "Menu principal",
            "footer_menu" => "Menu pied de page",
        ];
    }

    public static function register_menus()
    {
        register_nav_menus(self::default_menus());
    }

    public static function create_menus()
    {
        $default_menus = self::default_menus();
        $locations = [];

        foreach ($default_menus as $location => $name) {
            // Try to find menu by location first
            $menu_exists = wp_get_nav_menu_object($location);
            
            if (!$menu_exists) {
                // If not found, create new menu using the location as the name
                $menu_id = wp_create_nav_menu($location);
                
                if (!is_wp_error($menu_id)) {
                    $locations[$location] = $menu_id;
                }
            } else {
                $locations[$location] = $menu_exists->term_id;
            }
        }

        if (!empty($locations)) {
            set_theme_mod('nav_menu_locations', $locations);
        }
    }
}