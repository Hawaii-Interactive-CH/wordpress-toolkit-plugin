# Option Page

## Introduction

The `OptionPage` model is a base model for options pages. It is used to retrieve information from an options page. It is typically used for theme options pages.

This type of page requires the ACF Pro extension and must be linked in the WordPress admin under `ACF > Add > Settings > Click on Rule and choose "option page" > Select the option page`.

## Usage

### acf()

To retrieve information from the options page, use the `acf()` method.

```php
<?php

namespace Toolkit;

use Toolkit\models\Config;

?>

<?php echo Config::acf(<field_name>); ?>
```

### have_rows()

Returns `true` if there are rows in the repeater with `<field_name>` as the key.

```php
<?php

namespace Toolkit;

use Toolkit\models\Config;

?>

<?php if(Config::have_rows(<field_name>)) { ?>
  <!-- Code -->
<?php } ?>
```
