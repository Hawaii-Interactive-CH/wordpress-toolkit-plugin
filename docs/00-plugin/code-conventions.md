# Code Conventions

This project follows the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). The key rules are summarised below.

---

## Indentation

Use **tabs**, not spaces.

```php
// correct
public function id() {
	return $this->_data['id'];
}

// incorrect
public function id() {
    return $this->_data['id'];
}
```

---

## Brace Style

Opening braces go on the **same line** as the declaration (K&R style).

```php
// correct
class Demo extends CustomPostType {
}

public function register() {
}

// incorrect
class Demo extends CustomPostType
{
}
```

---

## Spacing

### Inside parentheses

Always add a space inside parentheses for control structures and function calls.

```php
// correct
if ( $x ) {
}
function_exists( 'acf_register_block' );
in_array( $value, $array, true );

// incorrect
if ($x) {
}
function_exists("acf_register_block");
```

### After `!`

Always add a space after the `!` operator.

```php
// correct
if ( ! $id ) {
}
if ( ! function_exists( 'get_field' ) ) {
}

// incorrect
if (!$id) {
}
```

### Around operators

```php
// correct
$x = 1;
$result = $a + $b;

// incorrect
$x=1;
$result=$a+$b;
```

---

## Quotes

Use **single quotes** for plain strings. Use double quotes only when the string contains a variable or special character that requires it.

```php
// correct
$type = 'post';
trigger_error( 'ACF is not installed.', E_USER_WARNING );

// incorrect
$type = "post";
trigger_error( "ACF is not installed.", E_USER_WARNING );
```

---

## Comparisons

Always use **strict comparisons** (`===` / `!==`).

```php
// correct
if ( 'object' === gettype( $data ) ) {
}
if ( false !== strpos( $str, 'foo' ) ) {
}

// incorrect
if ( gettype( $data ) == 'object' ) {
}
```

Use **Yoda conditions** (constant on the left) for equality checks.

```php
// correct
if ( 'post' === $type ) {
}

// incorrect
if ( $type === 'post' ) {
}
```

---

## Arrays

Use the short `[]` syntax. Align `=>` operators when declaring multi-key arrays.

```php
// correct
$args = [
	'post_type'      => 'demo',
	'posts_per_page' => 10,
	'order'          => 'DESC',
];

// incorrect
$args = array(
	'post_type' => 'demo',
	'posts_per_page' => 10,
);
```

---

## Functions

Prefer `implode()` over its alias `join()`.

```php
// correct
implode( ', ', $items );

// incorrect
join( ', ', $items );
```

Use the third parameter of `in_array()` for strict type checking.

```php
// correct
in_array( $value, $array, true );

// incorrect
in_array( $value, $array );
```

---

## Docblocks

All public and protected methods must have a docblock with `@param` and `@return` tags.

```php
/**
 * Get an ACF field value by key.
 *
 * @param string $key The ACF field key.
 * @return mixed
 */
public function acf( string $key ) {
	// ...
}
```

Private methods and one-liner helpers may omit the docblock only when the method name and signature are self-explanatory.

---

## Naming

| Element | Convention | Example |
|---|---|---|
| Classes | `PascalCase` | `CustomPostType`, `BlockHero` |
| Methods & functions | `snake_case` | `find_by_id()`, `acf_media()` |
| Variables | `snake_case` | `$post_id`, `$render_callback` |
| Constants | `UPPER_SNAKE_CASE` | `TYPE`, `WP_TOOLKIT_VERSION` |
| Hooks | `snake_case` with prefix | `toolkit_register_block` |

---

## Namespaces

All classes must declare a namespace matching their folder path under `Toolkit`.

```php
// Plugin utility → utils/
namespace Toolkit\utils;

// Theme custom model → models/custom/
namespace Toolkit\models\custom;
```

See [namespace.md](namespace.md) for the full namespace reference.

---

## Escaping Output

Always escape output at the point of rendering, using the most specific function available.

```php
echo esc_html( $title );
echo esc_url( $link );
echo esc_attr( $class );
echo wp_kses_post( $content );
```

Never echo raw user data or unverified external values.

---

## Sanitising Input

Sanitise all data coming from `$_GET`, `$_POST`, or the REST API before using it.

```php
$slug = sanitize_text_field( $_POST['slug'] ?? '' );
$id   = absint( $_GET['id'] ?? 0 );
```

---

## Internationalisation

All user-facing strings must be wrapped in a translation function with the plugin text domain.

```php
// correct
__( 'Add new item', 'hi-theme-toolkit' );
esc_html__( 'Category', 'hi-theme-toolkit' );

// incorrect
__( 'Add new item' );
'Add new item';
```
