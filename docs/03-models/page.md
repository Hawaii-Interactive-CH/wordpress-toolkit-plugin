# Page

## Introduction

Le model `Page` est un modèle de base pour les pages. Il est utilisé pour recuperer des informations sur une page.

En général, il est utilisé pour les pages statiques en y ajoutant des constantes avec les ID des pages.

Le fichier se trouve dans le thème dans le dossier `models`.

```php
const HOME = 1;
const ABOUT = 2;
const CONTACT = 3;
```

Il suffit de faire un use du model `Page` pour avoir accès à ces constantes.

```php
<?php

namespace Toolkit;

use Toolkit\models\Page;

$page = new Page(Page::HOME);

?>
```
