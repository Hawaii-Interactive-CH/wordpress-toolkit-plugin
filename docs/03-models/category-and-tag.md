# Categories and Tags

## Creating a Category for a Custom Post Type

To create a category for a Custom Post Type, add a file in the theme's `models/custom` folder.

`Tag` and `Category` are taxonomies and work the same way.

You can generate one when creating a `model` via the `Toolkit > Models` tab in the WordPress admin, or by copying and modifying the following code:

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
        'name' => 'Categories',
        'singular_name' => 'Category',
        'search_items' => 'Search categories',
        'all_items' => 'All categories',
        'parent_item' => 'Parent category',
        'parent_item_colon' => 'Parent category:',
        'edit_item' => 'Edit category',
        'update_item' => 'Update category',
        'add_new_item' => 'Add new category',
        'new_item_name' => 'New category',
        'menu_name' => 'Categories',
      ),
    ));
  }
}
```

## Usage

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

The `all_by_type` method retrieves all categories of a specific type.

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

The `current` method retrieves the current category in a WordPress loop.

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

Returns the category ID.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->id() ?></p>
```

### slug()

Returns the category slug.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->slug() ?></p>
```

### title()

Returns the category title.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->title() ?></p>
```

### link()

Returns the link to the category.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<a href="<?= $category->link() ?>">Link to category</a>
```

### find_by_id()

Finds a category by its ID.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>
```

### find_by_slug()

Finds a category by its slug.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_slug('slug_of_category');

?>
```

### acf()

Returns the value of an ACF field.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<p><?= $category->acf('field_name') ?></p>
```

### jsonSerialize()

Returns an associative array containing the model data as JSON.

```php
<?php

namespace Toolkit;

use Toolkit\models\custom\DemoCategory;

$category = DemoCategory::find_by_id(1);

?>

<?= json_encode($category) ?>
```
