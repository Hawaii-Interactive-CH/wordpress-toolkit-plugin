# Systeme de Namespace entre le plugin et le thème

## Introduction

Le système de namespace permet de définir des chemins de fichiers pour les classes, les fonctions et les constantes. Cela permet de mieux organiser le code et de le rendre plus lisible.

Afin d'avoir un acces aux classes du plugin dans le thème, il est nécessaire d'utiliser le même système de namespace qui est `Toolkit`.

De base les fichiers seront d'abord chargés depuis le plugin, puis depuis le thème si le fichier existe dans ce dernier.

Cela permet de surcharger les classes du plugin dans le thème.

## Configuration

### 1. Ajouter le namespace dans le haut du fichier

```php
<?php

namespace Toolkit;

class Post {
    // ...
}
```

### 2. Utiliser la classe dans le thème

```php
namespace Toolkit;

use Toolkit\Post;

$post = new Post();
```

Nous pouvons remarquer que le namespace est le même que celui du plugin. En général, il est recommandé de nommer selon la structure du dossier du plugin ou du thème.

Example:

Dans le cas ou un fichier se trouve dans le dossier `inc` du plugin, le namespace sera `Toolkit\Inc`.

## Structure de base

Voici la structure de base du plugin et du thème:

### Utils

```md
- Toolkit\utils\AssetService
- Toolkit\utils\DocService
- Toolkit\utils\GravityForm
- Toolkit\utils\MainService
- Toolkit\utils\ModelService
- Toolkit\utils\Navigation
- Toolkit\utils\PDFService
- Toolkit\utils\RegisterService
- Toolkit\utils\ShareLinks
- Toolkit\utils\Size
- Toolkit\utils\Slugify
- Toolkit\utils\Updscale
- Toolkit\utils\WPML
```

### Models

Les models sont des classes qui permettent de gérer les custom post type et les custom taxonomy.

Afin de pouvoir les surcharger dans le thème, il est nécessaire de les modifiers dans le dossier `models` du thème. Les Abstracts du dans le dossoer `models` du plugin (Ex: `AbstractPost` ...) sont des classes qui permettent de définir des méthodes par défaut pour les models et de les surcharger dans le thème (Ex: `Post` ...) si besoin.

Concernant les custom post type propre au thème, il est recommandé de les placer dans le dossier `models/custom` du thème ou d'utiliser le générateur intégré au plugin.

Par default les `models` sont:

```md
- Toolkit\models\AbstractCategory
- Toolkit\models\AbstractFile
- Toolkit\models\AbstractGallery
- Toolkit\models\AbstractMedia
- Toolkit\models\AbstractPage
- Toolkit\models\AbstractPost
- Toolkit\models\AbstractSearch
- Toolkit\models\AbstractTag
- Toolkit\models\Block
- Toolkit\models\CustomPostType
- Toolkit\models\OptionPage
- Toolkit\models\PostType
- Toolkit\models\QueryBuilder
- Toolkit\models\Taxonomy
```
