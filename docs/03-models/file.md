# File

## Introduction

The `File` model is a base model for file attachments. It is used to retrieve information about a file.

## Usage

### url()

Get the file URL.

```php
<?php

namespace Toolkit;

use Toolkit\models\File;

$file = new File(<file_id>);

?>

<?php echo $file->url(); ?>
```
