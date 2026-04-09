# Page

## Introduction

The `Page` model is a base model for pages. It is used to retrieve information about a page.

It is generally used for static pages by adding constants with the page IDs.

The file is located in the theme's `models` folder.

```php
const HOME = 1;
const ABOUT = 2;
const CONTACT = 3;
```

Simply use the `Page` model to access these constants.

```php
<?php

namespace Toolkit;

use Toolkit\models\Page;

$page = new Page(Page::HOME);

?>
```
