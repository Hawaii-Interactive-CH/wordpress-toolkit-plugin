# Utilisation du plugin

Ce plugin permet de charger les fonctionnalités de base d'un thème WordPress.

- [x] Activation du mode maintenance
- [x] Creation de custom post type (CPT) et activation/désactivation de ceux-ci
- [x] Creation de block gutenberg
- [x] Gestion des menus wordpress (Masqué dans le menu d'administration)
- [ ] Cookie banner (TODO)
- [ ] Plus de CPT par défaut (ex: FAQ, Team, ...) (TODO)

## Constantes disponibles

```php
// Chemin absolu du plugin
define( 'WP_TOOLKIT_DIR', plugin_dir_path(__FILE__) );

// URL du plugin
define( 'WP_TOOLKIT_URL', plugin_dir_url(__FILE__) );

// Chemin absolu du thème
define( 'WP_TOOLKIT_THEME_PATH', get_template_directory() );

// URL du thème
define( 'WP_TOOLKIT_THEME_URL', get_template_directory_uri() );

// Chemin absolu des vues.php du thème
define( 'WP_TOOLKIT_THEME_VIEWS_PATH', get_template_directory() . '/templates' );
```

## Intelephense (VSCode)

Pour que Intelephense fonctionne correctement, il est nécessaire de déclarer le nom de son user dans le fichier `autoload.js` du thème.

Ce script permet de charger les classes et les fonctions du thème dans Intelephense.
