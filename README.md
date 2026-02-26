# Wordpress Toolkit Plugin

## Responsables du projet

Hawaii Interactive
<dev@hawaii.do>
+41 21 519 02 87
Dev team

## Description

Ce plugin permet de charger les fonctionnalités de base du thème.

- Activation du mode maintenance
- Creation de custom post type (CPT) et activation/désactivation de ceux-ci
- Gestion des menus wordpress
- Cookie banner
- API Authentication avec whitelist

## Documentation technique

### Prérequis WordPress

Requires at least: 5.0
Requires PHP: 8.0

### Installation

Télécharger le plugin [wordpress-toolkit-plugin](https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-plugin) en tant que zip et l'installer via l'administration de Wordpress.

## Mise à jour

Le plugin integere un système de mise à jour basé sur [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) et est lié au dépôt git du plugin sur https://github.com/Hawaii-Interactive-CH/wordpress-toolkit-plugin.

Pour mettre à jour le plugin, il faut changer la version `readme.txt` et `wordpress-toolkit-plugin.php` et pousser les changements sur le dépôt git. Le plugin detectera automatiquement les mis à jour sur les sites utilisant le plugin et proposera la mise à jour.

## Documentation

Une documentation est disponible dans l'administration de Wordpress dans le menu `Toolkit` > `Docs`.

Pour la mettre à jour, il suffit de modifier les fichiers markdown dans le dossier `docs` du plugin et pour mettre à jour la table des matières.

## Claude Code Skills

Ce projet inclut des skills [Claude Code](https://claude.ai/claude-code) pour accélérer le développement. Les skills sont des commandes slash (`/skill-name`) qui guident Claude dans la génération de code respectant les conventions du projet.

### Skills disponibles

| Commande | Description |
|---|---|
| `/create-cpt` | Génère une classe `CustomPostType` avec `JsonSerializable` et optionnellement une taxonomy de catégorie |
| `/create-taxonomy` | Génère une classe `Taxonomy` standalone avec `register()` et `jsonSerialize()` |
| `/create-block` | Génère une classe `Block` ACF et son template PHP dans `partials/blocks/` |
| `/create-service` | Génère un service statique dans `utils/` avec `register()` et les hooks WordPress |
| `/create-option-page` | Génère une classe `OptionPage` ACF avec les accesseurs de champs statiques |

### Utilisation

Dans Claude Code, invoquer la commande directement :

```
/create-cpt
```

Claude posera les questions nécessaires (nom de classe, slug, labels, icône, supports, etc.) et générera les fichiers dans `models/custom/`.

Il est aussi possible de passer des arguments directement :

```
/create-cpt Product "Products" "Product"
```

### Ajouter les skills globalement

Les skills du projet sont disponibles uniquement dans ce dépôt (via `.claude/commands/`). Pour les rendre disponibles dans tous vos projets, copiez le fichier dans votre répertoire global :

```bash
cp .claude/commands/create-cpt.md ~/.claude/commands/create-cpt.md
```

Pour les organiser en sous-dossier (invocables via `/wordpress:create-cpt`) :

```bash
mkdir -p ~/.claude/commands/wordpress
cp .claude/commands/create-cpt.md ~/.claude/commands/wordpress/create-cpt.md
```

### Ajouter un nouveau skill

Créer un fichier Markdown dans `.claude/commands/` :

```
.claude/commands/my-skill.md
```

Le fichier doit commencer par un front-matter avec une description, suivi des instructions pour Claude :

```markdown
---
description: 'Ce que fait le skill'
---

# Mon Skill

Instructions pour Claude...
```
