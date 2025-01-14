# Média

## Introduction

Le model `Media` est un modèle de base pour les médias. Il est utilisé pour recuperer des informations sur un média.

## Utilisation

### src()

Avoir l'url du média selon la taille. Les tailles disponibles sont celles définies dans le fichier `function.php`.

```php
<?php

namspace Toolkit;

use Toolkit\models\Media;

$media = new Media(<media_id>);

?>

<img
    alt="<?= $media->alt() ?>"
    src="<?= $media->src("image-xl") ?>"
    srcset="<?= $media->src("image-s") ?> 400w, 
            <?= $media->src("image-m") ?> 860w,
            <?= $media->src("image-l") ?> 1280w, 
            <?= $media->src("image-xl") ?> 1920w"
    sizes="100vw">

// Autre exemple

<figure>
    <picture>
        <source
                media="(min-width: 1281px)"
                srcset="<?= $media->src("image-xl") ?> 1x, <?= $media->src("image-xl-2x") ?> 2x">
        <source
                media="(max-width: 1280px)" 
                srcset="<?= $media->src("image-l") ?> 1x, <?= $media->src("image-l-2x") ?> 2x">
        <source
                media="(max-width: 860px)"
                srcset="<?= $media->src("image-m") ?> 1x, <?= $media->src("image-m-2x") ?> 2x">
        <source
                media="(max-width: 400px)"
                srcset="<?= $media->src("image-s") ?> 1x, <?= $media->src("image-s-2x") ?> 2x">
        <img
            srcset="<?= $media->src("image-l") ?> 1280w,<?= $media->src("image-xl") ?> 1920w"
            src="<?= $media->src("image-xl") ?>"
            alt="<?= $media->alt() ?>">
    </picture>
</figure>
```

### alt()

Avoir le texte alternatif du média.

```php
<?php

namspace Toolkit;

use Toolkit\models\Media;

$media = new Media(<media_id>);

?>

<?php echo $media->alt(); ?>
```

### caption()

Avoir la légende du média.

```php

<?php

namspace Toolkit;

use Toolkit\models\Media;

$media = new Media(<media_id>);

?>

<?php echo $media->caption(); ?>
```
