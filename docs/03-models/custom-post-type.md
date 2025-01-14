# Custom Post Type

## Créer un Custom Post Type

Pour créer un `Custom Post Type`, il suffit d'ajouter un fichier dans le dossier `models/custom` du thème.

Il est possible d'en générer un via l'onglet `Toolkit > models` et click sur le tab `Model` dans l'administration de WordPress ou en copiant et modifiant le code suivant:

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
