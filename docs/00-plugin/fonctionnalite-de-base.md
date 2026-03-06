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

## Personnaliser la page de connexion — `MainService::customize_login()`

Permet de remplacer le logo, la couleur de fond et la couleur du bouton de la page `/wp-login.php`.

### Options

| Clé | Type | Description |
|---|---|---|
| `logo` | `string` | URL de l'image de logo (remplace le logo WordPress) |
| `background` | `string` | Couleur de fond CSS (ex. `'#f5f5f5'`) |
| `button_color` | `string` | Couleur CSS du bouton Submit |
| `button_hover` | `string` | *(optionnel)* Couleur CSS au survol — par défaut égale à `button_color` |

### Exemple

```php
use Toolkit\utils\MainService;

MainService::customize_login([
    'logo'         => get_template_directory_uri() . '/assets/images/logo.svg',
    'background'   => '#f5f5f5',
    'button_color' => '#e63946',
    'button_hover' => '#c1121f',
]);
```

À appeler depuis le fichier `functions.php` du thème ou dans un hook `init`.

---

## Intelephense (VSCode)

Pour que Intelephense fonctionne correctement, il est nécessaire de déclarer le nom de son user dans le fichier `autoload.js` du thème.

Ce script permet de charger les classes et les fonctions du thème dans Intelephense.
