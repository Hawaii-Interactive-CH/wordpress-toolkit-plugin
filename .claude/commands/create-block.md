---
description: 'Generate an ACF Block class extending Toolkit\models\Block and its PHP template'
---

# Create Block

Generate a new ACF Block class and its template file for the WordPress Toolkit plugin.

## Instructions

Ask the user for the following information if not already provided in the arguments (`$ARGUMENTS`):

1. **Block title** (human-readable, e.g. "Key Numbers", "Hero Banner", "Team Members")
2. **Class name** (PascalCase, derived from title by default, prefixed with `Block`, e.g. `BlockKeyNumbers`) — confirm with user
3. **Block TYPE slug** (lowercase with hyphens, prefixed with `block-`, e.g. `block-key-numbers`) — default derived from title
4. **Description** — short description of the block's purpose
5. **Icon** (WordPress dashicon name without prefix, e.g. `chart-bar`, `format-image`, `groups`) — default to `block-default`
6. **Keywords** (comma-separated, used for block search, e.g. `numbers, stats, figures`) — default to block title words
7. **Mode** (`auto`, `edit`, or `preview`) — default to `auto`
8. **ACF fields** — ask the user to list the ACF field keys the block will use (e.g. `title`, `image`, `items`). These will be scaffolded as `$this->acf('field_key')` calls in the template.

## Output

### 1. Class file: `models/custom/{ClassName}.php`

```php
<?php

namespace Toolkit\models\custom;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\models\Block;

class {ClassName} extends Block
{
    const TYPE = '{block-type-slug}';

    public static function settings()
    {
        return [
            'title'       => __('{Block Title}', 'toolkit'),
            'description' => __('{Description}', 'toolkit'),
            'mode'        => '{mode}',
            'icon'        => '{icon}',
            'keywords'    => [{keywords_array}],
        ];
    }
}
```

### 2. Template file: `partials/blocks/{block-type-slug}.php`

Generate a starter template using the ACF fields provided. The `$block` variable is an instance of the class.

```php
<?php

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

/** @var \Toolkit\models\custom\{ClassName} $block */

?>
<div class="block-{block-type-slug}">
    <?php // TODO: render your block content using $block->acf('field_key') ?>
</div>
```

If the user provided ACF field keys, add a commented example for each field:

```php
<?php $title = $block->acf('title'); ?>
```

**Notes:**
- The template is loaded automatically by `Block::render()` from `partials/blocks/{block-type-slug}.php` in the active theme
- The block requires ACF Pro to be installed

## After generating the files

Remind the user to:

1. Register the block in their theme (e.g. `functions.php`):

```php
add_action('acf/init', function () {
    \Toolkit\models\custom\{ClassName}::register();
});
```

2. Create the corresponding ACF field group in the WordPress admin and assign it to the block with the rule: **Block** → **is equal to** → `{Block Title}`
