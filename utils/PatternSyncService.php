<?php

namespace Toolkit\utils;

// Prevent direct access.
defined('ABSPATH') or exit;

/**
 * Updates the toolkit patterns (hithto/*) already inserted in the content
 * with the current version of their partial.
 *
 * The structure (blocks, classes, locks) comes from the partial, the content
 * (texts, images, lists, ACF fields) is kept from the saved block.
 * Blocks are matched by their data-name, or by their type and position.
 * A pattern holding content that has no place in the new version is left as is.
 */
class PatternSyncService
{
    const PREFIX = 'hithto/';
    const BATCH = 20;
    const AJAX_ACTION = 'hithto_pattern_sync';

    /**
     * Attributes set by the partial on the blocks that keep their saved content (image...).
     */
    const STRUCTURE_ATTRS = ['className', 'anchor', 'metadata'];

    /**
     * Blocks whose content is kept, on top of the ACF blocks and the blocks with content attributes.
     */
    const CONTENT_BLOCKS = ['core/heading', 'core/paragraph', 'core/list', 'core/image'];

    /**
     * Blocks always taken from the partial, even if they have content attributes.
     */
    const CODE_BLOCKS = ['core/html', 'core/group'];

    public static function register()
    {
        add_action('wp_ajax_' . self::AJAX_ACTION, [self::class, 'ajax']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function enqueue_scripts($hook)
    {
        if ('toplevel_page_wordpress-toolkit-plugin' !== $hook || !current_user_can('manage_options')) {
            return;
        }

        wp_enqueue_script('hithto-pattern-sync', HITHTO_URL . 'admin/assets/js/pattern-sync.js', [], HITHTO_VERSION, true);
        wp_localize_script('hithto-pattern-sync', 'hithtoPatternSync', [
            'action' => self::AJAX_ACTION,
            'nonce'  => wp_create_nonce(self::AJAX_ACTION),
            'batch'  => self::BATCH,
        ]);
    }

    /**
     * Toolkit patterns currently registered, keyed by name.
     *
     * @return array<string, array> name => registered pattern
     */
    public static function patterns(): array
    {
        $patterns = [];
        foreach (\WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
            if (str_starts_with($pattern['name'], self::PREFIX)) {
                $patterns[$pattern['name']] = $pattern;
            }
        }
        ksort($patterns);
        return $patterns;
    }

    /**
     * Number of contents using a pattern.
     */
    public static function usage_count(string $name): int
    {
        return count(self::find_posts([$name]));
    }

    /**
     * Contents holding at least one of the patterns (every post type, revisions and trash excluded).
     *
     * @param string[] $names Pattern names.
     * @return int[] Post IDs.
     */
    public static function find_posts(array $names): array
    {
        global $wpdb;

        if (!$names) {
            return [];
        }

        $where = [];
        $args = [];
        foreach ($names as $name) {
            $where[] = 'post_content LIKE %s';
            $args[] = '%' . $wpdb->esc_like('"patternName":"' . $name . '"') . '%';
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the placeholders are built above
        $sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_status NOT IN ('auto-draft', 'trash', 'inherit') AND (" . implode(' OR ', $where) . ') ORDER BY ID';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return array_map('intval', $wpdb->get_col($wpdb->prepare($sql, $args)));
    }

    /**
     * Resync the patterns of a post.
     *
     * @param int      $post_id
     * @param string[] $names   Patterns to resync.
     * @param bool     $apply   Save the post, or only report what would change.
     * @return array Report: id, title, link, updated (count), skipped (list of pattern title + reason), saved.
     */
    public static function sync_post(int $post_id, array $names, bool $apply): array
    {
        $post = get_post($post_id);
        $report = [
            'id'      => $post_id,
            'title'   => $post ? (get_the_title($post) ?: '#' . $post_id) : '#' . $post_id,
            'link'    => $post ? (string) get_edit_post_link($post, 'raw') : '',
            'type'    => $post ? $post->post_type : '',
            'updated' => 0,
            'skipped' => [],
            'saved'   => false,
            'error'   => '',
        ];

        if (!$post) {
            $report['error'] = __('Content not found.', 'hi-theme-toolkit');
            return $report;
        }

        $patterns = array_intersect_key(self::patterns(), array_flip($names));
        $blocks = self::sync_blocks(parse_blocks($post->post_content), $patterns, $report);

        if ($report['updated'] && $apply) {
            $result = wp_update_post([
                'ID'           => $post_id,
                'post_content' => wp_slash(serialize_blocks($blocks)),
            ], true);

            if (is_wp_error($result)) {
                $report['error'] = $result->get_error_message();
            } else {
                $report['saved'] = true;
            }
        }

        return $report;
    }

    /**
     * Walk the block tree and resync the pattern roots.
     */
    private static function sync_blocks(array $blocks, array $patterns, array &$report): array
    {
        foreach ($blocks as $i => $block) {
            $name = $block['attrs']['metadata']['patternName'] ?? '';

            if ($name && isset($patterns[$name])) {
                $result = self::sync_pattern($block, $patterns[$name]);
                if (is_string($result)) {
                    $report['skipped'][] = ['pattern' => $patterns[$name]['title'], 'reason' => $result];
                } elseif (null !== $result) {
                    $blocks[$i] = $result;
                    $report['updated']++;
                }
                continue;
            }

            if (!empty($block['innerBlocks'])) {
                $blocks[$i]['innerBlocks'] = self::sync_blocks($block['innerBlocks'], $patterns, $report);
            }
        }
        return $blocks;
    }

    /**
     * Rebuild a pattern from its partial, with the content of the saved block.
     *
     * @return array|string|null The new block, the reason why it is skipped, or null when nothing changes.
     */
    private static function sync_pattern(array $old, array $pattern)
    {
        $roots = self::named(parse_blocks($pattern['content']));
        if (1 !== count($roots)) {
            return __('the pattern must have a single root block to be resynced', 'hi-theme-toolkit');
        }

        $oldBlocks = [];
        self::content_blocks([$old], $oldBlocks);
        $newBlocks = [];
        self::content_blocks($roots, $newBlocks);

        $matches = self::match($newBlocks, $oldBlocks);

        // Never lose content: content blocks of the saved pattern must all have a place in the new version
        $lost = array_filter(array_diff_key($oldBlocks, array_flip($matches)), [self::class, 'has_content']);
        if ($lost) {
            $labels = array_map(function ($block) {
                return $block['attrs']['metadata']['name'] ?? $block['blockName'];
            }, array_values($lost));
            /* translators: %s: list of block names */
            return sprintf(__('content without a place in the new version: %s', 'hi-theme-toolkit'), implode(', ', array_unique($labels)));
        }

        $index = 0;
        $new = self::rebuild($roots, $oldBlocks, $matches, $index)[0];

        // Keep the pattern link set by the editor on insertion
        foreach (['patternName', 'categories'] as $key) {
            if (isset($old['attrs']['metadata'][$key])) {
                $new['attrs']['metadata'][$key] = $old['attrs']['metadata'][$key];
            }
        }

        if (serialize_block($new) === serialize_block($old)) {
            return null;
        }

        return $new;
    }

    /**
     * Content blocks of a pattern, in document order. Content blocks are leaves: their inner blocks
     * (list items...) are part of their content. Wrappers (groups...) can change freely.
     */
    private static function content_blocks(array $blocks, array &$list): void
    {
        foreach (self::named($blocks) as $block) {
            if (self::is_content($block['blockName'])) {
                $list[] = $block;
            } else {
                self::content_blocks($block['innerBlocks'], $list);
            }
        }
    }

    /**
     * Match the content blocks of the partial with the saved ones, same block type only:
     * first by data-name, then in order among the blocks left.
     *
     * @return int[] Index of the new block => index of the saved block.
     */
    private static function match(array $newBlocks, array $oldBlocks): array
    {
        $matches = [];
        $used = [];

        foreach ($newBlocks as $i => $block) {
            $name = (string) ($block['attrs']['metadata']['name'] ?? '');
            if ('' === $name) {
                continue;
            }
            foreach ($oldBlocks as $j => $old) {
                if (!isset($used[$j]) && $old['blockName'] === $block['blockName'] && ($old['attrs']['metadata']['name'] ?? '') === $name) {
                    $matches[$i] = $j;
                    $used[$j] = true;
                    break;
                }
            }
        }

        foreach ($newBlocks as $i => $block) {
            if (isset($matches[$i])) {
                continue;
            }
            foreach ($oldBlocks as $j => $old) {
                if (!isset($used[$j]) && $old['blockName'] === $block['blockName']) {
                    $matches[$i] = $j;
                    $used[$j] = true;
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Rebuild the blocks of the partial, filling the content blocks with the matching saved block.
     * Walks the tree in the same order as content_blocks().
     */
    private static function rebuild(array $blocks, array $oldBlocks, array $matches, int &$index): array
    {
        foreach ($blocks as $i => $block) {
            if (empty($block['blockName'])) {
                continue;
            }

            if (self::is_content($block['blockName'])) {
                if (isset($matches[$index])) {
                    $blocks[$i] = self::merge($block, $oldBlocks[$matches[$index]]);
                }
                $index++;
                continue;
            }

            $blocks[$i]['innerBlocks'] = self::rebuild($block['innerBlocks'], $oldBlocks, $matches, $index);
        }
        return $blocks;
    }

    /**
     * Structure of the partial block, content of the saved block.
     */
    private static function merge(array $new, array $old): array
    {
        $name = $new['blockName'];

        // Text blocks: new tag and classes, saved inner HTML
        if ('core/heading' === $name || 'core/paragraph' === $name) {
            $html = self::replace_inner($new['innerHTML'], self::inner_html($old['innerHTML']));
            if (null === $html) {
                return $old;
            }
            $new['innerHTML'] = $html;
            $new['innerContent'] = [$html];
            return $new;
        }

        // List: new wrapper, saved items
        if ('core/list' === $name) {
            $strings = array_values(array_filter($new['innerContent'], 'is_string'));
            $open = $strings[0] ?? '';
            $close = count($strings) > 1 ? end($strings) : '';
            $new['innerBlocks'] = $old['innerBlocks'];
            $new['innerHTML'] = $open . $close;
            $new['innerContent'] = array_merge([$open], array_fill(0, count($old['innerBlocks']), null), [$close]);
            return $new;
        }

        // ACF: new settings, saved field values over the default ones
        if (str_starts_with($name, 'acf/')) {
            $data = array_merge((array) ($new['attrs']['data'] ?? []), (array) ($old['attrs']['data'] ?? []));
            if ($data) {
                $new['attrs']['data'] = $data;
            }
            return $new;
        }

        // Other blocks (image...): saved block, with the classes, anchor and metadata of the partial
        $block = $old;
        foreach (self::STRUCTURE_ATTRS as $attr) {
            if (isset($new['attrs'][$attr])) {
                $block['attrs'][$attr] = $new['attrs'][$attr];
            } else {
                unset($block['attrs'][$attr]);
            }
        }

        // The wrapper tag is in the first chunk of the HTML (the whole HTML for a block without inner blocks)
        $block['innerHTML'] = self::retag($old['innerHTML'], $old['attrs'], $block['attrs']);
        if (isset($old['innerContent'][0]) && is_string($old['innerContent'][0])) {
            $block['innerContent'][0] = self::retag($old['innerContent'][0], $old['attrs'], $block['attrs']);
        }

        return $block;
    }

    /**
     * Swap the classes and id of the first tag of the saved HTML for the ones of the partial.
     */
    private static function retag(string $html, array $oldAttrs, array $newAttrs): string
    {
        $tags = new \WP_HTML_Tag_Processor($html);
        if (!$tags->next_tag()) {
            return $html;
        }

        foreach (preg_split('/\s+/', (string) ($oldAttrs['className'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $class) {
            $tags->remove_class($class);
        }
        foreach (preg_split('/\s+/', (string) ($newAttrs['className'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $class) {
            $tags->add_class($class);
        }

        if (!empty($newAttrs['anchor'])) {
            $tags->set_attribute('id', $newAttrs['anchor']);
        } elseif (!empty($oldAttrs['anchor'])) {
            $tags->remove_attribute('id');
        }

        return $tags->get_updated_html();
    }

    /**
     * Inner HTML of a single element (<h2 ...>inner</h2>).
     */
    private static function inner_html(string $html): string
    {
        return preg_match('#^\s*<([a-z][a-z0-9]*)\b[^>]*>(.*)</\1>\s*$#is', $html, $matches) ? $matches[2] : '';
    }

    private static function replace_inner(string $html, string $inner): ?string
    {
        if (!preg_match('#^(\s*<([a-z][a-z0-9]*)\b[^>]*>)(.*)(</\2>\s*)$#is', $html, $matches)) {
            return null;
        }
        return $matches[1] . $inner . $matches[4];
    }

    /**
     * Content block: its content is kept from the saved block.
     */
    private static function is_content(string $name): bool
    {
        if (in_array($name, self::CODE_BLOCKS, true)) {
            return false;
        }
        if (in_array($name, self::CONTENT_BLOCKS, true) || str_starts_with($name, 'acf/')) {
            return true;
        }

        $type = \WP_Block_Type_Registry::get_instance()->get_registered($name);
        if (!$type) {
            return false;
        }
        if (!empty($type->supports['contentRole'])) {
            return true;
        }
        foreach ((array) $type->attributes as $attribute) {
            if (($attribute['role'] ?? $attribute['__experimentalRole'] ?? '') === 'content') {
                return true;
            }
        }
        return false;
    }

    /**
     * Saved content worth keeping: empty headings and paragraphs (placeholder only) can be dropped.
     */
    private static function has_content(array $block): bool
    {
        if ('core/heading' === $block['blockName'] || 'core/paragraph' === $block['blockName']) {
            return '' !== trim(html_entity_decode(wp_strip_all_tags(self::inner_html($block['innerHTML']))));
        }
        return true;
    }

    /**
     * Blocks with a name (the parser adds unnamed blocks for the whitespace between blocks).
     */
    private static function named(array $blocks): array
    {
        return array_values(array_filter($blocks, function ($block) {
            return !empty($block['blockName']);
        }));
    }

    /**
     * AJAX: scan (post IDs), preview or apply (on a batch of post IDs).
     */
    public static function ajax()
    {
        check_ajax_referer(self::AJAX_ACTION, 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'hi-theme-toolkit'), 403);
        }

        $post_data = wp_unslash($_POST);
        $step = sanitize_key($post_data['step'] ?? '');
        $names = array_values(array_intersect(
            array_map('sanitize_text_field', (array) ($post_data['patterns'] ?? [])),
            array_keys(self::patterns())
        ));

        if (!$names) {
            wp_send_json_error(__('Select at least one pattern.', 'hi-theme-toolkit'));
        }

        if ('scan' === $step) {
            wp_send_json_success(['ids' => self::find_posts($names)]);
        }

        if ('preview' !== $step && 'apply' !== $step) {
            wp_send_json_error(__('Unknown step.', 'hi-theme-toolkit'));
        }

        $ids = array_slice(array_filter(array_map('absint', (array) ($post_data['ids'] ?? []))), 0, self::BATCH);
        $reports = [];
        foreach ($ids as $id) {
            if (current_user_can('edit_post', $id)) {
                $reports[] = self::sync_post($id, $names, 'apply' === $step);
            }
        }

        wp_send_json_success(['reports' => $reports]);
    }
}
