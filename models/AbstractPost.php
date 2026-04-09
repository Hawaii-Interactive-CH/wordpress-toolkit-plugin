<?php

namespace Toolkit\models;

// Prevent direct access.
defined( 'ABSPATH' ) or exit;

use Toolkit\models\AbstractTag;
use Toolkit\models\AbstractCategory;
use Toolkit\models\PostType;

abstract class AbstractPost extends PostType {
	const TYPE = 'post';

	/**
	 * Get categories.
	 *
	 * @param callable|null $callback Optional callback.
	 * @return array
	 */
	public function categories( ?callable $callback = null ) {
		return $this->terms( AbstractCategory::class, $callback );
	}

	/**
	 * Get tags.
	 *
	 * @param callable|null $callback Optional callback.
	 * @return array
	 */
	public function tags( ?callable $callback = null ) {
		return $this->terms( AbstractTag::class, $callback );
	}

	/**
	 * Get comma-separated category names.
	 *
	 * @return string
	 */
	public function categories_name() {
		return implode(
			', ',
			$this->categories( function ( $category ) {
				return $category->title();
			} )
		);
	}

	/**
	 * Get comma-separated tag names.
	 *
	 * @return string
	 */
	public function tags_name() {
		return implode(
			', ',
			$this->tags( function ( $tag ) {
				return $tag->title();
			} )
		);
	}
}
