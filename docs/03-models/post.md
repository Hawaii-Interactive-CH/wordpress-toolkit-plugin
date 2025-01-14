# Post

## Introduction

Le model `Post` est un modèle de base pour les articles. Il est utilisé pour recuperer des informations sur un article.

Il a la même structure que le model `Page` mais avec des méthodes spécifiques pour les articles.

## Utilisation

### categories()

Pour avoir les catégories de l'article il suffit d'utiliser la méthode `categories()`.

```php
<?php

namespace Toolkit;

use Toolkit\models\Post;

$model = new Post(<post_id>);
?>

<ul>
    <?php foreach ($model->categories() as $category): ?>
        <li id="category-<?= $category->id() ?>" ><?= $category->title() ?></li>
    <?php endforeach; ?>
</ul>
```

### categories_name()

Pour avoir les noms des catégories de l'article séparé par une `,` il suffit d'utiliser la méthode `categories_name()`.

```php
<?php

namespace Toolkit;

use Toolkit\models\Post;

$model = new Post(<post_id>);

?>

<p><?php echo $model->categories_name() ?></p>
```

### tags()

Pour avoir les tags de l'article il suffit d'utiliser la méthode `tags()`.

```php
<?php

namespace Toolkit;

use Toolkit\models\Post;

$model = new Post(<post_id>);

?>

<ul>
    <?php foreach ($model->tags() as $tag): ?>
        <li id="tag-<?= $tag->id() ?>" ><?= $tag->title() ?></li>
    <?php endforeach; ?>
</ul>
```

### tags_name()

Pour avoir les noms des tags séparé par une `,` de l'article il suffit d'utiliser la méthode `tags_name()`.

```php
<?php

namespace Toolkit;

use Toolkit\models\Post;

$model = new Post(<post_id>);

?>

<p><?php echo $model->tags_name() ?></p>
```
