# Fichier

## Introduction

Le model `File` est un modèle de base pour les fichiers. Il est utilisé pour recuperer des informations sur un fichier.

## Utilisation

### url()

Avoir l'url du fichier.

```php
<?php

namspace Toolkit;

use Toolkit\models\File;

$file = new File(<file_id>);

?>

<?php echo $file->url(); ?>
```
