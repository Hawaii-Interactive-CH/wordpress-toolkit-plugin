# Custom Post Type

## Créer un Custom Post Type

Pour créer un `Custom Post Type`, ajouter un fichier dans le dossier `models/custom` du thème, ou en générer un via `Toolkit > Models` dans l'administration.

```php
<?php

namespace Toolkit\models\custom;

use Toolkit\models\CustomPostType;

class Demo extends CustomPostType implements \JsonSerializable
{
  const TYPE = 'demo';
  const SLUG = 'demos';

  public static function type_settings()
  {
    return array(
      'menu_position' => 2,
      'label' => 'Demos',
      'labels' =>
      array(
        'name' => 'Demos',
        'singular_name' => 'Demo',
        'menu_name' => 'Demos',
        'all_items' => 'All demos',
        'add_new' => 'Add new',
        'add_new_item' => 'Add new demo',
        'edit_item' => 'Edit demo',
        'new_item' => 'New demo',
        'view_item' => 'View demo',
        'view_items' => 'View demos',
        'search_items' => 'Search demo',
      ),
      'description' => '',
      'public' => true,
      'publicly_queryable' => true,
      'show_ui' => true,
      'show_in_rest' => true,
      'show_in_nav_menus' => true,
      'rest_base' => '',
      'has_archive' => true,
      'show_in_menu' => true,
      'exclude_from_search' => false,
      'capability_type' => 'post',
      'map_meta_cap' => true,
      'hierarchical' => false,
      'rewrite' =>
      array(
        'slug' => 'demos',
        'with_front' => false,
      ),
      'query_var' => true,
      'menu_icon' => 'dashicons-icon-default',
      'supports' =>
      array(
        0 => 'title',
        1 => 'editor',
        2 => 'thumbnail',
        3 => 'excerpt',
      ),
    );
  }

  public function jsonSerialize(): mixed
  {
    return [
      "id" => $this->id(),
      "title" => $this->title(),
      "slug" => $this->slug(),
      "link" => $this->link(),
      "excerpt" => $this->excerpt(),
      "content" => $this->content(),
      "date" => $this->date(),
    ];
  }
}
```

---

## Colonnes de la liste admin

### Ajouter des colonnes — `add_columns()`

Insère des colonnes personnalisées juste après la colonne **Titre**. Idempotent : appeler `add_columns()` plusieurs fois pour le même type n'enregistre les hooks qu'une seule fois.

#### Options par colonne

| Clé | Type | Description |
|---|---|---|
| `label` | `string` | Libellé de l'en-tête de colonne |
| `render` | `callable` | Reçoit l'instance du modèle, doit afficher (echo) le contenu |
| `format` | `string` | *(optionnel)* Raccourci date — remplace `render` par `$post->date($format)` |
| `sortable` | `bool\|string\|array` | *(optionnel)* Rend la colonne triable |

> `format` et `render` sont mutuellement exclusifs. Si `format` est défini, `render` est ignoré.

#### Valeurs de `sortable`

| Valeur | Comportement |
|---|---|
| `true` | Tri par meta_key = slug de la colonne (alphabétique) |
| `'ma_cle'` | Tri par la meta_key spécifiée (alphabétique) |
| `['key' => 'ma_cle', 'numeric' => true]` | Tri numérique par la meta_key spécifiée |

#### Exemple

```php
// Dans functions.php ou l'initialisation du thème
Demo::add_columns([
    'illustration' => [
        'label'  => __('Illustration', 'theme'),
        'render' => fn($post) => $post->thumbnail(
            fn($img) => '<img src="' . esc_url($img->src('thumbnail')) . '" width="60">'
        ),
    ],
    'categorie' => [
        'label'    => __('Catégorie', 'theme'),
        'render'   => fn($post) => esc_html(
            implode(', ', $post->terms(DemoCategory::class, fn($t) => $t->name()))
        ),
        'sortable' => true,
    ],
    'prix' => [
        'label'    => __('Prix', 'theme'),
        'render'   => fn($post) => esc_html($post->acf('prix')) . ' CHF',
        'sortable' => ['key' => 'prix', 'numeric' => true],
    ],
    'date_publication' => [
        'label'  => __('Date', 'theme'),
        'format' => 'd.m.Y',
    ],
]);
```

### Supprimer des colonnes — `remove_columns()`

Supprime une ou plusieurs colonnes par leur slug.

```php
Demo::remove_columns(['title', 'date']);
```

Slugs courants : `cb` (checkbox), `title`, `author`, `date`, `comments`.
