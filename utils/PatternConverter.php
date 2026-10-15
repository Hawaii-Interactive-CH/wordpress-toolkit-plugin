<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

/**
 * Converts the plain HTML of a pattern partial into block markup.
 *
 * h1-h6 → heading, p → paragraph, ul/ol → list, img → image,
 * div/section/... → group, [data-acf] → ACF block, anything else → custom HTML.
 * Options are set with data attributes: data-name, data-placeholder,
 * data-acf and data-field-{name}.
 */
class PatternConverter
{
    const GROUP_TAGS = ['div', 'section', 'header', 'footer', 'article', 'aside', 'main'];

    /**
     * ACF field keys by block type, keyed by field name.
     *
     * @var array
     */
    private static $fieldKeys = [];

    /**
     * Convert plain HTML into block markup.
     *
     * @param string $html The pattern HTML.
     * @return string
     */
    public static function convert(string $html): string
    {
        $processor = \WP_HTML_Processor::create_fragment($html);
        if (!$processor) {
            return serialize_blocks([self::leaf('core/html', [], $html)]);
        }

        // Build a tree of nodes: elements (tag, attrs, children, open, close) and texts
        $root = (object) ['children' => []];
        $stack = [$root];

        while ($processor->next_token()) {
            $parent = end($stack);
            $type = $processor->get_token_type();

            if ('#text' === $type) {
                $parent->children[] = (object) ['text' => $processor->serialize_token()];
                continue;
            }

            if ('#tag' !== $type) {
                continue;
            }

            if ($processor->is_tag_closer()) {
                if (count($stack) > 1) {
                    array_pop($stack)->close = $processor->serialize_token();
                }
                continue;
            }

            $node = (object) [
                'tag'      => strtolower($processor->get_tag()),
                'attrs'    => self::attributes($processor),
                'children' => [],
                'open'     => $processor->serialize_token(),
                'close'    => '',
            ];
            $parent->children[] = $node;

            if ($processor->expects_closer()) {
                $stack[] = $node;
            }
        }

        // The parser gave up on an unsupported structure: keep the HTML as is
        if (null !== $processor->get_last_error()) {
            return serialize_blocks([self::leaf('core/html', [], $html)]);
        }

        return serialize_blocks(self::blocks($root->children));
    }

    /**
     * Convert element nodes into blocks, texts outside an element are ignored.
     */
    private static function blocks(array $nodes): array
    {
        $blocks = [];
        foreach ($nodes as $node) {
            if (!isset($node->text)) {
                $blocks[] = self::block($node);
            }
        }
        return $blocks;
    }

    private static function block(object $node): array
    {
        if (isset($node->attrs['data-acf'])) {
            return self::acf($node);
        }

        $tag = $node->tag;
        $attrs = self::block_attrs($node);

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            $level = (int) $matches[1];
            if (2 !== $level) {
                $attrs['level'] = $level;
            }
            $attrs += self::placeholder($node);
            return self::leaf('core/heading', $attrs, "<{$tag}" . self::html_attrs('wp-block-heading', $node) . '>' . self::text($node) . "</{$tag}>");
        }

        if ('p' === $tag) {
            $attrs += self::placeholder($node);
            return self::leaf('core/paragraph', $attrs, '<p' . self::html_attrs('', $node) . '>' . self::text($node) . '</p>');
        }

        if ('ul' === $tag || 'ol' === $tag) {
            if ('ol' === $tag) {
                $attrs['ordered'] = true;
            }
            $items = [];
            foreach ($node->children as $child) {
                if (!isset($child->text) && 'li' === $child->tag) {
                    $items[] = self::leaf('core/list-item', [], '<li>' . self::text($child) . '</li>');
                }
            }
            return self::container('core/list', $attrs, "<{$tag}" . self::html_attrs('wp-block-list', $node) . '>', "</{$tag}>", $items);
        }

        if ('img' === $tag) {
            $img = '<img src="' . esc_url($node->attrs['src'] ?? '') . '" alt="' . esc_attr($node->attrs['alt'] ?? '') . '"/>';
            return self::leaf('core/image', $attrs, '<figure' . self::html_attrs('wp-block-image', $node) . '>' . $img . '</figure>');
        }

        if (in_array($tag, self::GROUP_TAGS, true)) {
            if ('div' !== $tag) {
                $attrs['tagName'] = $tag;
            }
            return self::container('core/group', $attrs, "<{$tag}" . self::html_attrs('wp-block-group', $node) . '>', "</{$tag}>", self::blocks($node->children));
        }

        return self::leaf('core/html', [], self::outer_html($node));
    }

    /**
     * ACF block, with the data-field-{name} values and their field keys.
     */
    private static function acf(object $node): array
    {
        $type = (string) $node->attrs['data-acf'];
        $keys = self::field_keys($type);
        $data = [];

        foreach ($node->attrs as $name => $value) {
            if (!str_starts_with($name, 'data-field-')) {
                continue;
            }
            $field = substr($name, strlen('data-field-'));
            $data[$field] = self::field_value((string) $value);
            if (isset($keys[$field])) {
                $data['_' . $field] = $keys[$field];
            }
        }

        $attrs = ['name' => 'acf/' . $type];
        $metadata = self::metadata($node);
        if ($metadata) {
            $attrs['metadata'] = $metadata;
        }
        if ($data) {
            $attrs['data'] = $data;
        }
        $attrs['mode'] = 'preview';

        return self::leaf('acf/' . $type, $attrs, '');
    }

    /**
     * Decode JSON values (a link field for example), keep the string otherwise.
     */
    private static function field_value(string $value)
    {
        if ('' !== $value && in_array($value[0], ['[', '{'], true)) {
            $decoded = json_decode($value, true);
            if (null !== $decoded) {
                return $decoded;
            }
        }
        return $value;
    }

    /**
     * Field keys of an ACF block, keyed by field name.
     */
    private static function field_keys(string $type): array
    {
        if (!isset(self::$fieldKeys[$type])) {
            self::$fieldKeys[$type] = [];
            if (function_exists('acf_get_field_groups')) {
                foreach (acf_get_field_groups(['block' => 'acf/' . $type]) as $group) {
                    foreach (acf_get_fields($group) ?: [] as $field) {
                        self::$fieldKeys[$type][$field['name']] = $field['key'];
                    }
                }
            }
        }
        return self::$fieldKeys[$type];
    }

    /**
     * Block attributes shared by every element: class, id, data-name and data-locked.
     */
    private static function block_attrs(object $node): array
    {
        $attrs = [];
        $metadata = self::metadata($node);
        if ($metadata) {
            $attrs['metadata'] = $metadata;
        }
        $class = self::class_name($node);
        if ('' !== $class) {
            $attrs['className'] = $class;
        }
        if (!empty($node->attrs['id'])) {
            $attrs['anchor'] = (string) $node->attrs['id'];
        }
        return $attrs;
    }

    /**
     * Block metadata: name in the editor (data-name) and lock for non-administrators (data-locked).
     */
    private static function metadata(object $node): array
    {
        $metadata = [];
        if (!empty($node->attrs['data-name'])) {
            $metadata['name'] = (string) $node->attrs['data-name'];
        }
        if (isset($node->attrs['data-locked'])) {
            $metadata['hithtoLocked'] = true;
        }
        return $metadata;
    }

    private static function placeholder(object $node): array
    {
        return empty($node->attrs['data-placeholder']) ? [] : ['placeholder' => (string) $node->attrs['data-placeholder']];
    }

    /**
     * Theme classes of an element, without the wp-block-* classes added by WordPress.
     */
    private static function class_name(object $node): string
    {
        $classes = preg_split('/\s+/', (string) ($node->attrs['class'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $classes = array_filter(array_map('sanitize_html_class', $classes), function ($class) {
            return '' !== $class && !str_starts_with($class, 'wp-block-');
        });
        return implode(' ', $classes);
    }

    /**
     * id and class attributes of the saved HTML.
     */
    private static function html_attrs(string $base, object $node): string
    {
        $html = '';
        if (!empty($node->attrs['id'])) {
            $html .= ' id="' . esc_attr($node->attrs['id']) . '"';
        }
        $classes = trim($base . ' ' . self::class_name($node));
        if ('' !== $classes) {
            $html .= ' class="' . esc_attr($classes) . '"';
        }
        return $html;
    }

    /**
     * Inner HTML of a text element (heading, paragraph, list item), on a single line.
     */
    private static function text(object $node): string
    {
        return trim(self::inner_html($node));
    }

    private static function inner_html(object $node): string
    {
        $html = '';
        foreach ($node->children as $child) {
            $html .= isset($child->text) ? preg_replace('/\s+/u', ' ', $child->text) : self::outer_html($child);
        }
        return $html;
    }

    private static function outer_html(object $node): string
    {
        return $node->open . self::inner_html($node) . $node->close;
    }

    private static function attributes(\WP_HTML_Processor $processor): array
    {
        $attrs = [];
        foreach ($processor->get_attribute_names_with_prefix('') ?? [] as $name) {
            $attrs[$name] = $processor->get_attribute($name);
        }
        return $attrs;
    }

    /**
     * Block without inner blocks.
     */
    private static function leaf(string $name, array $attrs, string $html): array
    {
        return [
            'blockName'    => $name,
            'attrs'        => $attrs,
            'innerBlocks'  => [],
            'innerHTML'    => $html,
            'innerContent' => '' === $html ? [] : [$html],
        ];
    }

    /**
     * Block with inner blocks: each inner block is a null in innerContent.
     */
    private static function container(string $name, array $attrs, string $open, string $close, array $innerBlocks): array
    {
        return [
            'blockName'    => $name,
            'attrs'        => $attrs,
            'innerBlocks'  => $innerBlocks,
            'innerHTML'    => $open . $close,
            'innerContent' => array_merge([$open], array_fill(0, count($innerBlocks), null), [$close]),
        ];
    }
}
