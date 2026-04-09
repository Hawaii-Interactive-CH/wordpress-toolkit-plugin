# Post

## Introduction

The `Post` model is a base model for blog posts. It is used to retrieve information about a post.

It has the same structure as the `Page` model but with methods specific to posts.

## Usage

### categories()

To get the post's categories, use the `categories()` method.

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

To get the post's category names separated by `,`, use the `categories_name()` method.

```php
<?php

namespace Toolkit;

use Toolkit\models\Post;

$model = new Post(<post_id>);

?>

<p><?php echo $model->categories_name() ?></p>
```

### tags()

To get the post's tags, use the `tags()` method.

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

To get the post's tag names separated by `,`, use the `tags_name()` method.

```php
<?php

namespace Toolkit;

use Toolkit\models\Post;

$model = new Post(<post_id>);

?>

<p><?php echo $model->tags_name() ?></p>
```
