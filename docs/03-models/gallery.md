# Galerie de photos

## Introduction

Le model `Gallery` est un modèle de base pour les galeries de photos. Il est utilisé pour recuperer des informations sur une galerie de photos.

## Utilisation

### pictures()

Pour avoir les photos de la galerie il suffit d'utiliser la méthode `pictures()`.

```php
<?php

namspace Toolkit;

use Toolkit\models\Gallery;
use Toolkit\models\Media;

// Ids des photos (un tableau de ids acf fonctionne aussi)
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
