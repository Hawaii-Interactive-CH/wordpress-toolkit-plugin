# Page à Option

## Introduction

Le model `OptionPage` est un modèle de base pour les pages d'options. Il est utilisé pour recuperer des informations sur une page d'options. En regle générale, il est utilisé pour les pages d'options de thèmes.

Ce type de page nécessite l'extension ACF Pro et doit linké dans l'administration de WordPress `ACF > Ajouter > Paramètres > Clicker sur Règle et choisir "option page" > Séléctionner la page à option`.

## Utilisation

### acf()

Pour avoir les informations de la page d'options il suffit d'utiliser la méthode `acf()`.

```php
<?php

namspace Toolkit;

use Toolkit\models\Config;

?>

<?php echo Config::acf(<field_name>); ?>
```

### have_rows()

Retourne `true` si il y a des lignes dans le repeater avec le nom `<field_name>` comme clé.

```php
<?php

namspace Toolkit;

use Toolkit\models\Config;

?>

<?php if(Config::have_rows(<field_name>)) { ?>
  <!-- Code -->
<?php } ?>
```
