# Photo Gallery

## Introduction

The `Gallery` model is a base model for photo galleries. It is used to retrieve information about a photo gallery.

## Usage

### pictures()

To get the gallery photos, use the `pictures()` method.

```php
<?php

namespace Toolkit;

use Toolkit\models\Gallery;
use Toolkit\models\Media;

// Image IDs (an array of ACF IDs also works)
$images_ids = [1, 2, 3];

$gallery = new Gallery($images_ids);

?>

<?php $gallery->pictures(function(Media $media) { ?>
  <img
    alt="<?= $media->alt() ?>"
    src="<?= $media->src("image-xl") ?>"
    srcset="<?= $media->src("image-s") ?> 400w, 
            <?= $media->src("image-m") ?> 860w,
            <?= $media->src("image-l") ?> 1280w, 
            <?= $media->src("image-xl") ?> 1920w"
    sizes="100vw">
<?php }); ?>
```
