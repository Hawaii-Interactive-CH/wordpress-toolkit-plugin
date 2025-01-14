# Custom Block

## Créer un Custom Block

Pour créer un `Block`, il suffit d'ajouter un fichier dans le dossier `models/custom` du thème et de créer son partial dans le dossier `partials/blocks` avec le même nom que le `TYPE`.

Il est possible d'en générer un via l'onglet `Toolkit > models` et click sur le tab `Block` dans l'administration de WordPress ou en copiant et modifiant le code suivant:

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\Block;

class BlockDemo extends Block
{
  const TYPE = 'block-demo';

  public static function settings()
  {
    return array(
      'title' => 'Demo',
      'mode' => 'auto',
      'description' => 'Demo',
      'icon' => 'block-default',
      'keywords' =>
      array(
        0 => 'section',
        1 => 'hi-block',
      ),
    );
  }
}
```

## Utilisation

Les `Blocks` sont des éléments de contenu qui peuvent être ajoutés dans les `Posts` et les `Pages` depuis l'éditeur de WordPress. Ils aggissent comme des `Gutenberg Blocks` et peuvent être ajoutés, modifiés et supprimés dans le contenu du `Post` ou de la `Page`.
