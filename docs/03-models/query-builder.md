# QueryBuilder

## Introduction

The `QueryBuilder` model is a base model for queries. It is used to retrieve posts with a fluent, chainable API.

## Usage

### from()

The `from` method retrieves all records of a given type. It takes the post type name and an optional callback.

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

The `where` method filters results by a condition. It takes a key, a value, and an optional callback.

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

The `paginate` method paginates the results. It takes the number of results per page and an optional callback.

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

The `search` method searches posts by a keyword. It takes a search string and an optional callback.

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

The `where_ids` method retrieves posts by a list of IDs. It takes an array of IDs and an optional callback.

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

The `order` method sorts results. It takes a key, a sort direction, and an optional callback.

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

The `meta_order` method sorts results by a meta field. It takes a meta key, a sort direction, and an optional callback.

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

The `meta_query_relation` method sets the relation between meta queries (`AND` or `OR`).

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

The `add_meta_query` method adds a meta query. It takes a key, a value, a comparison operator, and an optional callback.

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

The `tax_query_relation` method sets the relation between taxonomy queries (`AND` or `OR`).

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

The `add_tax_query` method adds a taxonomy query. It takes a taxonomy, a term, a comparison operator, and an optional callback.

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

The `after` method retrieves posts published after a given date.

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

The `before` method retrieves posts published before a given date.

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

The `add_date_filter` method adds a date filter by key and value.

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

The `find_all` method returns all matching posts as an array.

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

The `find_one` method returns the first matching post.

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

The `limit` method limits the number of results.

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

The `find_by_id` method retrieves a single post by its ID.

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

The `count_all` method counts all matching posts.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$count = QueryBuilder::from('posts')->count_all();

?>

<p><?= $count ?></p>
```

### count_displayed()

The `count_displayed` method counts all posts matching the current query parameters.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$count = QueryBuilder::from('posts')->where('post_type', 'post')->count_displayed();

?>

<p><?= $count ?></p>
```

### page_number()

The `page_number` method returns the current page number.

```php
<?php

namespace Toolkit;

use Toolkit\models\QueryBuilder;

$page_number = QueryBuilder::from('posts')->page_number();

?>

<p><?= $page_number ?></p>
```

### pagination()

The `pagination` method returns the pagination HTML.
