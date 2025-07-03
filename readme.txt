=== Wordpress Toolkit Plugin ===
Contributors: Hawaii Interactive
Tags: toolkit
Requires at least: 5.0
Tested up to: 6.4.3
Requires PHP: 8.0
License: GPL v2 or later

Wordpress Toolkit est un plugin qui fournit des fonctions utiles pour le développement de thèmes Wordpress.

== Description ==
Ce plugin permet de charger les fonctionnalités de base du thème.

- Activation du mode maintenance
- Creation de custom post type (CPT) et activation/désactivation de ceux-ci
- Creation de block gutenberg et activation/désactivation de ceux-ci
- Gestion des menus wordpress

== Installation ==
Télécharger le plugin [wordpress-toolkit-plugin](https://git.hawai.li/hawai-li/wordpress-toolkit-plugin) en tant que zip et l'installer via l'administration de Wordpress.

== Changelog ==

= 1.8.0 =
- Add support of wp-i18n in js script

= 1.7.3 =
- Fix cpt creation

= 1.7.2 =
- Fix url assets path for block.css

= 1.7.1 =
- Add blocks.css to see custom block style in admin

= 1.7.0 =
- Add category to media files

= 1.6.6 =
- Remove default custom CPT to keep only the one defined in the theme

= 1.6.5 =
- Fix typo

= 1.6.4 =
- Change privacy to make plugin public on github

= 1.6.3 =
- Fix svg size processing

= 1.6.2 =
- Fix svg size processing

= 1.6.1 =
- Fix svg size processing
- Remove deprectaed null log

= 1.6.0 =
- Feat Add webp support for image upload
- Feat Add menu service to manage menus programmatically

= 1.5.1 =
- Fix get template

= 1.5.0 =
- Feat Add authAPI

= 1.4.1 =
- Fix GFroms notifications builder

= 1.4.0 =
- Feat  Allow max upload size update from admin

= 1.3.5 =
- Update GForms fields, added select, radio, checkbox format

= 1.3.4 =
- Update GForms api url

= 1.3.2 =
- Update Gforms api key register

= 1.3.0 =
- Update cookie consent banner message

= 1.2.10 =
- Fix missing class import check && return value of jsonSerialize

= 1.2.9 =
- Update GForms: allow null value for forms selection

= 1.2.8 =
- Enable title tag support

= 1.2.7 =
- Update GForms class & fix register class

= 1.2.6 =
- Rename script avoid conflict with other plugin

= 1.2.5 =
- Fix block acf fields if not installed

= 1.2.4 =
- Fix block generator title format

= 1.2.3 =
- Fix conflict

= 1.2.2 =
- Fix conflict

= 1.2.1 =
- Fix archive templates rendering

= 1.2.0 =
- Add Cookie Consent & banner

= 1.1.0 =
- Event model update
- Default ACF fields for event model

= 1.0.8 =
- Feat: Update plugin update checker to 5.4

= 1.0.7 =
- Fix: Gallery model instance musnt be AbstractModel

= 1.0.6 =
- Add local documentation genrated from local markdown files inside docs folder
- Better local vite detectection, avoid to use vite in production && staging environment
- Fix typo on custom post type generator file name

= 1.0.5 =
- Update vite assets compilation to match staging and production environment
- New way to load fonts from static folder

= 1.0.4 =
- Dev host update to 0.0.0.0

= 1.0.3 =
- Test with Wordpress 6.4.3
- Ajout du generateur de categorie et de block

= 1.0.2 =
- Update API key for plugin-update-checker
- Better readme.txt

= 1.0.1 =
- Fixed some bugs about namespace.

= 1.0.0 =
- Initial release.

== Upgrade Notice ==
Le plugin integere un système de mise à jour basé sur [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) et est lié au dépôt git du plugin sur https://git.hawai.li/hawai-li/wordpress-toolkit-plugin.

Pour mettre à jour le plugin, il faut changer la version `readme.txt` et `wordpress-toolkit-plugin.php` et pousser les changements sur le dépôt git. Le plugin detectera automatiquement les mis à jour sur les sites utilisant le plugin et proposera la mise à jour.