# Wordpress Toolkit Plugin

## Responsables du projet

Hawaii Interactive
<dev@hawaii.do>
+41 21 519 02 87
Backend team & DevOps

## Description

Ce plugin permet de charger les fonctionnalités de base du thème.

- Activation du mode maintenance
- Creation de custom post type (CPT) et activation/désactivation de ceux-ci
- Gestion des menus wordpress

## Documentation technique

### Prérequis WordPress

Requires at least: 5.0
Requires PHP: 8.0

### Installation

Télécharger le plugin [wordpress-toolkit-plugin](https://git.hawai.li/hawai-li/wordpress-toolkit-plugin) en tant que zip et l'installer via l'administration de Wordpress.

## Mise à jour

Le plugin integere un système de mise à jour basé sur [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) et est lié au dépôt git du plugin sur https://git.hawai.li/hawai-li/wordpress-toolkit-plugin.

Pour mettre à jour le plugin, il faut changer la version `readme.txt` et `wordpress-toolkit-plugin.php` et pousser les changements sur le dépôt git. Le plugin detectera automatiquement les mis à jour sur les sites utilisant le plugin et proposera la mise à jour.

## Documentation

Une documentation est disponible dans l'administration de Wordpress dans le menu `Toolkit` > `Docs`.

Pour la mettre à jour, il suffit de modifier les fichiers markdown dans le dossier `docs` du plugin et pour mettre à jour la table des matières.

## TODO

- [ ] Intégraton du cookie banner
- [ ] Plus de CPT par défaut (ex: FAQ, Team, ...)
