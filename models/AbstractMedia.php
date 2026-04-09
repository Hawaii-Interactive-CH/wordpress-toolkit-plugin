<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\utils\Size;

abstract class AbstractMedia extends PostType {
	const TYPE = 'attachment';

	/**
	 * Get the optimized image URL for a registered size.
	 *
	 * Returns the WebP URL when available, falls back to PNG/JPG, then to the
	 * original full-size URL if the requested size has not been generated yet.
	 *
	 * @param string $size A size name registered via Size::add(), or "full".
	 * @return string|false The image URL, or false if the attachment doesn't exist.
	 */
	public function src( $size = 'thumbnail' ): string {
		$data = Size::src( $this->id(), $size );

		if ( 'full' !== $size && ! $data ) {
			return $this->src( 'full' );
		}

		if ( ! $data ) {
			return false;
		}

		if ( isset( $data[0] ) ) {
			return $data[0];
		}

		return $data['src'];
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
	public function srcset( array $sizes ): string {
		$parts = [];

		foreach ( $sizes as $size_name => $descriptor ) {
			$url = $this->src( $size_name );
			if ( $url ) {
				$parts[] = $url . ' ' . $descriptor;
			}
		}

		return implode( ', ', $parts );
	}

	/**
	 * Render a complete <picture> element with WebP sources and a fallback <img>.
	 *
	 * Each entry in $sources defines one <source> element. The last resolved source
	 * is used as the fallback <img> src unless $fallback_size is provided explicitly.
	 * Supports two source modes, which can be mixed:
	 *
	 * Density-descriptor mode (1x/2x):
	 *   - 'size'   string  Required. Registered size name (e.g. 'image-xl').
	 *   - 'size2x' string  Optional. Size name for the 2x retina variant.
	 *
	 * Width-descriptor mode (w) — default when 'sizes' is omitted or a non-empty string:
	 *   - 'size'   string       Required. Registered size name. Width is looked up from Size registry.
	 *   - 'size2x' string       Optional. Registered size name for retina. Width is looked up automatically.
	 *   - 'sizes'  string|false Optional. Sizes attribute value. Defaults to '100vw'. Pass false to use density mode.
	 *
	 * Density-descriptor mode (1x/2x) — opt in with 'sizes' => false:
	 *   - 'size'   string  Required. Registered size name (e.g. 'image-xl').
	 *   - 'size2x' string  Optional. Size name for the 2x retina variant.
	 *   - 'sizes'  false   Required to activate this mode.
	 *
	 * Explicit srcset array mode (advanced override):
	 *   - 'srcset' array   Map of size name => custom descriptor (e.g. ['image-xl' => '1920w']).
	 *
	 * Common keys (all modes):
	 *   - 'media'  string  Optional. Media query (e.g. '(min-width: 1280px)').
	 *
	 * @param array  $sources       Ordered array of source definitions.
	 * @param string $class         Optional CSS class added to the <img> element.
	 * @param bool   $lazy          Whether to add loading="lazy" to the <img>. Default true.
	 * @param bool   $decode        Whether to add decoding="async" to the <img>. Default false.
	 * @param string $fallback_size Optional size name to use as the <img> src. Required when
	 *                              all sources carry a media query (no implicit default source).
	 * @return string               The rendered <picture> HTML, or an empty string if no sources resolved.
	 *
	 * @example Width descriptors (w) — default mode, sizes="100vw" applied automatically
	 * echo $media->picture([
	 *     ['size' => 'image-xl', 'size2x' => 'image-xl-2x', 'media' => '(min-width: 1920px)'],
	 *     ['size' => 'image-s',  'size2x' => 'image-s-2x',  'media' => '(max-width: 400px)'],
	 * ], '', true, true, 'image-xl');
	 *
	 * @example Density descriptors (1x/2x) — opt in with 'sizes' => false
	 * echo $media->picture([
	 *     ['size' => 'image-s',  'size2x' => 'image-s-2x',  'media' => '(max-width: 640px)',  'sizes' => false],
	 *     ['size' => 'image-xl', 'size2x' => 'image-xl-2x', 'sizes' => false],
	 * ]);
	 */
	public function picture( array $sources, string $class = '', bool $lazy = true, bool $decode = false, string $fallback_size = '' ): string {
		if ( empty( $sources ) ) {
			return '';
		}

		$alt         = esc_attr( $this->alt() );
		$class_attr  = $class  ? ' class="' . esc_attr( $class ) . '"' : '';
		$lazy_attr   = $lazy   ? ' loading="lazy"' : '';
		$decode_attr = $decode ? ' decoding="async"' : '';
		$html        = '<picture>';
		$last_src    = '';

		foreach ( $sources as $source ) {
			// 'sizes' defaults to '100vw' (width-descriptor mode); pass false for density (1x/2x) mode.
			$sizes_value = $source['sizes'] ?? '100vw';

			if ( isset( $source['srcset'] ) && is_array( $source['srcset'] ) ) {
				// Explicit srcset array mode: ['srcset' => ['size-name' => 'Xw', ...]]
				$srcset_value = $this->srcset( $source['srcset'] );
				if ( ! $srcset_value ) {
					continue;
				}
				if ( ! $last_src ) {
					$first_url = $this->src( array_key_first( $source['srcset'] ) );
					if ( $first_url ) {
						$last_src = $first_url;
					}
				}
			} elseif ( false !== $sizes_value && '' !== $sizes_value ) {
				// Width-descriptor mode: auto-derive pixel widths from registered sizes.
				$size_name = $source['size'] ?? '';
				$url       = $this->src( $size_name );
				if ( ! $url ) {
					continue;
				}
				$last_src     = $url;
				$w            = Size::width( $size_name );
				$srcset_value = $url . ( $w ? " {$w}w" : ' 1x' );
				if ( ! empty( $source['size2x'] ) ) {
					$url_2x = $this->src( $source['size2x'] );
					if ( $url_2x ) {
						$w_2x          = Size::width( $source['size2x'] );
						$srcset_value .= ', ' . $url_2x . ( $w_2x ? " {$w_2x}w" : ' 2x' );
					}
				}
			} else {
				// Density-descriptor mode: 1x / 2x (opt in with 'sizes' => false).
				$size_name = $source['size'] ?? '';
				$url       = $this->src( $size_name );
				if ( ! $url ) {
					continue;
				}
				$last_src     = $url;
				$srcset_value = $url . ' 1x';
				if ( ! empty( $source['size2x'] ) ) {
					$url_2x = $this->src( $source['size2x'] );
					if ( $url_2x ) {
						$srcset_value .= ', ' . $url_2x . ' 2x';
					}
				}
			}

			$media_attr = ! empty( $source['media'] )                       ? ' media="' . esc_attr( $source['media'] ) . '"' : '';
			$sizes_attr = ( $sizes_value && true !== $sizes_value ) ? ' sizes="' . esc_attr( $sizes_value ) . '"' : '';

			$html .= '<source' . $media_attr . ' srcset="' . esc_attr( $srcset_value ) . '"' . $sizes_attr . '>';
		}

		$fallback_src = $fallback_size ? ( $this->src( $fallback_size ) ?: $last_src ) : $last_src;

		if ( ! $fallback_src ) {
			return '';
		}

		$html .= '<img src="' . esc_url( $fallback_src ) . '" alt="' . $alt . '"' . $class_attr . $lazy_attr . $decode_attr . '>';
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
	public function inline_svg(): string {
		$file = get_attached_file( $this->id() );

		if ( ! $file || 'svg' !== strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
			return '';
		}

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return '';
		}

		$svg = file_get_contents( $file );

		if ( ! $svg ) {
			return '';
		}

		// Strip XML declaration and DOCTYPE.
		$svg = preg_replace( '/<\?xml[^>]*\?>/i', '', $svg );
		$svg = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $svg );
		$svg = trim( $svg );

		return $svg;
	}

	/**
	 * Get the image alt text set in the WordPress media library.
	 *
	 * @return string The alt text, or an empty string if not set.
	 */
	public function alt(): string {
		return get_post_meta( $this->id(), '_wp_attachment_image_alt', true );
	}

	/**
	 * Get the caption set in the WordPress media library.
	 *
	 * @return string The caption, or an empty string if not set.
	 */
	public function caption(): string {
		return wp_get_attachment_caption( $this->id() );
	}
}
