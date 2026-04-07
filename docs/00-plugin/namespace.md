# Namespace System Between the Plugin and the Theme

## Introduction

The namespace system allows defining file paths for classes, functions, and constants. This helps organize code and makes it more readable.

To access plugin classes from the theme, you must use the same namespace: `Toolkit`.

By default, files are first loaded from the plugin, then from the theme if the file exists there.

This allows overriding plugin classes in the theme.

## Configuration

### 1. Add the namespace at the top of the file

```php
<?php

namespace Toolkit;

class Post {
    // ...
}
```

### 2. Use the class in the theme

```php
namespace Toolkit;

use Toolkit\Post;

$post = new Post();
```

Note that the namespace is the same as the plugin's. In general, it is recommended to name namespaces according to the folder structure of the plugin or theme.

Example:

If a file is located in the `inc` folder of the plugin, the namespace will be `Toolkit\Inc`.

## Base Structure

Here is the base structure of the plugin and theme:

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
- Toolkit\utils\Upscale
- Toolkit\utils\WPML
```

### Models

Models are classes used to manage custom post types and custom taxonomies.

To override them in the theme, modify them in the theme's `models` folder. The Abstract classes in the plugin's `models` folder (e.g. `AbstractPost`, ...) define default methods that can be overridden in the theme (e.g. `Post`, ...) if needed.

For custom post types specific to the theme, it is recommended to place them in the theme's `models/custom` folder or use the plugin's built-in generator.

The default models are:

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
