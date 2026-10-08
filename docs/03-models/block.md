# Custom Block

## Creating a Custom Block

To create a `Block`, add a file in the theme's `models/custom` folder and create its partial in the `partials/blocks` folder with the same name as the `TYPE`.

Copy and modify the following code, then enable the block in `Toolkit > Models`:

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

## Usage

`Blocks` are content elements that can be added to `Posts` and `Pages` from the WordPress editor. They behave like `Gutenberg Blocks` and can be added, edited, and removed from the `Post` or `Page` content.

## Inserter Preview

Without example data, the block inserter shows an empty preview of the block. Add `example` to `settings()` to render it with sample field values (keyed by field name):

```php
public static function settings()
{
  return array(
    'title' => 'Demo',
    // ...
    'example' => array(
      'attributes' => array(
        'mode' => 'preview',
        'data' => array(
          'title' => 'Join us',
          'text' => 'Lorem ipsum dolor sit amet.',
        ),
      ),
    ),
  );
}
```
