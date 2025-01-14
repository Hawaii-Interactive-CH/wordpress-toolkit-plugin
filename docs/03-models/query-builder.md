# QueryBuilder

## Introduction

Le model `QueryBuilder` est un modèle de base pour les requêtes. Il est utilisé pour récupérer des informations sur une requête.

## Utilisation

### from()

La méthode `from` permet de récupérer toutes les informations d'une table. Elle prend en paramètre le nom de la table et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### where()

La méthode `where` permet de récupérer toutes les informations d'une table en fonction d'une condition. Elle prend en paramètre la clé, la valeur et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->where('post_type', 'post', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### paginate()

La méthode `paginate` permet de paginer les résultats. Elle prend en paramètre le nombre de résultats par page et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->paginate(5, function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### search()

La méthode `search` permet de rechercher des informations dans une table. Elle prend en paramètre la recherche et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->search('lorem', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### where_ids()

La méthode `where_ids` permet de récupérer toutes les informations d'une table en fonction d'une liste d'identifiants. Elle prend en paramètre la liste d'identifiants et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->where_ids([1, 2, 3], function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### order()

La méthode `order` permet de trier les résultats. Elle prend en paramètre la clé, le type de tri et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->order('post_date', 'DESC', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### meta_order()

La méthode `meta_order` permet de trier les résultats en fonction des métadonnées. Elle prend en paramètre la clé, le type de tri et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->meta_order('meta_key', 'DESC', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### meta_query_relation()

La méthode `meta_query_relation` permet de définir la relation entre les requêtes de métadonnées. Elle prend en paramètre la relation et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->meta_query_relation('AND')->where('post_type', 'post', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### add_meta_query()

La méthode `add_meta_query` permet d'ajouter une requête de métadonnées. Elle prend en paramètre la clé, la valeur, le type de comparaison et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->add_meta_query('meta_key', 'meta_value', '!=', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### tax_query_relation()

La méthode `tax_query_relation` permet de définir la relation entre les requêtes de taxonomie. Elle prend en paramètre la relation et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->tax_query_relation('AND')->where('post_type', 'post', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### add_tax_query()

La méthode `add_tax_query` permet d'ajouter une requête de taxonomie. Elle prend en paramètre la clé, la valeur, le type de comparaison et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->add_tax_query('taxonomy', 'term', '!=', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### after()

La méthode `after` permet de récupérer toutes les informations d'une table après une date. Elle prend en paramètre la date et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->after('2020-01-01', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### before()

La méthode `before` permet de récupérer toutes les informations d'une table avant une date. Elle prend en paramètre la date et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->before('2020-01-01', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### add_date_filter()

La méthode `add_date_filter` permet d'ajouter un filtre de date. Elle prend en paramètre la clé, la valeur et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->add_date_filter('post_date', '2020-01-01', function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### find_all()

La méthode `find_all` permet de récupérer toutes les informations d'une table.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php foreach (QueryBuilder::from('posts')->find_all() as $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php } ?>
</ul>
```

### find_one()

La méthode `find_one` permet de récupérer une information d'une table.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$post = QueryBuilder::from('posts')->find_one();

?>

<a href="<?= $post->link() ?>">
  <?= $post->title() ?>
</a>
```

### limit()

La méthode `limit` permet de limiter le nombre de résultats. Elle prend en paramètre le nombre de résultats et une fonction de rappel.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;
use Toolkit\models\Post;

?>

<ul>
  <?php QueryBuilder::from('posts')->limit(5, function (Post $post) { ?>
    <li>
      <a href="<?= $post->link() ?>">
        <?= $post->title() ?>
      </a>
    </li>
  <?php }); ?>
</ul>
```

### find_by_id()

La méthode `find_by_id` permet de récupérer une information d'une table en fonction de son identifiant.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$post = QueryBuilder::from('posts')->find_by_id(1);

?>

<a href="<?= $post->link() ?>">
  <?= $post->title() ?>
</a>
```

### count_all()

La méthode `count_all` permet de compter toutes les informations d'une table.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$count = QueryBuilder::from('posts')->count_all();

?>

<p><?= $count ?></p>
```

### count_displayed()

La méthode `count_displayed` permet de compter toutes les informations d'une table en fonction des paramètres de requête.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$count = QueryBuilder::from('posts')->where('post_type', 'post')->count_displayed();

?>

<p><?= $count ?></p>
```

### page_number()

La méthode `page_number` permet de récupérer le numéro de la page.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$page_number = QueryBuilder::from('posts')->page_number();

?>

<p><?= $page_number ?></p>
```

### pagination()

La méthode `pagination` permet de récupérer la pagination.
