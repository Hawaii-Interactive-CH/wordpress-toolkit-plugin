# Catégories et Tags

## Créer une catégorie pour un Custom Post Type

Pour créer une catégorie pour un Custom Post Type, il suffit d'ajouter un fichier dans le dossier `models/custom` du thème.

`Tag` et `Category` sont des taxonomies et fonctionnent de la même manière.

Il est possible d'en générer un lors de la création d'un `model` via l'onglet `Toolkit > models` dans l'administration de WordPress ou en copiant et modifiant le code suivant:

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\Taxonomy;
use Toolkit\models\custom\Demo;

class DemoCategory extends Taxonomy
{
  const TYPE = 'demo_category';
  public static function register()
  {
    register_taxonomy(self::TYPE, Demo::TYPE, array(
      'hierarchical' => true,
      'show_admin_column' => true,
      'publicly_queryable' => false,
      'show_in_rest' => true,
      'labels' =>
      array(
        'name' => 'Catégories',
        'singular_name' => 'Catégorie',
        'search_items' => 'Rechercher une catégorie',
        'all_items' => 'Tout les catégories',
        'parent_item' => 'Catégorie parente',
        'parent_item_colon' => 'Catégorie parente:',
        'edit_item' => 'Éditer la catégorie',
        'update_item' => 'Modifier la catégorie',
        'add_new_item' => 'Ajouter une nouvelle catégorie',
        'new_item_name' => 'Nouvelle catégorie',
        'menu_name' => 'Catégories',
      ),
    ));
  }
}
```

## Utilisation

### all()

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

?>

<ul>
  <?php DemoCategory::all(function (DemoCategory $category) { ?>
    <li>
      <a href="<?= $category->link() ?>">
        <?= $category->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### all_by_type()

La méthode `all_by_type` permet de récupérer toutes les catégories d'un type spécifique.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

?>

<ul>
  <?php DemoCategory::all_by_type('demo', function (DemoCategory $category) { ?>
    <li>
      <a href="<?= $category->link() ?>">
        <?= $category->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>

```

### current()

Le model `current` permet de récupérer la catégorie courante dans une boucle WordPress.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

?>

<?php DemoCategory::current(function (DemoCategory $category) { ?>
  <h1><?= $category->title() ?></h1>
<?php }); ?>
```

### id()

Renvoie l'ID de la catégorie.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->id() ?></p>
```

### slug()

Renvoie le slug de la catégorie.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->slug() ?></p>
```

### title()

Renovie le titre de la catégorie.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->title() ?></p>
```

### link()

Renvoie le lien vers la catégorie.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<a href="<?= $category->link() ?>">Lien vers la catégorie</a>
```

### find_by_id()

Trouve une catégorie par son ID.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>
```

### find_by_slug()

Trouve une catégorie par son slug.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_slug('slug_of_category');

?>
```

### acf()

Renvoie la valeur d'un champ ACF.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->acf('field_name') ?></p>
```

### jsonSerialize()

Renvoie un tableau associatif contenant les données du modèle sous forme de JSON.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<?= json_encode($category) ?>
```
