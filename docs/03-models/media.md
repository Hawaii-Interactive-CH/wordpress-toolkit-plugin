# Média

## Introduction

Le modèle `AbstractMedia` est la classe de base pour les pièces jointes WordPress (images, SVG, etc.). Il fournit des méthodes pour récupérer l'URL optimisée d'une image, générer des attributs `srcset`, rendre un élément `<picture>` complet, et insérer un SVG en ligne.

Pour l'utiliser, créez une classe dans `models/custom/` qui étend `AbstractMedia` :

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\AbstractMedia;

class Media extends AbstractMedia {}
```

---

## Méthodes disponibles

### src()

Retourne l'URL optimisée d'une image pour une taille donnée. Priorité WebP → PNG/JPG → original. Si la taille demandée n'existe pas, retourne l'URL de l'image originale.

```php
$media = new Media($attachment_id);

// URL de la version WebP générée pour la taille "image-xl"
echo $media->src('image-xl');

// URL de l'image originale
echo $media->src('full');
```

Exemple dans un template :

```php
<img
    src="<?= $media->src('image-xl') ?>"
    alt="<?= $media->alt() ?>">
```

---

### srcset()

Construit la valeur de l'attribut `srcset` à partir d'un tableau associatif `[nom de taille => descripteur de largeur]`. Seules les tailles déjà générées sont incluses.

```php
$media = new Media($attachment_id);

echo $media->srcset([
    'image-s'  => '640w',
    'image-m'  => '960w',
    'image-l'  => '1280w',
    'image-xl' => '1920w',
]);

// → "https://…/image-640x….webp 640w, https://…/image-960x….webp 960w, …"
```

Exemple dans un template :

```php
<img
    src="<?= $media->src('image-xl') ?>"
    srcset="<?= $media->srcset(['image-s' => '640w', 'image-l' => '1280w', 'image-xl' => '1920w']) ?>"
    sizes="100vw"
    alt="<?= $media->alt() ?>">
```

---

### picture()

Génère un élément `<picture>` complet avec des éléments `<source>` et un `<img>` de secours. Chaque entrée du tableau `$sources` correspond à un `<source>`. Supporte les variantes retina (2x) et les media queries.

**Paramètres :**

| Paramètre | Type    | Description |
|-----------|---------|-------------|
| `$sources` | `array` | Tableau de définitions de sources (voir ci-dessous) |
| `$class`  | `string` | Classe CSS optionnelle ajoutée à l'élément `<img>` |
| `$lazy`   | `bool`  | Ajoute `loading="lazy"` à l'`<img>`. Défaut : `true` |

Chaque entrée de `$sources` est un tableau associatif :

| Clé      | Type     | Description |
|----------|----------|-------------|
| `size`   | `string` | **Requis.** Nom de taille enregistrée (ex: `'image-xl'`) |
| `media`  | `string` | Optionnel. Media query CSS (ex: `'(min-width: 1280px)'`) |
| `size2x` | `string` | Optionnel. Nom de taille pour la variante retina 2x |

Le dernier `<source>` résolu est également utilisé comme `src` de l'`<img>` de secours.

```php
$media = new Media($attachment_id);

// Exemple simple : une seule source avec retina
echo $media->picture([
    ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
]);
```

```html
<!-- Résultat -->
<picture>
    <source srcset="https://…/image-xl.webp 1x, https://…/image-xl-2x.webp 2x">
    <img src="https://…/image-xl.webp" alt="…" loading="lazy">
</picture>
```

Exemple avec plusieurs breakpoints :

```php
echo $media->picture([
    ['size' => 'image-s',  'media' => '(max-width: 640px)',  'size2x' => 'image-s-2x'],
    ['size' => 'image-m',  'media' => '(max-width: 1280px)', 'size2x' => 'image-m-2x'],
    ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
], 'hero__image');
```

```html
<!-- Résultat -->
<picture>
    <source media="(max-width: 640px)"  srcset="https://…/image-s.webp 1x, https://…/image-s-2x.webp 2x">
    <source media="(max-width: 1280px)" srcset="https://…/image-m.webp 1x, https://…/image-m-2x.webp 2x">
    <source srcset="https://…/image-xl.webp 1x, https://…/image-xl-2x.webp 2x">
    <img src="https://…/image-xl.webp" alt="…" class="hero__image" loading="lazy">
</picture>
```

---

### inline_svg()

Retourne le contenu brut du fichier SVG pour l'insérer directement dans le HTML. Utile pour contrôler les icônes ou illustrations via CSS/JS. Ne fonctionne que pour les pièces jointes avec l'extension `.svg`.

La déclaration `<?xml ?>` et le DOCTYPE sont supprimés automatiquement.

```php
$icon = new Media($svg_attachment_id);

echo $icon->inline_svg();

// → <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">…</svg>
```

Exemple dans un template :

```php
<button class="btn">
    <?= $icon->inline_svg() ?>
    Télécharger
</button>
```

---

### alt()

Retourne le texte alternatif défini dans la médiathèque WordPress.

```php
echo $media->alt();
// → "Photo de présentation de l'équipe"
```

---

### caption()

Retourne la légende définie dans la médiathèque WordPress.

```php
echo $media->caption();
// → "Photo prise lors de l'événement annuel 2024"
```

---

## Exemple complet

```php
<?php
// Dans functions.php ou un fichier de thème

use Toolkit\models\custom\Media;

// Depuis un champ ACF (retourne un ID)
$attachment_id = get_field('hero_image');

if ($attachment_id) :
    $media = new Media($attachment_id);
?>

<figure class="hero">
    <?= $media->picture([
        ['size' => 'image-s',  'media' => '(max-width: 640px)',  'size2x' => 'image-s-2x'],
        ['size' => 'image-m',  'media' => '(max-width: 1280px)', 'size2x' => 'image-m-2x'],
        ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
    ], 'hero__img') ?>

    <?php if ($media->caption()) : ?>
        <figcaption><?= $media->caption() ?></figcaption>
    <?php endif ?>
</figure>

<?php endif ?>
```

---

## Notes

- Les tailles d'image doivent être enregistrées via `Size::add()` dans `functions.php`. L'image originale est retournée si une taille n'est pas encore générée (traitement en arrière-plan via cron).
- `picture()` et `srcset()` omettent silencieusement les tailles non encore générées.
- `inline_svg()` lit le fichier directement sur le disque. Ne pas utiliser avec des SVG provenant de sources non fiables.
