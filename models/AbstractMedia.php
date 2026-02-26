<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\utils\Size;

abstract class AbstractMedia extends PostType
{
    const TYPE = "attachment";

    /**
     * Get the optimized image URL for a registered size.
     *
     * Returns the WebP URL when available, falls back to PNG/JPG, then to the
     * original full-size URL if the requested size has not been generated yet.
     *
     * @param string $size A size name registered via Size::add(), or "full".
     * @return string|false The image URL, or false if the attachment doesn't exist.
     */
    public function src($size = "thumbnail"): string
    {
        $data = Size::src($this->id(), $size);

        if ($size !== "full" and !$data) {
            return $this->src("full");
        }

        if (!$data) {
            return false;
        }

        if (isset($data[0])) {
            return $data[0];
        }

        return $data["src"];
    }

    /**
     * Build a srcset attribute string from a map of size names to viewport widths.
     *
     * Each entry in $sizes maps a registered size name to a width descriptor (e.g. "640w").
     * Only sizes that have already been generated are included in the output.
     *
     * @param array<string, string> $sizes Associative array of size name => width descriptor.
     *                                     Example: ['image-s' => '640w', 'image-l' => '1280w']
     * @return string A ready-to-use srcset attribute value, or an empty string if no URLs resolved.
     *
     * @example
     * $media->srcset(['image-s' => '640w', 'image-m' => '960w', 'image-l' => '1280w'])
     * // → "https://…/image-640x…webp 640w, https://…/image-960x…webp 960w, …"
     */
    public function srcset(array $sizes): string
    {
        $parts = [];

        foreach ($sizes as $size_name => $descriptor) {
            $url = $this->src($size_name);
            if ($url) {
                $parts[] = $url . ' ' . $descriptor;
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Render a complete <picture> element with WebP sources and a fallback <img>.
     *
     * Each entry in $sources defines one <source> element. The last entry in $sources
     * is also used as the fallback <img> src. Outputs lazy loading and optional CSS class.
     *
     * @param array  $sources  Ordered array of source definitions. Each entry is an associative
     *                         array with the following keys:
     *                         - 'size'   string  Required. Registered size name (e.g. 'image-xl').
     *                         - 'media'  string  Optional. Media query (e.g. '(min-width: 1280px)').
     *                         - 'size2x' string  Optional. Size name for the 2x retina variant.
     * @param string $class    Optional CSS class added to the <img> element.
     * @param bool   $lazy     Whether to add loading="lazy" to the <img>. Default true.
     * @return string          The rendered <picture> HTML, or an empty string if no sources resolved.
     *
     * @example
     * echo $media->picture([
     *     ['size' => 'image-s',  'media' => '(max-width: 640px)',  'size2x' => 'image-s-2x'],
     *     ['size' => 'image-m',  'media' => '(max-width: 1280px)', 'size2x' => 'image-m-2x'],
     *     ['size' => 'image-xl', 'size2x' => 'image-xl-2x'],
     * ]);
     */
    public function picture(array $sources, string $class = '', bool $lazy = true): string
    {
        if (empty($sources)) {
            return '';
        }

        $alt   = esc_attr($this->alt());
        $class = $class ? ' class="' . esc_attr($class) . '"' : '';
        $lazy  = $lazy ? ' loading="lazy"' : '';

        $html         = '<picture>';
        $fallback_src = '';

        foreach ($sources as $source) {
            $size_name = $source['size'] ?? '';
            $url       = $this->src($size_name);

            if (!$url) {
                continue;
            }

            // Keep track of the last resolved URL to use as <img> fallback.
            $fallback_src = $url;

            $srcset_value = $url . ' 1x';

            if (!empty($source['size2x'])) {
                $url_2x = $this->src($source['size2x']);
                if ($url_2x) {
                    $srcset_value .= ', ' . $url_2x . ' 2x';
                }
            }

            $media_attr = !empty($source['media']) ? ' media="' . esc_attr($source['media']) . '"' : '';

            $html .= '<source' . $media_attr . ' srcset="' . esc_attr($srcset_value) . '">';
        }

        if (!$fallback_src) {
            return '';
        }

        $html .= '<img src="' . esc_url($fallback_src) . '" alt="' . $alt . '"' . $class . $lazy . '>';
        $html .= '</picture>';

        return $html;
    }

    /**
     * Render the raw SVG markup for this attachment inline.
     *
     * Reads the SVG file from disk and returns its contents so it can be
     * embedded directly in the HTML, enabling CSS/JS control over the SVG.
     * Only works for attachments with an .svg extension.
     *
     * The output is sanitized: the <?xml …?> declaration and DOCTYPE are
     * stripped, and only the inner <svg> element is returned.
     *
     * @return string The sanitized <svg> markup, or an empty string if the
     *                attachment is not an SVG or the file cannot be read.
     *
     * @example
     * echo $icon->inline_svg();
     * // → <svg xmlns="…" viewBox="0 0 24 24">…</svg>
     */
    public function inline_svg(): string
    {
        $file = get_attached_file($this->id());

        if (!$file || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg') {
            return '';
        }

        if (!file_exists($file) || !is_readable($file)) {
            return '';
        }

        $svg = file_get_contents($file);

        if (!$svg) {
            return '';
        }

        // Strip XML declaration and DOCTYPE.
        $svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg);
        $svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg);
        $svg = trim($svg);

        return $svg;
    }

    /**
     * Get the image alt text set in the WordPress media library.
     *
     * @return string The alt text, or an empty string if not set.
     */
    public function alt(): string
    {
        return get_post_meta($this->id(), "_wp_attachment_image_alt", true);
    }

    /**
     * Get the caption set in the WordPress media library.
     *
     * @return string The caption, or an empty string if not set.
     */
    public function caption(): string
    {
        return wp_get_attachment_caption($this->id());
    }
}
